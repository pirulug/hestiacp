# Guia de Servicio VPN WireGuard en HestiaCP

Este documento describe la arquitectura, operacion y mantenimiento del servicio nativo de WireGuard VPN implementado en este fork de Hestia Control Panel.

## Descripcion General

El servicio permite configurar el servidor HestiaCP (Debian / Ubuntu VPS) como un nodo central (Hub / Relay) de VPN para interconectar dispositivos remotos (ej. laptops Windows, dispositivos moviles) y redes locales privadas (ej. servidores Homelab detras de CGNAT o routers residenciales sin apertura de puertos).

## Componentes y Arquitectura

* Servicio systemd: `wg-quick@wg0`
* Interfaz de red: `wg0`
* Subred privada predeterminada: `10.66.66.0/24` (servidor toma `10.66.66.1`)
* Puerto predeterminado: `51820` (UDP)
* Directorio de datos: `$HESTIA/data/vpn/`
  * `server.conf`: Parametros del servidor (puerto, subred, endpoint publico, DNS, llave publica).
  * `peers.conf`: Registro de clientes y subredes enrutadas.
  * `clients/<CLIENT>.conf`: Archivos de configuracion listos para importar.
* Configuracion activa en Linux: `/etc/wireguard/wg0.conf`

## Comandos CLI (`bin/`)

### Administracion del Servidor
* Inicializar VPN:
  `v-add-sys-vpn [PUERTO] [SUBRED] [DNS] [IP_PUBLICA]`
  Ejemplo: `v-add-sys-vpn 51820 10.66.66.0/24 "1.1.1.1, 8.8.8.8"`
* Consultar estado del servidor:
  `v-list-sys-vpn [FORMAT]`
* Modificar configuracion del servidor:
  `v-change-sys-vpn [PUERTO] [DNS] [IP_PUBLICA]`
* Desactivar y eliminar VPN:
  `v-delete-sys-vpn`

### Administracion de Clientes (Peers)
* Agregar cliente:
  `v-add-vpn-client CLIENT [ROUTED_NETS] [TUNNEL_MODE] [DNS] [COMMENT]`
  * `CLIENT`: Nombre alfanumerico (ej. `windows-pc`, `homelab`).
  * `ROUTED_NETS`: Subred LAN opcional detras del peer (ej. `192.168.1.0/24`).
  * `TUNNEL_MODE`: `split` (predeterminado, solo enruta subredes VPN/LAN) o `full` (`0.0.0.0/0`).
  * `DNS`: Servidores DNS opcionales.
  * `COMMENT`: Descripcion del peer.
* Listar clientes:
  `v-list-vpn-clients [FORMAT]`
* Ver configuracion de un cliente:
  `v-list-vpn-client CLIENT [FORMAT]`
* Suspender / Reactivar cliente:
  `v-suspend-vpn-client CLIENT`
  `v-unsuspend-vpn-client CLIENT`
* Eliminar cliente:
  `v-delete-vpn-client CLIENT`

## Escenario: Conectar Windows a Homelab a traves de HestiaCP

1. Desde el panel web en Server -> VPN (o via CLI con `v-add-vpn-client`):
   * Crear cliente `homelab` con subred enrutada `192.168.1.0/24`.
   * Crear cliente `windows-pc` en modo `split`.
2. En el servidor Homelab:
   * Descargar `homelab.conf` y guardarlo en `/etc/wireguard/wg0.conf`.
   * Habilitar el servicio: `systemctl enable --now wg-quick@wg0`.
   * El tunel enviara keepalive cada 25 segundos atravesando el NAT residencial.
3. En la maquina con Windows:
   * Importar `windows-pc.conf` en el cliente WireGuard para Windows y activar el tunel.
   * Conectarse directamente a la IP VPN del homelab (`10.66.66.3`) o a cualquier equipo de la red `192.168.1.0/24`.

