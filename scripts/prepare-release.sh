#!/bin/bash

version=""

while getopts "v:" arg; do
  case $arg in
    v)
      version=$OPTARG
      ;;
    *)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
  esac
done

# Checks if value of $version is empty
if [[ -z $version ]]; then
  echo "Error: -v option is mandatory" >&2
  exit 1
fi

dir="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
project_dir=$(readlink -f "$dir/..")

$dir/delete-npm-files.sh
$dir/delete-dist.sh
cd $project_dir
npm install
$dir/remove-php-eof-closing-tags.sh -d $project_dir/src
pattern='data-app-version="[0-9]+\.[0-9]+\.[0-9]+"'
replacement="data-app-version=\"$version\""
# Replace version number
sed -r -i '' "s|$pattern|$replacement|g" $project_dir/src/web/app/index.html
node $dir/build.js
