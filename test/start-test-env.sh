#!/bin/bash
# Start the DokuWiki test environment
set -e
cd "$(dirname "$0")"

echo "Starting DokuWiki test environment..."
docker compose up -d

echo "Waiting for DokuWiki to become healthy..."
for i in {1..30}; do
    if docker inspect --format='{{.State.Health.Status}}' dokuwiki-bpmnio-test 2>/dev/null | grep -q healthy; then
        echo "DokuWiki is ready: http://localhost:8080"
        echo "  BPMN: http://localhost:8080/doku.php?id=test:bpmn-test"
        echo "  DMN:  http://localhost:8080/doku.php?id=test:dmn-test"
        exit 0
    fi
    sleep 2
done

echo "ERROR: Timeout waiting for DokuWiki"
docker logs dokuwiki-bpmnio-test --tail 20
exit 1
