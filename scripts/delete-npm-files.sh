#!/bin/bash

DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"

# Deletes node_modules directory and its contents
rm -rf "$DIR/../node_modules"

# Deletes package-lock.json file
rm "$DIR/../package-lock.json"
