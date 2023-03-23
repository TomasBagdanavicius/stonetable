#!/bin/bash

# Usage:
# ./remove-php-eof-closing-tags.sh -d "./directory-path"

target_dir="."

while getopts "d:" arg; do
  case $arg in
    d)
      target_dir=$OPTARG
      ;;
    *)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
  esac
done

find "$target_dir" -name '*.php' -type f -print0 | while IFS= read -r -d '' file; do
  # Read file contents into a variable
  contents=$(< "$file")
  # Remove all trailing whitespace characters
  contents="$(echo "$contents" | sed -e 's/[[:space:]]*$//')"
  # Contents ends with a '?>' closing tag
  if [[ "$contents" =~ \?\>$ ]]; then
    # Trim off the trailing '?>' closing tag
    contents="${contents%?\>}"
    # Remove all trailing whitespace characters again
    contents="$(echo "$contents" | sed -e 's/[[:space:]]*$//')"
  fi
  # Add a single line break to the end of the string
  contents=$contents$'\n'
  # Replace file contents. The -n parameter in the echo command tells it to
  # not add a newline character at the end of the output.
  echo -n "$contents" > "$file"
done
