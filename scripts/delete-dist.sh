#!/bin/bash

DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"

# Deletes the dist directory and its contents
rm -rf "$DIR/../dist"
