# Creación del repo moodle_blumod

Este repo se ha creado en [GitHub](https://github.com/DesarrollosGalan/moodle_blumod.git) mediante un fork repo oficial de [Moodle](https://github.com/moodle/moodle.git). Al ser un fork de Moodle se genera un repo *Público*.

# Desarrollo en local

Los siguientes pasos se recogen en la [documentación oficial](https://docs.moodle.org/405/en/Development:Git_for_developers) publicada por Moodle

### Pasos para desarrollar en local

* Clonar respositorio

Puede hacerse con herramientas como SourceTree o directamente con comandos **git**

```
git clone --branch MOODLE_405_STABLE https://github.com/DesarrollosGalan/moodle_blumod.git
o
git clone git@github.com:<YOUR_GITHUB_USERNAME>/moodle_blumod.git <YOUR_LOCAL_MOODLE_FOLDER>
```

* Establecer el vínculo este repo con el oficial de Moodle

```
cd <YOUR_LOCAL_MOODLE_FOLDER>
git remote add upstream git://git.moodle.org/moodle.git
```

### Actualizar a un versión de Moodle *minor*

```
cd <YOUR_LOCAL_MOODLE_FOLDER>
git pull upstream MOODLE_NNN_STABLE
```

Únicamente mantenemos sincronizadas una rama *MOODLE_NNN_STABLE*.

En el caso de encontrar conflictos, haremos un **merge** indicando que el repo original de Moodle es el que prevalece.

```
cd <YOUR_LOCAL_MOODLE_FOLDER>
git merge -X theirs upstream/MOODLE_NNN_STABLE
```

### Actualizar a una versión de Moodle *major*

Esto suele suponer usar el código de una nueva rama del repo original de Moodle

Si queremos cambiar de versión basta con cambiar la rama a sincronizar. Revisar el script bash **sync_upstream_moodle.sh** para ver el detalle de los comandos a ejecutar.

# Actualizar el servidor

Será muy similar al indicado para el *Desarrollo local*

### Primera instalación o actualización de versión **major** en nueva ruta

Se clonará el repo.

```
cd <YOUR_LOCAL_MOODLE_FOLDER>
git clone https://github.com/DesarrollosGalan/moodle_blumod.git
git checkout MOODLE_NNN_STABLE
```

### Actualizar a un versión de Moodle *minor*

Descargar los cambios

```
cd <YOUR_LOCAL_MOODLE_FOLDER>
git pull origin MOODLE_NNN_STABLE
```

, y su hubiera conflictos

```
cd <YOUR_LOCAL_MOODLE_FOLDER>
git merge -X theirs origin/MOODLE_NNN_STABLE
```

# Moodle 4.5 DB Schema

https://www.examulator.com/er/4.5/index.html

### Moodle 4.5 ERD Competencias

https://www.examulator.com/er/4.5/tables/competency.html
