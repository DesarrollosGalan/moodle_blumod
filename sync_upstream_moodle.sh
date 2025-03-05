#!/bin/bash

git fetch upstream
for BRANCH in MOODLE_405_STABLE main; do
    echo "git push origin ..."
    git push origin refs/remotes/upstream/$BRANCH:refs/heads/$BRANCH
done
