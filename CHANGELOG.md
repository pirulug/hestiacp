# Changelog

All notable changes to this project will be documented in this file.

## [1.10.7] - Service Release

### Security

- Harden `v-quick-install` and fix `v-quick-install-app` option parsing and PHP version check #5946 #5747 #5748, (GHSA-chw8-phjg-wmq7)
- Anchor archive extension checks in `v-extract-fs-archive` #5819
- Delete access keys when deleting a user #5941
- Add NetAPI honeypot attackers to the malicious IP blacklist #5974
- Fix symlink following by root in user home directories: verify directories component by component and do user-owned file operations with the user's privileges in mail/web domain scripts, rebuild and domain moves, SSH key scripts, user backup/restore and restic restores (restic now restores into a root-only staging directory) (GHSA-hc82-cqjf-hcpg, GHSA-wp7p-qfxc-cg88 and GHSA-pfj7-fhx8-q4qx )
- Fail `v-add-mail-account` when the mailbox can't be created
- Harden API authentication: validate API key names and refuse symlinked or out-of-store key files in `v-check-api-key`, fail closed on empty client IPs, trim and strictly compare the allowed IP list, and reject non-string request fields
- Harden handling of password and hash files: check ownership, refuse symlinks and path traversal, fail closed on invalid files, remove them after reading, create API temp files with `tmpfile()`, clean up SMTP relay password files and pass the backup password through a file instead of argv (GHSA-vxpc-8vm2-8rv3)
- Block mutating GET requests in read-only sessions and exit after redirecting logged-in users from password reset
- Protect `ROOT_USER` SSH keys like access keys and enforce the protected-admin policy in the web terminal (GHSA-p4vq-6v6h-cc74)

### Changes

- Add `.xz`/`.txz` archive support to `v-extract-fs-archive` and fix extraction of single-file archives with URL query strings #5852
- Add a visibility toggle button to password inputs #5742 #5959
- Support the `rounds=` parameter in sha-512 shadow hashes #5955 #5956
- Update Roundcube to 1.7.6 and install `symfony/polyfill-php84` before the upgrade #5972
- Update SnappyMail to 3.0.1 #5960
- Update SnappyMail plugins during upgrade #5961
- Update MariaDB target version to 12.3 #5536
- Update Zlib to 1.4
- Update `cloudflare-ip-validator` to 1.0.1 #5947
- Raise `pm.max_children` of the panel PHP-FPM pool from 4 to 8
- Prevent CNAME DNS records from coexisting with other records for the same name
- Include the installed `hestia-web-terminal` package in system update checks and automatic updates
- Add `actionflush` to the Fail2Ban action; the 1.10.7 upgrade updates the action on existing installations #5022
- Update all non-major dependencies and Biome to 2.6.6 #5945 #5944
- Add a manual apt release workflow, build all Hestia debs locally for BATS and drop bullseye from the release matrix #5902 #5907

### Bug fixes

- Fix Let's Encrypt dns-01 challenge status polling #5842 #5854
- Fix the broken redirect URL when impersonating a user from search results #5957
- Fix search returning no results while impersonating a user #5958
- Skip `v-add-mail-domain-reject` when `v-add-mail-domain` fails #5037 #5950
- Pass the domain resolve URL to all ClassicPress and Dolibarr setup requests #5656 #5951
- Fix apps and options in `v-quick-install-app` #5943
- Keep signed zone files when rebuilding DNSSEC domains #5939
- Fix `v-delete-user-backup-exclusion` #5928
- Format the target domain in `v-change-web-domain-name` #4958
- Include the target domain's private directory in `open_basedir` for cross-domain docroots
- Fix web domain unsuspend target and allow suspending mail/db objects of suspended users
- Fix incremental backup file restoration from the web UI and CLI
- Include MySQL events in database dumps and append wildcard user grants
- Refresh the PHP-FPM pool path and previous version on backend template change
- Normalize `record` and `rtype` arguments in `v-change-dns-record`
- Fix the Backblaze B2 CLI download on arm64 #5905
- Fix the fail2ban multiport chain deletion #5022
- Fix the Roundcube upgrade hanging by exporting `COMPOSER_ALLOW_SUPERUSER` earlier
- Remove `policy-rc.d` on installer exit
- Fail loudly when the installer's backend package install fails

## [1.10.6] - Service Release

### Security

- Harden restore scripts: refuse symlinked user directories, stage restores in a root-owned directory, quote variables and use `chown --no-dereference` #5796
- Fix path traversal in the File Manager `escapeDots` filter (File Manager 7.16.4) #5732
- Fix Hestia root user authentication check in the File Manager #5739
- Add read-only/demo mode check for POST request handling #5756
- Improve checks for special characters in custom document roots and escape them before template replacement #5759
- Fix `mkpasswd` argument parsing so passwords or salts starting with `-` are not treated as options #5764
- Escape array keys in the debug panel #5750
- Fix `Header Location` redirects #5737
- Fix and harden Exim HELO checks: require HELO for all clients, reject bare/IP-like HELOs and our own IP, log FCrDNS and HELO/PTR mismatches #5862
- Quote domain config path in `is_dns_domain_new` and `is_mail_domain_new` #5853
- Fix `public_html` directory permissions when adding a web domain and use null-delimited paths in `find` #5730
- Ensure user tmp directory ownership and permissions during `rebuild_user_conf` #5849

