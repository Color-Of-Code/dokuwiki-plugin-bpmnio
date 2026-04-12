#!/usr/bin/env bash
set -euo pipefail

# Update vendor libraries (bpmn-js and dmn-js) from their release URLs.
# Usage: ./update-vendor.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENDOR_DIR="$SCRIPT_DIR/vendor"
TMP_DIR=$(mktemp -d)
trap 'rm -rf "$TMP_DIR"' EXIT

update_library() {
    local name="$1"
    local url_file="$VENDOR_DIR/$name/url.txt"

    if [[ ! -f "$url_file" ]]; then
        echo "ERROR: $url_file not found"
        return 1
    fi

    local tarball_url
    tarball_url=$(head -1 "$url_file")

    echo "Updating $name from $tarball_url ..."

    local archive="$TMP_DIR/$name.tar.gz"
    curl -sL "$tarball_url" -o "$archive"

    local extract_dir="$TMP_DIR/$name-extract"
    mkdir -p "$extract_dir"
    tar xzf "$archive" -C "$extract_dir"

    # The archive extracts to a single directory like bpmn-js-18.3.1/
    local src_dir
    src_dir=$(find "$extract_dir" -mindepth 1 -maxdepth 1 -type d | head -1)

    if [[ -z "$src_dir" ]]; then
        echo "ERROR: Could not find extracted directory for $name"
        return 1
    fi

    local dest="$VENDOR_DIR/$name"
    # Preserve url.txt
    local url_backup="$TMP_DIR/${name}_url.txt"
    cp "$dest/url.txt" "$url_backup"

    # Replace vendor directory contents
    rm -rf "$dest"
    cp -r "$src_dir" "$dest"
    cp "$url_backup" "$dest/url.txt"

    echo "Updated $name successfully."
}

update_library "bpmn-js"
update_library "dmn-js"

echo ""
echo "Done. Review the changes and update url.txt if needed."
