# Changelog

All notable changes to this project will be documented in this file.

## [1.10.5] - Service Release

### Security

- Verify user role/session has not been tampered with and fix suspended users not always having their login disabled #5706
- Fix `v-unsuspend-user` using the wrong `POLICY_USER_VIEW_SUSPENDED` comparison, so unsuspending a user did not always unlock their account #5711
- Improve read-only/demo mode enforcement, closing bypasses via direct URLs (e.g. `/add/cron/`), open redirects, and the admin login-as flow #5688
- Improve conf parsing and reserved-key handling to prevent injection via crafted config values #5698

### Changes

- Remove support for Debian 11 (Bullseye) #5654
- Bump Quick Install app versions: Dolibarr, Drupal, Grav, Joomla, OpenCart, PrestaShop #5700
- Bump hestia-php to 8.5.10 #5678
- Bump OpenSSL to 3.5.8 #5658
- Update PCRE to 10.48 #5673
- Update Roundcube to 1.7.4 #5690
- Bump File Manager to 7.16.2 #5695
- Enhance backup detail pages by adding icons to item types #5679
- Update dependencies #5666 #5693 #5694
- Add missing tests for suspending users #5710

### Fixes

- Fix inverted database limit check condition that could block users from creating databases within their quota #5640
- Fix phpMyAdmin SSO for remote databases and redirects to the wrong server #5454
- Rename phpMyAdmin tempdir configuration to load it last #5655
- Fix restic full restore to restore all objects and correct cron binary name #5709
- Fix backup host setup passing `$raw_password` instead of `$password` for Backblaze B2 credentials #5697
- Fix issues when running behind a NAT / reverse proxy setup #5689
- Implement locking mechanism for Fail2Ban actions and firewall state, and fix raw IP CIDR handling #5699
- Fix ProFTPd on Debian 13 and Ubuntu 26.04 #5677
- Omit CN for domains longer than 64 characters when generating SSL certificates #5676
- Fix Global SMTP relay password validation #5665
- Validate empty database alias #5663
- Fix Apache2 logrotate when webstats fails because the domain is suspended #5685
- Fix "Rejected because" DNSBL message for existing Exim installations #5683
- Fix action button icons overlapping text on mobile view #5680

## [1.10.4] - Service Release

### Security
- Hide accidental password leaking via auth.log / journalctl for sudo commands #5648
- Add owner verification to v-schedule-user-restore #5645

### Changes
- Disable imap and smtp debug in Roundcube by default to prevent disk space issues #5642
- Add check_hestia_demo_mode to all v-list-*-ssl scripts #5639
- Update ubuntu Docker tag to v26 #5438
- Update dependency @xterm/xterm to v6 #5627

### Fixes
- Fix database temp user creation failing on MySQL HeatWave due to Medium Password Policy #5453
- Fix bug in SFTP backup rotation causing backup storage to not properly rotate #5646
- Fix back link and Access Keys button pointing to wrong user #5635 #5638
- Fix migration scripts not adding the imap_sieve plugin to the IMAP config #5637
- Fix typo in serial causing an inconsistent default DNS serial value #5632
- Improve File Manager installation validation and error logging #5634

## [1.10.3] - Service Release

### Changes
- Update locales.

### Fixes
- Adjust text wrapping behavior in `u-text-break` utility #5621
- Fix missing symfony/mime dependency in FileManager and reinstall on upgrade #5616
- Fix 1.10.3.sh script to reinstall FileManager #5622
- Reorder firewall rules after rule deleting preventing a bug with moving up / down causing duplicate lines #5623

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
