#!/usr/bin/env bash

echo "updateAuthors.sh"
echo `./.svn2git/updateAuthors.sh`

svn2git --rebase --verbose
