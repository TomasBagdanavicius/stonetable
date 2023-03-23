#!/bin/bash

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

# Initialize variables to count the number of files and lines found
file_count=0
line_count=0

# Recursively loop through all files in the target directory
while read -r file; do
    # Check if the file is a text file (excluding binary files)
    if file -b "$file" | grep -q 'text'; then
        ext="${file##*.}"
        # Check if the extension matches any of the specified extensions
        if [[ "$extensions" == *"$ext"* || "$extensions" == "*" ]]; then
            # Check if the file contains any lines longer than 80 characters
            long_lines=$(grep -n '.\{81\}' "$file")
            if [ -n "$long_lines" ]; then
                # If any long lines were found, print the file path along with the line numbers and content
                echo "File: $file"
                echo "$long_lines" | awk -F ":" -v file="$file" '{
                    content = substr($2, 1, 80);
                    if (length($2) > 80) {content = content "..."}
                    print file ":" $1 ":" length($2) + 1 " => " content
                }'
                echo ""
                # Increment the file and line counters
                ((file_count++))
                line_count=$(($line_count + $(echo "$long_lines" | wc -l)))
            fi
        fi
    fi
done < <(find $directory -type f)

# Print the total number of files and lines found
echo "Total files with lines longer than 80 characters: $file_count"
echo "Total lines longer than 80 characters: $line_count"
