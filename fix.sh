#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo "Fixing: $DIR"
chown -R laravel:laravel $DIR
chmod -R g+rw $DIR
