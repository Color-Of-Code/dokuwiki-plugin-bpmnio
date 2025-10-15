# DokuWiki BPMN.io Plugin — Docker Test Environment

Docker Compose setup for testing the plugin in a real DokuWiki instance.

## Usage

```bash
# Start
./start-test-env.sh

# Run basic checks
./run-tests.sh

# Stop
./cleanup-test-env.sh          # keep volumes
./cleanup-test-env.sh --full   # remove everything
```

DokuWiki: http://localhost:8080
- BPMN: http://localhost:8080/doku.php?id=test:bpmn-test
- DMN: http://localhost:8080/doku.php?id=test:dmn-test

The plugin is mounted read-only from the parent directory. Edit files and refresh the browser to see changes.
