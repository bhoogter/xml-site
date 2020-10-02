#!/bin/bash
set -e -x

#git pull -r

# setup vars
DEV_VER="$(cat VERSION)"
ver="$(cat VERSION | sed -e 's/-dev$//')"
MAJOR=$(echo "${ver}" | sed -e 's/\.[0-9]*\.[0-9]*$//')
MINOR=$(echo "${ver}" | sed -e 's/^[0-9]*\.//' | sed -e 's/\.[0-9]*$//')
PATCH=$(echo "${ver}" | sed -e 's/^[0-9]*.[0-9]*.//')

# Tag a release
tagRelease() {
  TAG=$1

  echo "Releasing version: ${TAG}"
  git commit -am "Version $TAG"
  git tag "$TAG"
  git push origin "$TAG"
}

removeTag() {
  TAG=$1
  set +e
  git tag -d $TAG
  git push origin --delete $TAG
  set -e
}

releaseNewVersion() {
  # Remove '-dev' from the version file to prepare for release.
  if [[ "$ver" != "$DEV_VER" ]]; then
    echo $ver > VERSION.tmp
    mv -f VERSION.tmp VERSION
  fi

  tagRelease $ver

  NEW_PATCH=$(( $PATCH + 1 ))
  NEW_VERSION="$MAJOR.$MINOR.$NEW_PATCH-dev"
  echo "Setting new version: ${NEW_VERSION}"
  echo "$NEW_VERSION" > VERSION

  git add VERSION
  git commit -m "Back to -dev: $NEW_VERSION"
  git push origin master
}

rollbackVersion() {
  OLD_PATCH=$(( $PATCH - 1 ))
  OLD_VERSION="$MAJOR.$MINOR.$OLD_PATCH"
  OLD_DEV="$MAJOR.$MINOR.$OLD_PATCH-dev"

  removeTag "$OLD_VERSION"

  echo $OLD_DEV > VERSION.tmp
  mv -f VERSION.tmp VERSION
}


## Begin Script

while getopts "RS" opt; do
  case $opt in
  R|S) REPEAT=1 ;;
  \?)
    echo "Invalid option: -$OPTARG" >&2
    exit 2
    ;;
  esac
done
shift $((OPTIND - 1))

if [[ "$REPEAT" != "" ]]; then 
  rollbackVersion
  
  DEV_VER="$(cat VERSION)"
  ver="$(cat VERSION | sed -e 's/-dev$//')"
  MAJOR=$(echo "${ver}" | sed -e 's/\.[0-9]*\.[0-9]*$//')
  MINOR=$(echo "${ver}" | sed -e 's/^[0-9]*\.//' | sed -e 's/\.[0-9]*$//')
  PATCH=$(echo "${ver}" | sed -e 's/^[0-9]*.[0-9]*.//')
fi

releaseNewVersion