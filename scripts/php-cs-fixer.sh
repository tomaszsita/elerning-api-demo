#!/bin/bash

# PHP CS Fixer script for Docker
# This script runs PHP CS Fixer inside the Docker container

set -e

echo "Running PHP CS Fixer..."

# Run PHP CS Fixer in the Docker container
docker-compose exec app vendor/bin/php-cs-fixer fix

echo "PHP CS Fixer completed successfully!"
