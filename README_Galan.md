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

* Mantener sync y actualizado este repo con el de Moodle

Únicamente mantenemos sincronizadas las ramas *main* y *MOODLE_405_STABLE*. Los siguientes comandos se han guardado en el script bash **sync_upstream_moodle.sh**.

```
git fetch upstream
for BRANCH in MOODLE_405_STABLE main; do
 git push origin refs/remotes/upstream/$BRANCH:$BRANCH
done
```

# Moodle 4.5 DB Schema

https://www.examulator.com/er/4.5/index.html

### Moodle 4.5 ERD Competencias

https://www.examulator.com/er/4.5/tables/competency.html
