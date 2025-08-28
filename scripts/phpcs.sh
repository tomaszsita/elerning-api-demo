#!/bin/bash

# PHP Code Sniffer script for Docker
# This script runs PHPCS inside the Docker container

set -e

echo "Running PHP Code Sniffer..."

# Run PHPCS in the Docker container
docker-compose exec app vendor/bin/phpcs --standard=phpcs.xml

echo "PHP Code Sniffer completed successfully!"
