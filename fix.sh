#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo "Fixing: $DIR"
sudo chown -R laravel:laravel $DIR
sudo chmod -R g+rw $DIR
