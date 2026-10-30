# Conexión de Ubuntu a una VPN WireGuard

## Objetivo

Configurar el servidor Ubuntu `pirulug` como **cliente WireGuard** para conectarse a una VPN existente. En este ejemplo, el cliente utiliza `10.66.66.2/32`, el servidor remoto escucha en `172.238.218.159:51820` y únicamente se enruta por la VPN la red `10.66.66.0/24`.

> **Seguridad:** las claves privadas y precompartidas de la configuración original fueron compartidas en una conversación. Se recomienda **rotarlas** en el sistema que administra la VPN antes de usarla. Este documento no reproduce esos secretos.

## 1. Instalar WireGuard

```bash
sudo apt update
sudo apt install wireguard -y
```

Comprobar la instalación:

```bash
wg --version
sudo modprobe wireguard
lsmod | grep wireguard
```

El mensaje `wg-quick.target is a disabled or a static unit, not starting it` durante la instalación es normal: no implica un fallo.

## 2. Crear el archivo de configuración

```bash
sudo nano /etc/wireguard/wg0.conf
```

Colocar la configuración del cliente, sustituyendo los marcadores por las claves válidas **actualizadas**:

```ini
[Interface]
PrivateKey = <CLAVE_PRIVADA_NUEVA_DEL_CLIENTE>
Address = 10.66.66.2/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
PublicKey = /J1T8VaU+bsYiB97QLmnLuGjA9lW27rfBsWNcphQ9Vg=
PresharedKey = <CLAVE_PRECOMPARTIDA_NUEVA>
Endpoint = 172.238.218.159:51820
AllowedIPs = 10.66.66.0/24
PersistentKeepalive = 25
```

> Si se rota la clave del servidor, también se debe actualizar `PublicKey`. La clave pública mostrada corresponde a la configuración proporcionada originalmente y no es secreta.

Guardar con `Ctrl + O`, `Enter` y salir con `Ctrl + X`.

## 3. Proteger el archivo

```bash
sudo chown root:root /etc/wireguard/wg0.conf
sudo chmod 600 /etc/wireguard/wg0.conf
```

## 4. Activar la conexión VPN

```bash
sudo wg-quick up wg0
```

Si aparece un error relacionado con `resolvconf`, verificar la disponibilidad de la utilidad DNS requerida por `wg-quick` o ajustar la integración DNS del sistema antes de repetir el comando.

## 5. Comprobar el estado

```bash
sudo wg show
ip addr show wg0
```

Para comprobar que existe comunicación real, revisar que `sudo wg show` muestre un **latest handshake** reciente y contadores de **transfer** recibidos. La mera existencia de la interfaz no garantiza conectividad.

Probar la dirección del servidor VPN **si efectivamente utiliza `10.66.66.1` y permite ICMP**:

```bash
ping -c 4 10.66.66.1
```

Si no responde, comprobar la dirección real del peer, sus reglas de firewall y el estado del handshake.

## 6. Iniciar automáticamente al arrancar

Una vez comprobado que la conexión funciona:

```bash
sudo systemctl enable wg-quick@wg0
```

Consultar el estado:

```bash
sudo systemctl status wg-quick@wg0
```

Para activar o reiniciar usando systemd:

```bash
sudo systemctl start wg-quick@wg0
sudo systemctl restart wg-quick@wg0
```

> No ejecutar `wg-quick up wg0` y después `systemctl start wg-quick@wg0` sin desactivar primero la interfaz: puede producirse un error porque `wg0` ya existe. Para dejar la conexión administrada por systemd, ejecutar `sudo wg-quick down wg0` y luego `sudo systemctl enable --now wg-quick@wg0`.

## 7. Comandos de administración

| Acción | Comando |
|---|---|
| Conectar manualmente | `sudo wg-quick up wg0` |
| Desconectar manualmente | `sudo wg-quick down wg0` |
| Consultar peers y handshake | `sudo wg show` |
| Consultar IP asignada | `ip addr show wg0` |
| Consultar servicio | `sudo systemctl status wg-quick@wg0` |
| Reiniciar servicio | `sudo systemctl restart wg-quick@wg0` |
| Desactivar inicio automático | `sudo systemctl disable wg-quick@wg0` |
| Ver registros | `sudo journalctl -u wg-quick@wg0 -n 50 --no-pager` |

## 8. Entender las rutas de la VPN

```ini
AllowedIPs = 10.66.66.0/24
```

Esta configuración hace que el tráfico hacia `10.66.66.0/24` se enrute por WireGuard; el resto normalmente sigue utilizando la conexión habitual a Internet. No configura un túnel completo (`0.0.0.0/0`).

El parámetro `DNS` puede afectar la resolución de nombres del sistema según la integración de `wg-quick` con el gestor DNS del servidor.

## 9. Solución de problemas

1. **No hay handshake:** revisar la IP pública y el puerto UDP `51820`, el firewall, las claves y la configuración del servidor remoto.
2. **Hay handshake pero no hay acceso a la red:** revisar `AllowedIPs`, las rutas y el firewall del servidor y de los equipos de destino.
3. **La interfaz ya existe:** utilizar `sudo wg-quick down wg0` antes de volver a activarla, o administrarla exclusivamente con systemd.
4. **Problemas con DNS:** revisar el parámetro `DNS` y la disponibilidad de `resolvconf`/integración con el resolutor del sistema.
5. **Acceso SSH remoto:** mantener abierta la sesión SSH actual durante las pruebas y comprobar las rutas antes de cambiar cualquier regla de red.

## 10. Validación final

```bash
wg --version
sudo wg show
ip -brief addr show wg0
ip route get 10.66.66.1
sudo systemctl is-enabled wg-quick@wg0
```

La instalación y activación se consideran correctas cuando la interfaz existe, se observa un handshake reciente y hay comunicación con un destino autorizado dentro de la VPN.
