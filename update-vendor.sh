#!/usr/bin/env bash
set -euo pipefail

# Update vendor libraries (bpmn-js and dmn-js) from npm registry.
# Downloads pre-built npm packages (which include dist/) and keeps only
# the files needed to run the plugin.
# Usage: ./update-vendor.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENDOR_DIR="$SCRIPT_DIR/vendor"
TMP_DIR=$(mktemp -d)
trap 'rm -rf "$TMP_DIR"' EXIT

# Files/dirs to keep from the npm package
KEEP_PATTERNS=(dist package.json LICENSE README.md CHANGELOG.md)

update_library() {
    local name="$1"
    local version="$2"
    local dest="$VENDOR_DIR/$name"

    local tarball_url="https://registry.npmjs.org/${name}/-/${name}-${version}.tgz"
    echo "Updating $name to $version from $tarball_url ..."

    local archive="$TMP_DIR/$name.tgz"
    curl -sL "$tarball_url" -o "$archive"

    local extract_dir="$TMP_DIR/$name-extract"
    mkdir -p "$extract_dir"
    # npm tarballs extract to a package/ directory
    tar xzf "$archive" -C "$extract_dir"

    local src_dir="$extract_dir/package"
    if [[ ! -d "$src_dir" ]]; then
        echo "ERROR: Could not find extracted package/ directory for $name"
        return 1
    fi

    # Preserve url.txt
    local url_backup="$TMP_DIR/${name}_url.txt"
    cp "$dest/url.txt" "$url_backup"

    # Remove old vendor dir and recreate with only needed files
    rm -rf "$dest"
    mkdir -p "$dest"

    for pattern in "${KEEP_PATTERNS[@]}"; do
        if [[ -e "$src_dir/$pattern" ]]; then
            cp -r "$src_dir/$pattern" "$dest/$pattern"
        fi
    done

    # Restore url.txt and update version references
    cp "$url_backup" "$dest/url.txt"

    echo "Updated $name to $version successfully."
}

get_version() {
    local name="$1"
    local url_file="$VENDOR_DIR/$name/url.txt"

    if [[ ! -f "$url_file" ]]; then
        echo "ERROR: $url_file not found" >&2
        return 1
    fi

    # Extract version from the npm URL in url.txt (e.g. ...@18.14.0/)
    local version
    version=$(grep -oP "${name}@\K[0-9]+\.[0-9]+\.[0-9]+" "$url_file" | head -1)

    if [[ -z "$version" ]]; then
        echo "ERROR: Could not extract version for $name from $url_file" >&2
        return 1
    fi

    echo "$version"
}

bpmn_version=$(get_version "bpmn-js")
dmn_version=$(get_version "dmn-js")

update_library "bpmn-js" "$bpmn_version"
update_library "dmn-js" "$dmn_version"

echo ""
echo "Done. Updated bpmn-js to $bpmn_version and dmn-js to $dmn_version."
