#!/bin/bash

# Esta variable es para guardar la rama antigua y recuperar archivos necesarios
export BRANCH_OLD="galan_dev_and_deploy"

# Esta variable contiene el nombre de la rama segun la version de Moodle a la que queramos actualizar
export BRANCH=MOODLE_502_STABLE

git fetch upstream
git checkout -b $BRANCH upstream/$BRANCH
# git checkout $BRANCH_OLD -- sync_upstream_moodle.sh
# git checkout $BRANCH_OLD -- blocks/blumod

# Plugins Galan a recuperar
for PLUGIN in sync_upstream_moodle.sh public/blocks/blumod; do
  # git checkout $BRANCH_OLD -- $PLUGIN # Legacy
  git restore --source=$BRANCH_OLD $PLUGIN
done

# Otros plugins instalados que no están en el core
for PLUGIN in public/blocks/configurable_reports public/blocks/people public/blocks/taggedcoursesearch public/mod/pdfannotator public/mod/quiz/accessrule/onesession public/mod/wooclap public/theme/moove; do
  # git checkout $BRANCH_OLD -- $PLUGIN # Legacy
  git restore --source=$BRANCH_OLD $PLUGIN
done

git status
git add .
git commit -m "sync, blumod, otros plugins $BRANCH"
git push origin $BRANCH

# for BRANCH in MOODLE_500_STABLE; do
#     git checkout -b $BRANCH upstream/$BRANCH
#     # git pull --rebase upstream/$BRANCH
#     git merge upstream/$BRANCH
#     git push origin $BRANCH
#     # git push origin refs/remotes/upstream/$BRANCH:refs/heads/$BRANCH # Se usa pasa hacer push al repo origina lde Moodle. NO USARLO
# done

