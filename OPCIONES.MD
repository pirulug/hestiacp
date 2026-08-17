# ACTIVAR FILE MANAGER

```bash
# 1. Instalar dependencias del Administrador de Archivos (FileGator)
/usr/local/hestia/bin/v-add-sys-filemanager

# 2. Habilitar la variable FILE_MANAGER en la configuracion de Hestia
/usr/local/hestia/bin/v-change-sys-config-value "FILE_MANAGER" "true"

# 3. Asignar los permisos correspondientes
chown -R root:root /usr/local/hestia/web/fm
chown -R hestiaweb:hestiaweb /usr/local/hestia/web/fm/private
chown -R hestiaweb:hestiaweb /usr/local/hestia/web/fm/repository
chmod -R 775 /usr/local/hestia/web/fm/private
chmod -R 775 /usr/local/hestia/web/fm/repository

# 4. Reiniciar el servicio web de Hestia
systemctl restart hestia
```

---

# ACTIVAR LOGIN AUTOMATICO (SSO) EN PHPMYADMIN

```bash
# 1. Habilitar el sistema API en Hestia (requerido para SSO)
/usr/local/hestia/bin/v-change-sys-api enable api

# 2. Instalar y activar Single Sign-On (SSO) para phpMyAdmin
/usr/local/hestia/bin/v-add-sys-pma-sso

# 3. (Opcional) Restringir phpMyAdmin para permitir acceso unicamente via SSO desde el panel
/usr/local/hestia/bin/v-add-sys-pma-restrict

# 4. Reiniciar el servicio web de Hestia
systemctl restart hestia
```