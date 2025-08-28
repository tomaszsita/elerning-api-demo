#!/bin/bash

# PHP Code Beautifier and Fixer script for Docker
# This script runs PHPCBF inside the Docker container to automatically fix coding standards

set -e

echo "Running PHP Code Beautifier and Fixer..."

# Run PHPCBF in the Docker container
docker-compose exec app vendor/bin/phpcbf --standard=phpcs.xml

echo "PHP Code Beautifier and Fixer completed successfully!"