### Changes

- Add ECDSA key algorithm support for Let's Encrypt certificates #5629
- Add support for a custom DKIM selector #5760
- Add `v-delete-user-wp-cli` and let `v-add-user-wp-cli` manage `.bash_aliases` without duplicate entries #5786 #5787 #5788
- Add `E_BACKUP` error code for backup operations #5785
- Use the Sury repository for PHP on all Ubuntu versions instead of the PPA, existing installs are migrated #5825
- Let unattended-upgrades patch hosted PHP, nginx, Node.js, MariaDB, PostgreSQL and Apache (Ondřej Surý PPA) packages #5876 #5877 #5878 #5879 #5880 #5881 #5882
- Use the `crontab` command to install user cron jobs #5805
- Optimize JSON listing with bulk validated object parsing #5726 #5890
- Support bond interfaces in hestia-nginx port detection #5752
- Autofocus the web terminal on page load #5893
- Remove the admin account warning from the add web, mail, database and DNS pages, as the admin account no longer has sudo privileges #5896
- Set default `LANG` to `C.UTF-8` for web terminal sessions #5770
- Increase length of the Record column in the DNS records list #5771
- Remove `txt` from default `PROXY_EXT` to avoid `expires max` on `robots.txt` #5850
- Skip release branch version check when installing alpha/beta packages #5851
- Add `rsync` dependency and pre-check for Roundcube upgrades #5824
- Improve build speed and pass `--pkgrev` through in `hst_autocompile` #5891
- Bump hestia-php to 8.5.12 #5738 #5816
- Bump hestia-nginx to 1.30.6 #5723 #5797
- Bump OpenSSL to 3.5.9 and PCRE to 10.49 #5751
- Bump File Manager to 7.16.9 #5717 #5732 #5741 #5827 #5898
- Bump Quick Install app versions: Castopod, Grav, Joomla, MediaWiki, PrestaShop #5899
- Update Roundcube to 1.7.5 #5810
- Update SnappyMail to 3.0.0 #5901
- Run BATS suite on nginx-only installs and add upgrade migration tests #5865 #5861
- Update dependencies #5734 #5735 #5765 #5766 #5789 #5790 #5791 #5807 #5832 #5838 #5840 #5847 #5884 #5894 #5895

### Fixes

- Fix IDN handling in Let's Encrypt SSL certificate generation #5814
- Fix mail certificate renewal check so expired SMTP/IMAP certificates are renewed #5829
- Fix missing newline before SAN section in OpenSSL config #5831
- Set `root:mail` ownership on Hestia host certificates to avoid AppArmor conflict with ProFTPD #5778
- Fix ProFTPD not loading configuration files from `conf.d` #5859
- Fix typo in Dovecot SSL certificate configuration #5844
- Fix incremental restore mixing up database/mail/file restore types #5740
- Fix bug with the B2 CLI tool #5728
- Fix tar path handling #5754
- Fix syntax and error code typos in backup scripts #5782 #5783 #5784
- Fix suspend/unsuspend of DNS records on remote DNS hosts #5821 #5845 #5846
- Fix Bind `default-zones` include removal in Debian 13 / Ubuntu 26.04 migration scripts #5836
- Fix PHP version installation failure handling in `v-add-web-php` #5823
- Keep working PHP dependencies when `v-add-sys-dependencies` fails #5871
- Fix Composer 2.11 download failures with libcurl on Ubuntu 22.04 #5888
- Fix `v-add-sys-roundcube` for rsync 3.5.x #5811
- Fix `v-add-sys-web-terminal` installing the OS Node.js package instead of v24 #5763
- Fix `v-change-web-domain-ftp-path` path handling #5777
- Fix SSH key deletion and listing #5743
- Fix proxy extension validation #5768
- Fix trailing whitespace in domain IP in web.conf and `is_ip_valid`, trimmed on upgrade #5858 #5861
- Fix nginx 500 in phpMyAdmin/phpPgAdmin includes by using named captures #5863
- Fix phpMyAdmin config loop adding a phantom server for non-server `conf.d` files #5795
- Speed up `cleanup_key_permissions` for keys with long permission lists #5792
- Fix doubled user prefix in Quick Install database name and show CLI output on failure #5875
- Fix WordPress Quick Install failing for locales without a localized release zip by falling back to `en_US` and installing the language pack, and fix the `zh_CN` locale identifier #5900
- Fix trailing single quote being stripped when editing cron commands #5892
- Fix undefined `$panel` in `policies.php` #5835
- Fix File Manager showing the session-timeout redirect as file content #5636
- Fix installer race with `apt-get upgrade` and prevent `--with-debs` from overwriting local packages
- Fix mobile navbar colors and dropdown in dark mode #5775

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
