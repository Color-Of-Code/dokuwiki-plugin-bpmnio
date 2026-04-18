#!/bin/bash
# Run basic tests against the DokuWiki test environment
set -e

CONTAINER="dokuwiki-bpmnio-test"
PORT=8080
PLUGIN_DIR="/config/dokuwiki/lib/plugins/bpmnio"

if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
    echo "ERROR: Container $CONTAINER is not running. Run './start-test-env.sh' first."
    exit 1
fi

echo "Waiting for DokuWiki to respond..."
for i in {1..30}; do
    curl -sf "http://localhost:$PORT/" > /dev/null && break
    [ "$i" -eq 30 ] && { echo "ERROR: Timeout"; exit 1; }
    sleep 2
done

echo "Checking plugin files..."
for file in plugin.info.txt syntax/bpmnio.php script/bpmnio_render.js vendor/bpmn-js/package.json vendor/dmn-js/package.json; do
    docker exec "$CONTAINER" test -f "$PLUGIN_DIR/$file" || { echo "MISSING: $file"; exit 1; }
done
echo "  All plugin files present"

echo "Checking media fixtures..."
for file in /config/dokuwiki/data/media/test/bpmn-test.bpmn /config/dokuwiki/data/media/test/dmn-test.dmn; do
    docker exec "$CONTAINER" test -f "$file" || { echo "MISSING: $file"; exit 1; }
done
echo "  Shared media fixtures present"

echo "Checking test pages..."
for page in test:bpmn-test test:dmn-test; do
    curl -sf "http://localhost:$PORT/doku.php?id=$page" | grep -q "bpmn\|dmn" \
        && echo "  $page OK" \
        || echo "  $page WARNING: may have issues"
done

echo "Tests passed."
