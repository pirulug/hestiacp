# Comandos de Instalación y Compilación de HestiaCP (PiruHost)

Este documento contiene todos los comandos necesarios para limpiar el almacenamiento de tu VPS, compilar desde tu repositorio y desplegar el panel con permisos de ejecución asignados (`chmod +x`).

---

## 1. Limpieza de Espacio en Disco

El error `No space left on device` ocurre porque la carpeta `/tmp` (o el disco de tu VPS) se llenó con paquetes residuales y copias de seguridad de instalaciones previas. 

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

Clonamos en `/root/hestiacp` (en lugar de `/tmp`) para garantizar que haya suficiente espacio en disco:

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

## 5. Permisos del Gestor de Archivos (File Manager)

Corrige la configuración y permisos para solucionar el error 500:

```bash
# 1. Regenerar configuración
/usr/local/hestia/bin/v-add-sys-filemanager

# 2. Asignar propietarios y permisos
chown -R root:root /usr/local/hestia/web/fm
chown -R hestiaweb:hestiaweb /usr/local/hestia/web/fm/private
chown -R hestiaweb:hestiaweb /usr/local/hestia/web/fm/repository
chmod -R 775 /usr/local/hestia/web/fm/private
chmod -R 775 /usr/local/hestia/web/fm/repository

# 3. Reiniciar el servicio del panel
systemctl restart hestia
```

---

## 6. Personalizar Nombre de Marca (Whitelabel)

```bash
/usr/local/hestia/bin/v-change-sys-config-value 'APP_NAME' 'PiruHost'
```

---

## 7. Acceso al Panel

- **URL:** `https://hcp.piruhost.xyz:1897`
- **Usuario:** `admin`
- **Contraseña:** Tu contraseña configurada (`L@i76749024`)

> **Importante:** Al ingresar por primera vez, presiona **`Ctrl + F5`** (o `Ctrl + Shift + R`) en tu navegador para forzar la actualización de los logos e imágenes en la caché.
