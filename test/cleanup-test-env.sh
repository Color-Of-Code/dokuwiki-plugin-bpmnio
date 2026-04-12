#!/bin/bash
# Clean up the DokuWiki test environment
set -e
cd "$(dirname "$0")"

if [ "$1" = "--full" ]; then
    echo "Removing containers, volumes, and images..."
    docker compose down -v --rmi all
else
    echo "Stopping containers..."
    docker compose down
    echo "Use '--full' to also remove volumes and images"
fi
