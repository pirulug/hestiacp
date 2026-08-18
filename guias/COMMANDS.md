# Comandos de Instalación, Configuración y Actualización de HestiaCP (PiruHost)

Este documento contiene todos los comandos necesarios para limpiar almacenamiento en tu VPS, compilar desde tu repositorio, activar el Gestor de Archivos y mantener actualizado el panel con permisos de ejecución asignados (`chmod +x`).

---

## 1. Limpieza de Espacio en Disco

El error `No space left on device` ocurre porque la carpeta `/tmp` (o la partición raíz de tu VPS) se llena con paquetes temporales y copias de seguridad de instalaciones previas. 

Ejecuta como `root`:

```bash
# 1. Eliminar archivos temporales y copias de seguridad antiguas
rm -rf /tmp/* /tmp/.* /root/hst_install_backups/* /root/hst_backups/* 2>/dev/null

# 2. Limpiar la caché del gestor de paquetes APT
apt-get clean
apt-get autoremove -y
rm -rf /var/cache/apt/archives/*.deb

# 3. Verificar el espacio libre en disco (debe haber al menos 2-3 GB libres en /)
df -h /
```

---

## 2. Instalación de Herramientas de Compilación

```bash
# Instalar Node.js 24, npm, git y utilidades de compilación
curl -fsSL https://deb.nodesource.com/setup_24.x | bash -
apt-get update && apt-get install -y nodejs npm git build-essential
```

---

## 3. Clonar el Repositorio y Asignar Permisos (`chmod +x`)

Clonamos en `/root/hestiacp` (en lugar de `/tmp`) para asegurar que haya suficiente espacio en disco:

```bash
# 1. Eliminar versiones anteriores en /root
rm -rf /root/hestiacp /root/hestiacp-src

# 2. Clonar tu repositorio
git clone https://github.com/pirulug/hestiacp.git /root/hestiacp

# 3. Asignar permisos de ejecución (chmod +x) a todos los scripts
chmod +x /root/hestiacp/src/*.sh
chmod +x /root/hestiacp/bin/*
chmod +x /root/hestiacp/install/*.sh
```

---

## 4. Compilar e Instalar el Panel

Compilamos usando `/root/hestiacp-src` como directorio de construcción:

```bash
# 1. Definir directorio de compilación con espacio suficiente
export BUILD_DIR=/root/hestiacp-src

# 2. Entrar a la carpeta de código fuente
cd /root/hestiacp/src

# 3. Compilar e instalar el paquete hestia local
./hst_autocompile.sh --hestia --install '~localsrc'
```

---

## 5. Instalar y Configurar el Gestor de Archivos (File Manager)

Para instalar el File Manager, habilitarlo en la configuración y asignarle permisos correctos para evitar el error 500:

```bash
# 1. Instalar y generar File Manager (FileGator)
/usr/local/hestia/bin/v-add-sys-filemanager

# 2. Habilitar la variable en la configuración del sistema
/usr/local/hestia/bin/v-change-sys-config-value 'FILE_MANAGER' 'true'

# 3. Asignar propietarios y permisos
chown -R root:root /usr/local/hestia/web/fm
chown -R hestiaweb:hestiaweb /usr/local/hestia/web/fm/private
chown -R hestiaweb:hestiaweb /usr/local/hestia/web/fm/repository
chmod -R 775 /usr/local/hestia/web/fm/private
chmod -R 775 /usr/local/hestia/web/fm/repository

# 4. Reiniciar el servicio del panel
systemctl restart hestia
```

> **Importante para que aparezca el icono en la barra superior:** Debes **cerrar sesión (Log out)** en el panel web y volver a **iniciar sesión (Log in)** con el usuario `admin`, ya que los permisos de visualización del File Manager se cargan al iniciar la sesión del usuario.

---

## 6. Actualización desde Repositorio de GitHub a un Panel Hestia ya Instalado

Para desplegar nuevas funciones, cambios en plantillas o logos que subas a tu repositorio de GitHub `https://github.com/pirulug/hestiacp`:

### Método A: Compilación en Caliente desde el Clon Local (Recomendado)
```bash
# 1. Actualizar el código fuente local desde GitHub
cd /root/hestiacp
git pull origin main

# 2. Asignar permisos de ejecución por si hay nuevos scripts
chmod +x /root/hestiacp/src/*.sh /root/hestiacp/bin/* /root/hestiacp/install/*.sh

# 3. Compilar e instalar el paquete hestia
export BUILD_DIR=/root/hestiacp-src
cd /root/hestiacp/src
./hst_autocompile.sh --hestia --install '~localsrc'

# 4. Reiniciar el panel
systemctl restart hestia
```

### Método B: Actualización Directa vía Script
```bash
/usr/local/hestia/bin/v-update-sys-hestia-git pirulug main install
systemctl restart hestia
```

---

## 7. Generar Certificado SSL Let's Encrypt para el Hostname y Backend (Puerto 1897)

Para habilitar HTTPS seguro (candado verde) en `https://hcp.piruhost.xyz:1897`:

```bash
# 1. Asegurar que el hostname del sistema esté correctamente asignado
/usr/local/hestia/bin/v-change-sys-hostname hcp.piruhost.xyz

# 2. Generar y asociar automáticamente el certificado Let's Encrypt al backend
/usr/local/hestia/bin/v-add-letsencrypt-host
```

Si requieres forzar la emisión manualmente paso a paso:
```bash
# A. Crear el dominio web del hostname en admin (si no existe)
/usr/local/hestia/bin/v-add-web-domain admin hcp.piruhost.xyz

# B. Solicitar el certificado Let's Encrypt
/usr/local/hestia/bin/v-add-letsencrypt-domain admin hcp.piruhost.xyz '' yes

# C. Aplicar el certificado al panel de Hestia y reiniciar servicios
/usr/local/hestia/bin/v-update-host-certificate admin hcp.piruhost.xyz
systemctl restart hestia
```

> **Requisitos para que Let's Encrypt no falle:**
> - El registro DNS tipo `A` de `hcp.piruhost.xyz` debe apuntar a la IP pública del servidor VPS.
> - Si utilizas Cloudflare, el registro debe estar en modo **DNS Only** (nube gris desactivada), ya que el proxy de Cloudflare no soporta el puerto 1897 e interfiere con la validación de Let's Encrypt.
> - El puerto 80 debe estar abierto y accesible para responder al reto ACME de Let's Encrypt.

---

## 8. Personalizar Nombre de Marca (Whitelabel)

```bash
/usr/local/hestia/bin/v-change-sys-config-value 'APP_NAME' 'PiruHost'
```

---

## 9. Acceso al Panel

- **URL:** `https://hcp.piruhost.xyz:1897`
- **Usuario:** `admin`
- **Contraseña:** Tu contraseña configurada (`L@i76749024`)

> **Importante:** Al ingresar por primera vez, presiona **`Ctrl + F5`** (o `Ctrl + Shift + R`) en tu navegador para forzar la actualización de los logos e imágenes en la caché.
