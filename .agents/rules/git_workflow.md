# Reglas de Control de Versiones y Flujo Git

## Organizacion de Ramas

* `main`: Rama principal del fork personal de PiruHost. Debe contener las modificaciones y desarrollos propios (pipeline Git, PNPM, ajustes visuales, `guias/`).
* `upstream-latest`: Rama sincronizada con el repositorio oficial `hestiacp/hestiacp` (`upstream/main`). No realizar commits de funcionalidades propias directamente en esta rama.
* `upstream-1.10.5`: Rama fija de referencia sobre la version 1.10.5 oficial.

## Buenas Practicas

* Para sincronizar novedades de la rama upstream, seguir los procedimientos descritos en `guias/GUIA_SINCRONIZACION.md`.
* Los cambios en desarrollo deben crearse a partir de `main` en ramas de caracteristicas descriptivas si la complejidad lo amerita.
* Mantener los mensajes de confirmacion (commits) claros, sin emojis, y con un lenguaje tecnico profesional.
