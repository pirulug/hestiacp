# Changelog

All notable changes to this project will be documented in this file.

## [0.0.1] - Initial Release

### Added
- **Integración Git en Dominios Web:**
  - Soporte completo para conectar repositorios Git a dominios web con ramas personalizadas.
  - Ejecución de scripts de despliegue automatizado (`post_deploy`) tras cada pull o actualización.
  - Interfaz gráfica en el panel (`edit_web_git.php`) con iconos y botones dedicados de GitHub.
- **Gestor de Paquetes PNPM y Node.js:**
  - Utilidades CLI para instalación y aprovisionamiento de PNPM a nivel de sistema (`v-add-sys-pnpm`) y por usuario (`v-add-user-pnpm`).
  - Habilitación de scripts de compilación de paquetes nativos y `esbuild` en proyectos Node.js administrados con PNPM.
- **Actualizador del Panel vía Git (`v-update-sys-hestia-git`):**
  - Script CLI para compilar, empaquetar e instalar actualizaciones de HestiaCP en caliente directamente desde ramas y repositorios de GitHub.
- **Autenticación y Base de Datos:**
  - Soporte para Single Sign-On (SSO) automático en phpMyAdmin (`v-add-sys-pma-sso`).
  - Creación de usuarios temporales de base de datos (`v-add-pma-user-temp`) y restricción de acceso directo a phpMyAdmin.
- **Interfaz Web y Marca (Whitelabel):**
  - Nuevas plantillas personalizadas de inicio de sesión (`login_1.php`, `login_2.php`, `login_a.php`) y recuperación de cuenta.
  - Personalización de footer, logos SVG y configuración de marca (Whitelabel / PiruHost).
- **Documentación y Guías:**
  - Directorio centralizado `guias/` con manuales de instalación limpia, comandos frecuentes, sincronización de fork y opciones avanzadas.

### Fixed
- Corrección de finales de línea CRLF en archivos de configuración del servidor.
- Codificación en Base64 de las instrucciones `post_deploy` dentro de `git.conf` para evitar errores de sintaxis en scripts multilínea.
- Corrección de presets de lockfile en PNPM y aprobación de builds de dependencias nativas.
- Ajuste de permisos y configuración de directorios de trabajo para el Gestor de Archivos (FileGator).
