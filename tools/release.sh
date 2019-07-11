#!/bin/bash 

set -e

product='core'
head=develop


check=$(git symbolic-ref HEAD | cut -d / -f3-)
if [ $check != "$head" ]; then
    echo "Must be on $head branch"
    exit -1
fi

# So that we can see un-committed stuff
git status

# Display list of recently released versions
git fetch --tags
git log --tags --simplify-by-decoration --pretty="format:%d - %cr" | head -n5

echo "Which version we are releasing: "
read version

function finish {
  git checkout $head
  git branch -D release/$version
  git checkout composer.json
}
trap finish EXIT

# Create temporary branch (local only)
git branch release/$version
git checkout release/$version

# Find out previous version
prev_version=$(git log --tags --simplify-by-decoration --pretty="format:%d" | grep -Eo '[0-9\.A-Z-]+' | head -1)

echo "Releasing $version"
gcg 

vimr CHANGELOG.md

composer update
./vendor/phpunit/phpunit/phpunit  --no-coverage

echo "Press enter to publish the release"
read junk

git commit -m "Added release notes for $version" CHANGELOG.md || echo "but its ok"
merge_tag=$(git rev-parse HEAD)

git commit -m "Set up stable dependencies for $version" composer.json || echo "which is fine"

git tag $version
git push origin release/$version
git push --tags

git checkout $head
git merge $merge_tag --no-edit
git push

echo '=[ SUCCESS ]================================================'
echo "Released atk4/$product Version $version"
echo '============================================================'
echo

open https://github.com/atk4/$product/releases/tag/$version

# do we care about master branch? nah
