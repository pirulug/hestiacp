# Memoria del Proyecto HestiaCP (Fork PiruHost)

Este archivo almacena el contexto persistente, decisiones tecnicas, soluciones a problemas conocidos y directrices clave para agentes de IA y desarrolladores en este repositorio.

## Informacion del Proyecto

* Nombre: Hestia Control Panel (Fork PiruHost)
* Repositorio: pirulug/hestiacp
* Repositorio base (upstream): hestiacp/hestiacp
* Objetivo: Panel de control web optimizado para compilacion autonoma local, implementacion sin binarios externos y despliegues Git con soporte para Node.js / PNPM en entornos VPS (Debian / Ubuntu).

## Preferencias del Usuario y Reglas Globales

* Strings y comillas: Usar comillas dobles (`"`) como primera opcion. Comillas simples (`'`) unicamente cuando sea estrictamente necesario. Mantener coherencia en todo el proyecto.
* Formato Markdown: No utilizar `---` como separador horizontal en archivos `.md`. Evitar separadores innecesarios.
* Emojis: Prohibidos en codigo, mensajes de commit, documentacion, comentarios o interfaces, salvo peticion explicita del usuario.
* Estilo general: Mantener un enfoque profesional, limpio, simple y directo.

## Estructura de Ramas

* `main`: Rama de trabajo activa con todas las funcionalidades del fork (Git deploy, PNPM, personalizaciones esteticas, documentacion en `guias/`).
* `upstream-latest`: Sigue directamente la rama de desarrollo oficial (`upstream/main`).
* `upstream-1.10.5`: Rama estatica congelada en la etiqueta oficial 1.10.5 de HestiaCP para auditoria y comparaciones de codigo limpio.

## Base de Conocimiento y Problemas Resueltos

### Error de deploy Git con entidades HTML en JavaScript
* Sintoma: El despliegue manual o con pipeline no ejecutaba las acciones al hacer clic en los botones del modal.
* Causa: En `web/templates/pages/edit_web_git.php`, se utilizaba `tohtml(http_build_query(...))` dentro de una cadena JavaScript, transformando los caracteres `&` en `&amp;`. El navegador enviaba los parametros como `amp;action=pull`, lo cual impedia a PHP procesar `$_GET["action"]`.
* Solucion: Construir la cadena de consulta sin `tohtml()` cuando el destino es una variable literal de JavaScript.

### Error HTTP 500 en endpoint de Webhook Git
* Sintoma: Peticiones POST enviadas por GitHub o GitLab al webhook fallaban con codigo 500.
* Causa: En `web/api/webhook/index.php` se utilizaba la funcion `Hestiacp\quoteshellarg\quoteshellarg()` sin incluir el autoload de Composer.
* Solucion: Asegurar la inclusion obligatoria de `vendor/autoload.php` antes de usar funciones del namespace `Hestiacp`.

### Redireccion inesperada a /list/web/ en dominios de otros usuarios
* Sintoma: Al guardar configuraciones o realizar acciones en dominios pertenecientes a un usuario distinto de `admin`, el panel redirigia al listado principal.
* Causa: La URL de redireccion o peticion no incluia el parametro `&user=`, haciendo que el backend tomara `admin` como usuario por defecto y fallara al validar la pertenencia del dominio.
* Solucion: Conservar e incluir el parametro `&user=` en todos los formularios, enlaces y cabeceras de redireccion `header("Location: ...")`.

### Integracion de HestiaCP 1.10.5 Service Release
* Version base actualizada de 1.10.4 a 1.10.5.
* Parches de seguridad incorporados: verificacion de rol/sesion de usuario, correccion en `v-unsuspend-user`, refuerzo de modo lectura/demo y proteccion contra inyeccion en lectura de configuraciones (`HESTIA_RESERVED_CONF_KEYS`).
* Mejoras funcionales integradas: actualizacion de dependencias (PHP 8.5.10, OpenSSL 3.5.8, PCRE 10.48, Roundcube 1.7.4, File Manager 7.16.2), fin de soporte para Debian 11, mejoras en SSO phpMyAdmin para bases remotas (`%`) y soporte para Ubuntu 26.04 y Debian 13.
* Preservacion del fork: se mantuvieron intactas las integraciones Git con pipeline de construccion para dominios web, herramientas CLI para PNPM, plantillas de inicio de sesion personalizadas y documentacion interna en `guias/`.

## Comandos y Flujos Frecuentes

### Compilacion local autonoma
```bash
cd /tmp/hestiacp/src
./hst_autocompile.sh --all --noinstall --keepbuild "~localsrc"
```

### Actualizacion en caliente del panel en servidor
```bash
export BUILD_DIR=/tmp/hestiacp-src
cd /tmp/hestiacp/src
./hst_autocompile.sh --hestia --install "~localsrc"
systemctl restart hestia
```

### Permisos de ejecucion requeridos
Los scripts ejecutables deben tener permisos adecuados:
```bash
chmod +x /usr/local/hestia/bin/*
chmod +x /usr/local/hestia/src/*.sh
chmod +x /usr/local/hestia/install/*.sh
```

## Documentacion de Referencia Interna

* `guias/INSTALL.md`: Procedimiento de instalacion autonoma en VPS limpio.
* `guias/COMMANDS.md`: Comandos diarios de administracion, compilacion y mantenimiento.
* `guias/GUIA_SINCRONIZACION.md`: Flujo seguro para fusionar cambios desde `upstream`.
* `guias/ERROR.md`: Historial exhaustivo de causas raiz y correcciones tecnicas.
* `guias/OPCIONES.md`: Configuracion de File Manager, SSO y extensiones.
* `guias/RAMAS.md`: Referencia completa de navegacion entre ramas Git.
