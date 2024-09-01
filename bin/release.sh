#!/bin/bash
git checkout main
git merge develop
git push -u origin main
git checkout develop
