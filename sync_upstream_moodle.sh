#!/bin/bash

# Esta variable es para guardar la rama antigua y recuperar archivos necesarios
export BRANCH_BACKUP="galan_backup"

# Esta variable contiene el nombre de la rama segun la version major de Moodle a la que queramos actualizar, por ejemplo MOODLE_502_STABLE
export BRANCH=MOODLE_502_STABLE

# Verificar que estamos en la rama de la versión que vamos a actualizar, por ejemplo MOODLE_501_STABLE
git branch --show-current

# Llevar los cambios de la rama main a $BRANCH_BACKUP
git branch -f $BRANCH_BACKUP

# Actualizar desde el repo original de Moodle los últimos cambios 
git fetch upstream

# Crear la rama nueva a partir de la rama del upstream
git checkout -b $BRANCH upstream/$BRANCH

# Plugins Galan a incorporar a la nueva rama $BRANCH
for PLUGIN in sync_upstream_moodle.sh public/blocks/blumod README_Galan.md; do
  # git checkout $BRANCH_BACKUP -- $PLUGIN # Legacy
  git restore --source=$BRANCH_BACKUP $PLUGIN
done

# Otros Plugins a incorporar a la nueva rama $BRANCH, si están ya en el repo GIT
# Si no están se actualizan vía interfaz, o con descarga y copia
# La lista completa de plugins están en README_Galan.md
for PLUGIN in public/blocks/configurable_reports public/blocks/people public/blocks/taggedcoursesearch public/mod/pdfannotator public/mod/quiz/accessrule/onesession public/mod/wooclap public/theme/moove; do
  # git checkout $BRANCH_BACKUP -- $PLUGIN # Legacy
  git restore --source=$BRANCH_BACKUP $PLUGIN
done

# Commit de los cambios y push a la nueva rama $BRANCH
git status
git add .
git commit -m "sync, blumod, otros plugins $BRANCH"
git push origin $BRANCH

# Poner al día main con los cambios de $BRANCH
git checkout main
git merge -X theirs $BRANCH
git push origin main

# for BRANCH in MOODLE_500_STABLE; do
#     git checkout -b $BRANCH upstream/$BRANCH
#     # git pull --rebase upstream/$BRANCH
#     git merge upstream/$BRANCH
#     git push origin $BRANCH
#     # git push origin refs/remotes/upstream/$BRANCH:refs/heads/$BRANCH # Se usa pasa hacer push al repo origina lde Moodle. NO USARLO
# done

