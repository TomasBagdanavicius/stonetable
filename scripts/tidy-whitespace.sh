#!/bin/bash

# Usage:
# ./tidy-whitespace.sh -e "txt md" -d ./dir-path

# Parse the command-line arguments
while getopts ":d:e::" opt; do
  case ${opt} in
    d)
      directory=${OPTARG}
      ;;
    e)
      extensions=${OPTARG}
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
    :)
      echo "Option -$OPTARG requires an argument." >&2
      exit 1
      ;;
  esac
done

# If no directory argument specified, use current directory
if [ -z "$directory" ]; then
  directory="."
fi

# If no extensions argument specified, match all files
if [ -z "$extensions" ]; then
  extensions="*"
else
  # Convert the extensions string to a comma-separated list
  extensions=$(echo "$extensions" | tr ' ' ',')
fi

# Loop through all files in the directory and filter by extension
find "$directory" -type f | while read file; do
  # Get the file extension
  ext="${file##*.}"
  # Check if the extension matches any of the specified extensions
  if [[ "$extensions" == *"$ext"* || "$extensions" == "*" ]]; then
    # Read contents of file
    contents=$(cat "$file")
    # Remove trailing whitespace characters on each line
    contents="$(echo "$contents" | sed 's/[[:blank:]]*$//')"
    # Write modified contents back to file
    # The -n option tells echo not to append a newline character to the
    # output. This will trim all file trailing whitespace characters.
    echo -n "$contents" > "$file"
  fi
done
