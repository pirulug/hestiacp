# Guía de Procedimientos Manuales (Equivalentes a GitHub Actions)

Este documento detalla cómo ejecutar local y manualmente cada una de las tareas automatizadas que se encuentran en el directorio `.github/workflows/` de HestiaCP.

---

## Índice de Tareas

1. [Análisis Estático y Formateo (Linting)](#1-análisis-estático-y-formateo-linting)
   - [Markdownlint](#11-markdownlint-validación-de-archivos-markdown)
   - [ShellCheck](#12-shellcheck-validación-de-scripts-bash)
   - [Prettier](#13-prettier-formateo-de-código)
   - [Biome](#14-biome-linter-de-javascript-y-css)
2. [Pruebas de la Documentación (Vitest)](#2-pruebas-de-la-documentación-vitest)
3. [Pruebas de Integración con Docker y BATS](#3-pruebas-de-integración-con-docker-y-bats)
4. [Sincronización de Traducciones (Crowdin)](#4-sincronización-de-traducciones-crowdin)

---

## 1. Análisis Estático y Formateo (Linting)

Corresponde al flujo automatizado en `.github/workflows/lint.yml`.

### 1.1. Markdownlint (Validación de archivos Markdown)
Analiza la sintaxis y estilo de todos los archivos `.md` del proyecto.

* **Revisar errores:**
  ```bash
  npx markdownlint-cli2 "*.md" "docs/**/*.md"
  ```
* **Corregir errores automáticamente (donde sea posible):**
  ```bash
  npx markdownlint-cli2 --fix "*.md" "docs/**/*.md"
  ```

---

### 1.2. ShellCheck (Validación de scripts Bash)
Inspecciona todos los scripts del sistema (`bin/`, `install/`, `src/`) para detectar malas prácticas, variables sin comillas o errores de compatibilidad POSIX.

* **En Linux / WSL / Servidor:**
  ```bash
  # Instalar shellcheck si no está presente
  apt-get install -y shellcheck

  # Ejecutar en los scripts principales
  shellcheck --severity=error bin/v-* install/*.sh src/*.sh
  ```

* **En Windows vía npx:**
  ```bash
  npx shellcheck --severity=error bin/v-* install/*.sh src/*.sh
  ```

---

### 1.3. Prettier (Formateo de código)
Verifica que el código PHP, JS, CSS, Nginx, Shell y SQL cumpla con las reglas de estilo del proyecto.

* **Verificar si hay archivos sin formatear:**
  ```bash
  npx prettier --check .
  ```

* **Aplicar formateo automáticamente en todo el proyecto:**
  ```bash
  npx prettier --write .
  ```

---

### 1.4. Biome (Linter de JavaScript y CSS)
Analiza la calidad del código JS y CSS utilizando la configuración definida en `biome.json`.

* **Ejecutar el linter:**
  ```bash
  npx @biomejs/biome lint .
  ```

* **Aplicar correcciones seguras recomendadas:**
  ```bash
  npx @biomejs/biome check --write .
  ```

---

## 2. Pruebas de la Documentación (Vitest)

Corresponde al flujo automatizado en `.github/workflows/test.yml`. Ejecuta las pruebas unitarias de los componentes interactivos de la documentación de VitePress.

```bash
npm run docs:test
```

*(Equivalente manual: `npx vitest run --config docs/.vitepress/vitest.config.js`)*

---

## 3. Pruebas de Integración con Docker y BATS

Corresponde al flujo automatizado en `.github/workflows/pr-docker-bats.yml`. Construye un contenedor Ubuntu con Systemd real, compila los paquetes `.deb` locales de HestiaCP, instala el panel y corre la suite de pruebas automatizadas BATS (`test/test.bats` y `test/api.bats`).

### Requisitos previos
* Docker instalado y en ejecución.

### Paso 1: Construir la imagen Docker de prueba
```bash
docker build -f .github/docker/hestia-ci.Dockerfile -t hestia-ci:local .
```

### Paso 2: Iniciar el contenedor con soporte para Systemd y Cgroups
```bash
docker run -d \
  --name hestia-ci \
  --hostname hestia-dev.local \
  --privileged \
  --cgroupns=host \
  --tmpfs /run \
  --tmpfs /run/lock \
  --tmpfs /tmp:exec,mode=1777 \
  --dns 8.8.8.8 \
  --dns 8.8.4.4 \
  -e TZ=UTC \
  -e LANG=en_US.UTF-8 \
  -e LC_ALL=en_US.UTF-8 \
  -v /sys/fs/cgroup:/sys/fs/cgroup:rw \
  -v "$(pwd):/hestiacp-git" \
  -w /hestiacp-git \
  hestia-ci:local
```

### Paso 3: Compilar e instalar HestiaCP dentro del contenedor
```bash
docker exec -it hestia-ci bash -lc '
  set -euxo pipefail
  cd /hestiacp-git/src
  bash ./hst_autocompile.sh --hestia --noinstall --keepbuild "~localsrc"
  
  cd /hestiacp-git
  bash install/hst-install-ubuntu.sh \
    --hostname hestia.local.test \
    --email admin@example.com \
    --username admin \
    --password Password123 \
    --interactive no \
    --force \
    --with-debs /tmp/hestiacp-src/deb \
    --clamav no \
    --spamassassin no
'
```

### Paso 4: Ejecutar las pruebas BATS
```bash
docker exec -it hestia-ci bash -lc '
  set -euxo pipefail
  cd /hestiacp-git
  if ! command -v bats >/dev/null 2>&1; then
    test/test_helper/bats-core/install.sh /usr/local
  fi
  bats test/test.bats
'
```

### Paso 5: Detener y limpiar el contenedor al finalizar
```bash
docker rm -f hestia-ci
```

---

## 4. Sincronización de Traducciones (Crowdin)

Corresponde al flujo automatizado en `.github/workflows/crowdin.yml`. 

* Las plantillas de traducción del panel se encuentran en `web/inc/i18n/`.
* Para extraer o sincronizar manualmente cadenas de traducción a formato `.po`/`.pot`, HestiaCP utiliza utilidades estándar de `gettext` (`xgettext` / `msgmerge`).
* Si utilizas la CLI oficial de Crowdin de forma manual:
  ```bash
  crowdin upload sources
  crowdin download
  ```
