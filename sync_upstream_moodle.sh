#!/bin/bash

git fetch upstream
for BRANCH in MOODLE_405_STABLE; do
    # git checkout $BRANCH
    git pull --rebase upstream/$BRANCH
    # git merge upstream/$BRANCH
    git push origin $BRANCH
    # git push origin refs/remotes/upstream/$BRANCH:refs/heads/$BRANCH
done
