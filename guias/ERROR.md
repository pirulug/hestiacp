# Documentación del Error de Despliegue Git y Solución Técnica

## 1. Resumen del Problema

Tras la integración del diálogo modal de selección de despliegue (Pull manual con o sin ejecución del Build Pipeline), se presentaron tres anomalías críticas:

1. **Despliegue manual inoperante:** Al presionar el botón "Deploy / Pull Now" y seleccionar cualquiera de las opciones ("Solo Pull" o "Pull + Ejecutar Pipeline"), la interfaz recargaba sin ejecutar el pull ni los comandos de construcción.
2. **Fallo en despliegue automático (`git push`):** Las peticiones enviadas por GitHub / GitLab al endpoint de Webhook fallaban con error HTTP 500 y no actualizaban el repositorio ni ejecutaban el despliegue.
3. **Redirección involuntaria a `/list/web/`:** Al realizar acciones en dominios pertenecientes a usuarios distintos de `admin`, la interfaz redirigía forzosamente al listado principal de dominios web.

---

## 2. Trazabilidad de Commits

* **Último commit funcional inicial:** `17837b970c7e22bc4509d94efd51f08b26d73ab0`
* **Commit que introdujo los fallos:** `cc9812db271f981402b619efd8bb77a4cc047578` (y rama `1987af4a9e29951e993912f73447f730918f5f84`)
* **Commit de corrección y solución definitiva:** `8dfaf75d633289da9083042986e8c028b0bc1dee`

---

## 3. Análisis de Causa Raíz

### Causa 1: Escapado HTML indebido dentro del bloque `<script>` (JavaScript)

* **Archivo afectado:** `web/templates/pages/edit_web_git.php`
* **Detalle:** La función JavaScript `executeDeploy(runBuild)` construía la URL base utilizando la función PHP `tohtml()` sobre el resultado de `http_build_query()`:
  ```php
  // CÓDIGO CON ERROR:
  var baseUrl = "/edit/git/?<?= tohtml(http_build_query(["domain" => $v_domain, "action" => "pull", "token" => $_SESSION["token"]])) ?>";
  ```
* **Impacto:** La función `tohtml()` convierte los caracteres `&` en entidades HTML `&amp;`. Cuando este valor es asignado a una variable de cadena dentro de JavaScript, el motor del navegador no decodifica la entidad HTML, enviando una petición HTTP GET con la siguiente estructura:
  ```text
  /edit/git/?domain=ejemplo.com&amp;action=pull&amp;token=xyz&build=yes
  ```
* **Consecuencia en PHP:** El superglobal `$_GET` interpretaba `amp;action` como nombre del parámetro en lugar de `action`. Como resultado, la validación `if (isset($_GET["action"]) && $_GET["action"] === "pull")` evaluaba en falso y la solicitud era ignorada por completo.

---

### Causa 2: Falta de Autoload de Composer en el Webhook

* **Archivo afectado:** `web/api/webhook/index.php`
* **Detalle:** El script declaraba el uso de la función `quoteshellarg`:
  ```php
  use function Hestiacp\quoteshellarg\quoteshellarg;
  ```
  Sin embargo, no cargaba el archivo de autoload de Composer (`vendor/autoload.php`).
* **Impacto:** Cuando GitHub o GitLab enviaban una notificación vía POST al webhook, la primera llamada a `quoteshellarg()` arrojaba una excepción no capturada de PHP:
  ```text
  Fatal error: Uncaught Error: Call to undefined function Hestiacp\quoteshellarg\quoteshellarg() in /usr/local/hestia/web/api/webhook/index.php
  ```
* **Consecuencia:** El servidor web retornaba un código de estado HTTP 500 y el comando `v-update-web-domain-git` nunca llegaba a ejecutarse ante un `git push`.

---

### Causa 3: Pérdida del contexto de usuario y redirección a `/list/web/`

* **Archivos afectados:** `web/edit/git/index.php`, `web/templates/pages/edit_web_git.php`, `web/templates/pages/list_web.php`, `web/templates/pages/edit_web.php`
* **Detalle:** Cuando un administrador navegaba a la configuración de Git de un dominio perteneciente a otro usuario (o tras completar una acción con redirección `header("Location: ...")`), el parámetro `&user=` no se incluía en la URL.
* **Impacto:** Al recargar la página, `$user` se establecía por defecto en `"admin"`. La verificación de existencia del dominio ejecutaba:
  ```bash
  v-list-web-domain 'admin' 'dominio-de-usuario.com' json
  ```
  Al pertenecer el dominio a otro usuario, el comando devolvía un código de error de objeto inexistente (`E_NOTEXIST`).
* **Consecuencia:** La función `check_return_code_redirect($return_var, $output, "/list/web/")` capturaba el código de error y forzaba la redirección inmediata a `/list/web/`.

---

### Causa 4: Restricciones de seguridad de Git (`safe.directory`) y prompts SSH

* **Archivos afectados:** `bin/v-update-web-domain-git`, `bin/v-add-web-domain-git`
* **Detalle:** Al ejecutar comandos de Git bajo el usuario del sistema mediante `user_exec`, Git podía rechazar la operación si detectaba diferencias de propietario en el directorio de trabajo (`dubious ownership`). Asimismo, conexiones SSH sin bandera de modo no interactivo podían bloquearse esperando confirmación.
* **Consecuencia:** El comando `git fetch` o `git reset` fallaba silenciosamente con código de salida 128.

