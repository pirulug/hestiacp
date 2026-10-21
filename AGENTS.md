# Instrucciones para Agentes de Inteligencia Artificial

Este documento establece las directrices operativas, estándares de código, arquitectura y restricciones para cualquier agente de IA que trabaje en el repositorio de HestiaCP (fork mantenido por PiruHost / pirulug).

## Proposito del Repositorio

Este repositorio es un fork personalizado de Hestia Control Panel optimizado para:
* Compilacion autonoma y limpia desde codigo fuente sin descargas externas obligatorias.
* Despliegues independientes en servidores VPS basados en Debian y Ubuntu.
* Integracion nativa de Git para dominios web (repositorio remoto, ramas, webhooks y pipeline de construccion con soporte para PNPM, Node.js y Composer).
* Herramientas de mantenimiento rapido y personalizacion de marca (whitelabel).

## Estructura del Proyecto

* `bin/`: Comandos ejecutables de backend de HestiaCP (`v-*`). Todos los scripts en este directorio deben tener permisos de ejecucion (`chmod +x`).
* `func/`: Librerias y funciones compartidas en Bash (`main.sh`, `syshealth.sh`, etc.).
* `install/`: Scripts de instalacion para distribuciones soportadas (`hst-install-debian.sh`, `hst-install-ubuntu.sh`) y plantillas base.
* `src/`: Codigo fuente y scripts de compilacion (`hst_autocompile.sh`), construccion de paquetes `.deb` para `hestia`, `hestia-nginx` y `hestia-php`.
* `web/`: Interfaz de usuario web desarrollada en PHP.
  * `web/templates/`: Vistas y plantillas HTML/PHP para cada seccion.
  * `web/edit/`, `web/add/`, `web/list/`, `web/delete/`: Controladores de acciones web.
  * `web/api/`: Endpoints de API y receptores de webhooks (como `web/api/webhook/index.php`).
* `guias/`: Documentacion tecnica detallada, comandos y resolucion de problemas del fork.

## Reglas Obligatorias de Codigo y Estilo

Todo agente debe cumplir estrictamente las siguientes reglas:

1. Uso de comillas:
   * Usar primero comillas dobles (`"`) para cadenas de texto (strings).
   * Usar comillas simples (`'`) unicamente cuando sea estrictamente necesario.
   * Mantener consistencia en el uso de comillas en todo el proyecto.
2. Restricciones en documentacion Markdown (`.md`):
   * No utilizar `---` como separador horizontal bajo ninguna circunstancia.
   * Evitar separadores innecesarios.
   * Mantener estructura limpia basada en encabezados (`#`, `##`, `###`), listas y bloques de codigo.
3. Prohibicion absoluta de emojis:
   * No utilizar emojis en codigo, mensajes de commit, documentacion, comentarios de codigo ni interfaces graficas.
   * Solo se permite el uso de emojis si el usuario lo solicita de forma explicita en su peticion.
4. Tono y calidad:
   * Mantener el codigo y la documentacion limpios, simples y profesionales.

## Reglas Operativas de Git y Ramas

El repositorio cuenta con una organizacion de ramas definida:

* `main`: Rama principal del fork personal (`pirulug/hestiacp`). Contiene todas las funcionalidades propias desarrolladas (soporte Git, PNPM, personalizaciones visuales y carpeta `guias/`). Todos los cambios y desarrollos deben confirmarse aqui salvo instruccion contraria.
* `upstream-latest`: Rama sincronizada con el repositorio oficial (`hestiacp/hestiacp`, rama `main`). Utilizada como referencia de novedades y base de mezcla.
* `upstream-1.10.5`: Rama congelada en la etiqueta oficial estable 1.10.5 para comparaciones y auditoria.

Pautas para agentes en tareas de Git:
* Nunca modificar directamente ramas `upstream-*` con codigo propio.
* Para sincronizaciones con el repositorio oficial, consultar previamente `guias/GUIA_SINCRONIZACION.md`.
* Los archivos dentro de `guias/` pertenecen exclusivamente al fork personal.

## Reglas de Arquitectura y Lecciones Aprendidas

Al modificar o crear caracteristicas en el panel, respetar los siguientes precedentes tecnicos:

1. Parametros URL y JavaScript en plantillas PHP:
   * Nunca envolver llamadas a `http_build_query()` con la funcion `tohtml()` dentro de bloques `<script>` en JavaScript. La conversion de `&` a `&amp;` impide que PHP reconozca los parametros `$_GET`.
2. Dependencias de Composer en APIs y Webhooks:
   * Todo script en `web/api/` que utilice funciones de librerias de terceros (como `quoteshellarg`) debe incluir obligatoriamente el cargador automatico `vendor/autoload.php` antes de su invocacion.
3. Preservacion de contexto de usuario:
   * En formularios, acciones y redirecciones que gestionen recursos de un usuario determinado, propagar siempre el parametro `&user=` para evitar que la sesion asuma `admin` por defecto y redirija erroneamente al listado principal.
4. Seguridad en ejecucion de comandos de sistema:
   * Utilizar siempre envolturas seguras para argumentos que interactuen con el shell (`quoteshellarg()`).
   * Validar permisos de administrador antes de ejecutar binarios con privilegios de root (`v-*`).

## Verificacion de Cambios

Antes de dar por concluida una intervencion en el codigo:
* Validar sintaxis en scripts Bash (`bash -n ruta/al/script.sh`).
* Validar sintaxis en archivos PHP (`php -l ruta/al/archivo.php`).
* Confirmar que los permisos de ejecucion se preserven en los archivos de `bin/` y scripts en `src/`.
