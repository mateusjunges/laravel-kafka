#!/bin/bash

# Function to rename files by removing the numeric prefix
rename_files() {
  for file in "$1"/*; do
    if [ -d "$file" ]; then
      # If the file is a directory, recursively call the function
      rename_files "$file"
    elif [ -f "$file" ]; then
      # Check if the file has a prefix number followed by a hyphen or space
      if [[ $(basename "$file") =~ ^[0-9]+[-\ ] ]]; then
        # Remove the prefix and rename the file
        new_name=$(echo "$(basename "$file")" | sed -E 's/^[0-9]+[-\ ]//')
        mv "$file" "$(dirname "$file")/$new_name"
      fi
    fi
  done
}

# Run the function starting from the current directory
rename_files "$(pwd)"

echo "Renaming completed!"