---

## 4. Soluciones Implementadas

### A. Corrección de la URL de despliegue en JavaScript
En `web/templates/pages/edit_web_git.php`, se eliminó la envoltura `tohtml()` en el bloque de script para asegurar delimitadores de parámetros estándar `&`:
```javascript
// CÓDIGO CORREGIDO:
var baseUrl = "/edit/git/?<?= http_build_query(["domain" => $v_domain, "action" => "pull", "token" => $_SESSION["token"]] + (!empty($user_plain) && isset($_SESSION["user"]) && $user_plain !== $_SESSION["user"] ? ["user" => $user_plain] : (!empty($_GET["user"]) ? ["user" => $_GET["user"]] : []))) ?>";
window.location.href = baseUrl + "&build=" + (runBuild ? "yes" : "no");
```

Adicionalmente, en `web/edit/git/index.php` se implementó soporte de compatibilidad ante parámetros prefijados con `amp;`:
```php
$action = $_GET["action"] ?? ($_GET["amp;action"] ?? "");
```

---

### B. Inicialización de dependencias y soporte de firmas en Webhooks
En `web/api/webhook/index.php`, se incorporó la carga segura del autoload de Composer y se amplió la validación del secreto para soportar el parámetro en URL y las cabeceras estándar de GitHub (`X-Hub-Signature-256`) y GitLab (`X-Gitlab-Token`):
```php
try {
	require_once dirname(__DIR__, 2) . "/inc/vendor/autoload.php";
} catch (Throwable $ex) {
	http_response_code(500);
	echo json_encode(["status" => "error", "message" => "Autoload error: " . $ex->getMessage()]);
	exit(1);
}

use function Hestiacp\quoteshellarg\quoteshellarg;

// Validación de secreto (URL, HMAC SHA256 o Token de GitLab)
$raw_secret_valid = false;
if (!empty($secret) && hash_equals($git_data["WEBHOOK_SECRET"], $secret)) {
	$raw_secret_valid = true;
} elseif (!empty($_SERVER["HTTP_X_HUB_SIGNATURE_256"])) {
	$expected_sig = "sha256=" . hash_hmac("sha256", $payload_raw, $git_data["WEBHOOK_SECRET"]);
	if (hash_equals($expected_sig, $_SERVER["HTTP_X_HUB_SIGNATURE_256"])) {
		$raw_secret_valid = true;
	}
} elseif (!empty($_SERVER["HTTP_X_GITLAB_TOKEN"])) {
	if (hash_equals($git_data["WEBHOOK_SECRET"], $_SERVER["HTTP_X_GITLAB_TOKEN"])) {
		$raw_secret_valid = true;
	}
}
```

---

### C. Resolución automática de propietario y conservación de estado
En `web/edit/git/index.php`, se integró la resolución automática del propietario mediante `v-search-domain-owner` cuando un administrador accede sin indicar el parámetro `user`:
```php
if ($_SESSION["userContext"] === "admin") {
	if (!empty($_GET["user"])) {
		$user = quoteshellarg($_GET["user"]);
		$user_plain = htmlentities($_GET["user"]);
	} else if (!empty($v_domain)) {
		exec(
			HESTIA_CMD . "v-search-domain-owner " . quoteshellarg($v_domain) . " web",
			$owner_out,
			$owner_status,
		);
		if ($owner_status === 0 && !empty($owner_out[0])) {
			$user_plain = trim($owner_out[0]);
			$user = quoteshellarg($user_plain);
		}
		unset($owner_out);
	}
}

$user_param = (!empty($_GET["user"])) ? "&user=" . urlencode($_GET["user"]) : ((!empty($user_plain) && isset($_SESSION["user"]) && $user_plain !== $_SESSION["user"]) ? "&user=" . urlencode($user_plain) : "");
```
Se actualizó cada redirección `header("Location: ...")` para incluir `$user_param`.

---

### D. Parámetros de seguridad y robustez en scripts Bash
En `bin/v-update-web-domain-git` y `bin/v-add-web-domain-git`:
1. Se agregó `-c safe.directory=*` a todas las invocaciones de `git`.
2. Se incluyó `-o BatchMode=yes` en `GIT_SSH_COMMAND` para evitar bloqueos interactivos.
3. Se garantizó la creación del directorio temporal `$HOMEDIR/$user/tmp` con permisos `770` antes de generar los scripts de compilación.

---

## 5. Procedimiento de Compilación y Verificación en Servidor

Para compilar e instalar la versión corregida en el servidor:

```bash
# 1. Limpiar directorios previos y clonar el repositorio actualizado
rm -rf /root/hestiacp /root/hestiacp-src
git clone https://github.com/pirulug/hestiacp.git /root/hestiacp
chmod +x /root/hestiacp/src/*.sh /root/hestiacp/bin/* /root/hestiacp/install/*.sh

# 2. Compilar e instalar los paquetes de HestiaCP
export BUILD_DIR=/root/hestiacp-src
cd /root/hestiacp/src
./hst_autocompile.sh --hestia --install '~localsrc'

# 3. Reiniciar el servicio del panel
systemctl restart hestia
```
