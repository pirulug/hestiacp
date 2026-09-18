# Guia de Navegacion y Gestion de Ramas

Esta guia detalla las ramas disponibles en este repositorio y los comandos necesarios para alternar entre ellas, inspeccionar su estado y mantenerlas actualizadas.

## Ramas Disponibles en el Repositorio

* **main**: Rama principal de tu fork personal (`pirulug/hestiacp`). Contiene todas las funcionalidades propias desarrolladas (integracion Git para dominios web, utilidades PNPM, personalizaciones visuales de login/panel y la carpeta `guias/`).
* **upstream-latest**: Rama sincronizada directamente con la rama de desarrollo oficial de HestiaCP (`upstream/main`). Contiene las ultimas correcciones y actualizaciones del repositorio oficial.
* **upstream-1.10.5**: Rama fija en la version oficial estable 1.10.5 (`tags/1.10.5`), util como referencia del codigo limpio de la version de lanzamiento.

## Comandos para Navegar entre Ramas

### Ver en que rama te encuentras
Para listar las ramas locales y verificar la rama activa (marcada con un asterisco `*`):

```bash
git branch
```

Para listar todas las ramas, incluyendo las remotas (`origin` y `upstream`):

```bash
git branch -a
```

### Cambiar a tu fork personal (main)
Para regresar a tu entorno de trabajo con todas tus personalizaciones:

```bash
git checkout main
```

### Cambiar a la rama oficial actualizada (upstream-latest)
Para revisar o probar el codigo mas reciente de HestiaCP oficial:

```bash
git checkout upstream-latest
```

### Cambiar a la version estable oficial (upstream-1.10.5)
Para inspeccionar la base limpia de la version 1.10.5:

```bash
git checkout upstream-1.10.5
```

## Creacion de Nuevas Ramas de Trabajo

Si vas a desarrollar una nueva funcionalidad o realizar pruebas sin afectar la rama `main`, crea una rama dedicada a partir de la rama deseada:

### Crear rama a partir de tu fork (main)
```bash
git checkout main
git checkout -b feature/nueva-funcion
```

### Crear rama a partir del codigo oficial (upstream-latest)
```bash
git checkout upstream-latest
git checkout -b prueba/codigo-oficial
```

## Manejo de Cambios al Cambiar de Rama

### Archivos sin seguimiento (Untracked)
La carpeta `guias/` solo esta registrada dentro de la rama `main`. Cuando cambias a una rama oficial como `upstream-latest`, Git detectara `guias/` como archivos sin seguimiento (*untracked files*). No necesitas borrarlos; al volver a `main` con `git checkout main`, se reintegraran normalmente.

### Guardar cambios en curso antes de cambiar de rama
Si editaste archivos pero aun no deseas confirmarlos con un commit, puedes guardarlos temporalmente en la papelera de Git (*stash*):

```bash
# Guardar cambios pendientes
git stash

# Cambiar a la otra rama
git checkout upstream-latest

# Regresar y recuperar los cambios pendientes
git checkout main
git stash pop
```

## Actualizacion de Ramas

### Actualizar la rama oficial (upstream-latest)
Para descargar las ultimas novedades que el equipo de HestiaCP publique en GitHub:

```bash
git checkout upstream-latest
git pull upstream main
```

### Descargar todas las etiquetas y ramas oficiales sin cambiar de rama
```bash
git fetch upstream --tags
```

### Publicar cambios de tu fork en tu GitHub (origin)
Para enviar commits locales de `main` a tu repositorio remoto:

```bash
git checkout main
git push origin main
```
