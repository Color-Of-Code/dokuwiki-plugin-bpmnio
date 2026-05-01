# DokuWiki BPMN.io Plugin — Docker Test Environment

Docker Compose setup for testing the plugin in a real DokuWiki instance.

The test container bootstraps a local DokuWiki install automatically. On startup it ensures:

- installs the pinned `dw2pdf` plugin release `2026-01-08` into the persistent `/config` volume
- enables ACLs with local auth
- provisions two users:
  - `user` / `user` with read-only access
  - `admin` / `admin` with full admin access

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

DokuWiki: <http://localhost:8080>

- BPMN: <http://localhost:8080/doku.php?id=test:bpmn-test>
- DMN: <http://localhost:8080/doku.php?id=test:dmn-test>

The plugin source is mounted read-only from the parent directory and linked into Dokuwiki during container bootstrap. Test pages are mounted from `test/data` and shared media fixtures are mounted from `test/media`. Edit files and refresh the browser to see changes.
