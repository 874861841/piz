@echo off
cd $REPO/.git
set tagFileName=local-tags.txt
git tag -l >> %tagFileName%
for /f %%x in (%tagFileName%) do git tag -d %%x
git fetch origin --prune
git fetch origin --tags
del %tagFileName%
cd ..