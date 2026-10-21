# Reglas de Arquitectura y Desarrollo para HestiaCP

## Normas para Scripts y Backend (Bash)

* Ubicacion: Todos los comandos del sistema residen en `bin/` con el prefijo `v-*`. Las funciones compartidas residen en `func/`.
* Permisos: Preservar o asignar siempre permisos de ejecucion (`chmod +x`) a los scripts en `bin/`, `src/` e `install/`.
* Comillas en Bash: Utilizar comillas dobles (`"`) para variables y cadenas, por ejemplo `"$1"`, `"$DOMAIN"`, `"$user"`.
* Validacion: Verificar la sintaxis de cualquier script modificado mediante `bash -n <script>`.

## Normas para Interfaz Web (PHP y JavaScript)

* Carga de dependencias: Cualquier script de API o endpoint en `web/api/` debe invocar `vendor/autoload.php` antes de usar paquetes y namespaces externos como `Hestiacp\quoteshellarg\quoteshellarg()`.
* JavaScript y URLs: No utilizar la funcion `tohtml()` al inyectar URLs construidas con `http_build_query()` dentro de cadenas literales de JavaScript en plantillas PHP, ya que convierte `&` en `&amp;` corrompiendo la peticion HTTP GET.
* Contexto de usuario: Asegurar que el parametro `&user=` se conserve en formularios y redirecciones HTTP para evitar que el sistema revierta por defecto a `admin`.
* Validacion: Validar sintaxis PHP mediante `php -l <archivo.php>` tras cualquier modificacion.
