#!/bin/bash

# Read variables from .env and output all the lines starting with NEXT_

while IFS= read -r LINE
do
  case $LINE in
    NEXT_*)
      echo $LINE;;
  esac
done < "$1"
