#!/bin/bash

git fetch upstream
for BRANCH in MOODLE_405_STABLE; do
    git push origin refs/remotes/upstream/$BRANCH:refs/heads/$BRANCH
done
