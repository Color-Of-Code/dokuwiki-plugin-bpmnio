#!/usr/bin/env bash
set -euo pipefail

# Build committed vendor libraries from the npm packages installed in this repo.
# Usage: ./update-vendor.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

if [[ ! -d node_modules ]]; then
    echo "ERROR: node_modules is missing. Run npm install first." >&2
    exit 1
fi

npm run build:vendor
