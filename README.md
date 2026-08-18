# Hestia Control Panel (Fork PiruHost)

Fork personalizado de [Hestia Control Panel](https://www.hestiacp.com/) mantenido por **PiruHost / pirulug**, optimizado para compilación autónoma desde código fuente y despliegues independientes en servidores VPS (Debian / Ubuntu).

---

## Documentación y Guías Rápidas

Toda la documentación personalizada, guías de instalación y comandos de mantenimiento se encuentran organizados en la carpeta [`guias/`](guias/README.md):

| Documento | Descripción |
| :--- | :--- |
| [guias/INSTALL.md](guias/INSTALL.md) | Guía de instalación limpia y 100% autónoma en servidores VPS nuevos compilando paquetes `.deb` locales. |
| [guias/COMMANDS.md](guias/COMMANDS.md) | Comandos frecuentes de actualización, permisos (`chmod +x`), liberación de espacio en disco, SSL Let's Encrypt y Whitelabel. |
| [guias/GUIA_SINCRONIZACION.md](guias/GUIA_SINCRONIZACION.md) | Paso a paso para mantener este fork sincronizado con los últimos cambios del repositorio original (`upstream`). |
| [guias/OPCIONES.md](guias/OPCIONES.md) | Configuración del Gestor de Archivos (File Manager), SSO en phpMyAdmin y soporte Node.js / PNPM. |
| [guias/C2.md](guias/C2.md) | Scripts directos para compilar en caliente y solucionar permisos en servidor. |

---

## Instalación Rápida en VPS Nuevo

Para compilar e instalar este fork directamente desde el código fuente sin descargar binarios externos:

```bash
# 1. Instalar herramientas de compilación
apt-get update && apt-get install -y git build-essential

# 2. Clonar el repositorio
cd /tmp
rm -rf /tmp/hestiacp /tmp/hestiacp-src
git clone https://github.com/pirulug/hestiacp.git /tmp/hestiacp

# 3. Compilar paquetes .deb locales
cd /tmp/hestiacp/src
./hst_autocompile.sh --all --noinstall --keepbuild '~localsrc'

# 4. Ejecutar el instalador usando los paquetes compilados
cd /tmp/hestiacp/install
bash hst-install-debian.sh \
  --port "8083" \
  --lang "es" \
  --hostname "hcp.piruhost.xyz" \
  --username "admin" \
  --with-debs /tmp/hestiacp-src/deb \
  --force
```

---

## Actualización en Servidores Existentes

Para actualizar un servidor ya instalado con los últimos cambios subidos a este repositorio:

```bash
v-update-sys-hestia-git pirulug main install
systemctl restart hestia
```

O compilando manualmente en caliente desde el clon local:

```bash
cd /tmp
rm -rf /tmp/hestiacp /tmp/hestiacp-src
git clone https://github.com/pirulug/hestiacp.git /tmp/hestiacp
chmod +x /tmp/hestiacp/src/*.sh /tmp/hestiacp/bin/* /tmp/hestiacp/install/*.sh
export BUILD_DIR=/tmp/hestiacp-src
cd /tmp/hestiacp/src
./hst_autocompile.sh --hestia --install '~localsrc'
systemctl restart hestia
```

---

## Sincronización con el Repositorio Original (Upstream)

Para integrar las novedades y parches de seguridad de HestiaCP oficial:

```bash
git fetch upstream
git merge upstream/main
git push origin main
```

Para más detalles sobre resolución de conflictos y flujo de trabajo, consulta [guias/GUIA_SINCRONIZACION.md](guias/GUIA_SINCRONIZACION.md).

---

## Características del Panel

- Servidor web: Apache2 / NGINX con PHP-FPM y soporte multi-versión (PHP 5.6 - 8.5).
- Servidor DNS con BIND y soporte DNSSEC / clustering.
- Servidor de correo (Exim, Dovecot, ClamAV, SpamAssassin, Roundcube).
- Bases de datos MariaDB / MySQL y PostgreSQL con phpMyAdmin SSO.
- Gestor de archivos integrado (FileGator) y emulador de terminal web integrado.
- Certificados SSL automáticos gratuitos vía Let's Encrypt.
- Cortafuegos integrado (iptables, fail2ban, ipset).

---

## Licencia y Créditos

Este proyecto es un fork de [HestiaCP](https://github.com/hestiacp/hestiacp), el cual está bajo licencia [GPL v3](LICENSE) y basado originalmente en el proyecto VestaCP.
