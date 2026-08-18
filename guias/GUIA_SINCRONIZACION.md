# Guia de Sincronizacion de Fork con el Repositorio Original (Upstream)

Esta guia describe los pasos necesarios para mantener tu fork de HestiaCP actualizado con los cambios del repositorio oficial (`hestiacp/hestiacp`).

---

## Metodo 1: Sincronizacion mediante la Terminal Local (Recomendado)

### Paso 1: Configurar el remoto "upstream"
Por defecto, tu repositorio local solo apunta a tu fork (`origin`). Para conectarlo al repositorio oficial:

```bash
git remote add upstream https://github.com/hestiacp/hestiacp.git
```

Verifica que los remotos esten configurados correctamente:

```bash
git remote -v
```

Deberias ver una salida similar a:
```text
origin    https://github.com/pirulug/hestiacp.git (fetch)
origin    https://github.com/pirulug/hestiacp.git (push)
upstream  https://github.com/hestiacp/hestiacp.git (fetch)
upstream  https://github.com/hestiacp/hestiacp.git (push)
```

---

### Paso 2: Descargar los cambios del repositorio oficial
Obten las ultimas ramas y commits de `upstream` sin modificar tus archivos de trabajo:

```bash
git fetch upstream
```

---

### Paso 3: Integrar los cambios en tu rama local

1. Posicionate en la rama que deseas actualizar (por ejemplo, `main`):
   ```bash
   git checkout main
   ```

2. Elige uno de los siguientes metodos para integrar:

   * **Opcion A: Mediante `merge` (Conserva el historial original con un commit de union)**
     ```bash
     git merge upstream/main
     ```

   * **Opcion B: Mediante `rebase` (Mantiene tus commits personalizados encima de los cambios oficiales)**
     ```bash
     git rebase upstream/main
     ```

> [!NOTE]
> **Resolucion de conflictos:** Si hay modificaciones sobre las mismas lineas de codigo en archivos que tambien editaste en tu fork, Git pausara el proceso. Modifica los archivos en conflicto para resolver las diferencias, guardalos, agregalos con `git add .` y ejecuta:
> - Si usaste merge: `git commit`
> - Si usaste rebase: `git rebase --continue`

---

### Paso 4: Subir los cambios actualizados a tu fork en GitHub
Envia los commits integrados a tu repositorio remoto (`origin`):

* Si utilizaste `merge`:
  ```bash
  git push origin main
  ```

* Si utilizaste `rebase`:
  ```bash
  git push origin main --force-with-lease
  ```

---

## Metodo 2: Sincronizacion desde la Interfaz Web de GitHub

1. Ingresa a tu repositorio en GitHub: [github.com/pirulug/hestiacp](https://github.com/pirulug/hestiacp).
2. Localiza la seccion debajo del selector de ramas donde se indica el estado respecto al upstream.
3. Haz clic en el boton **"Sync fork"** y selecciona **"Update branch"**.
4. En tu terminal local, descarga las actualizaciones ejecutando:
   ```bash
   git checkout main
   git pull origin main
   ```

---

## Buenas Practicas para el Desarrollo en Forks

1. **Mantener la rama `main` limpia:** Utiliza `main` exclusivamente para sincronizarte con `upstream/main` sin aplicar commits directos en ella.
2. **Trabajar en ramas dedicadas:** Crea ramas de trabajo para tus personalizaciones o caracteristicas:
   ```bash
   git checkout -b feature/mi-modificacion
   ```
3. **Actualizar tus ramas de trabajo:** Cuando `main` este al dia con `upstream`, actualiza tu rama de desarrollo:
   ```bash
   git checkout feature/mi-modificacion
   git merge main
   ```
