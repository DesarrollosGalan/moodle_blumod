#!/bin/bash

# Esta variable es para guardar la rama antigua y recuperar archivos necesarios
export BRANCH_OLD="galan_dev_and_deploy"

# Esta variable contiene el nombre de la rama segun la version de Moodle a la que queramos actualizar
export BRANCH=MOODLE_501_STABLE
git fetch upstream
git checkout -b $BRANCH upstream/$BRANCH
git checkout $BRANCH_OLD -- sync_upstream_moodle.sh
git checkout $BRANCH_OLD -- blocks/blumod
git status
git add .
git commit -m "sync y blumod $BRANCH"
git push origin $BRANCH

# for BRANCH in MOODLE_500_STABLE; do
#     git checkout -b $BRANCH upstream/$BRANCH
#     # git pull --rebase upstream/$BRANCH
#     git merge upstream/$BRANCH
#     git push origin $BRANCH
#     # git push origin refs/remotes/upstream/$BRANCH:refs/heads/$BRANCH # Se usa pasa hacer push al repo origina lde Moodle. NO USARLO
# done
