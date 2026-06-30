# dokuwiki-plugin-bpmnio

Renders using the bpmn.io js libraries within dokuwiki:

- BPMN v2.0 diagrams
- DMN v1.3 decision requirement diagrams, decision tables and literal expressions

Refer to this page for details: <https://www.dokuwiki.org/plugin:bpmnio>

## Usage

Embed a diagram by wrapping inline XML (or referencing a media file) in a
`<bpmnio>` block:

```
<bpmnio type="bpmn">
...BPMN 2.0 XML...
</bpmnio>

<bpmnio type="bpmn" src="wiki:diagrams:zoning-map-amendment.bpmn" zoom="0.8" />
```

### Attributes

| Attribute | Values                  | Description                                                                        |
| --------- | ----------------------- | ---------------------------------------------------------------------------------- |
| `type`    | `bpmn` (default), `dmn` | Diagram kind.                                                                      |
| `src`     | media id                | Render a stored media file instead of inline XML.                                  |
| `zoom`    | positive number         | Scale factor applied to the rendered diagram.                                      |
| `lint`    | `on`, `off`, `inactive` | Per-diagram [bpmnlint](https://github.com/bpmn-io/bpmnlint) behaviour (BPMN only). |

### Linting BPMN diagrams

BPMN diagrams ship with an embedded [bpmnlint](https://github.com/bpmn-io/bpmnlint)
linter via [bpmn-js-bpmnlint](https://github.com/bpmn-io/bpmn-js-bpmnlint). A
toggle button appears in the corner of the canvas; clicking it overlays
clickable error/warning badges on the offending elements, each opening the list
of issues for that element. This works on rendered wiki pages (read-only viewer)
as well as in the editor.

The `lint` attribute controls the default state per diagram:

- `lint="on"` — overlays are shown immediately.
- `lint="inactive"` — the toggle button is present but overlays start hidden.
- `lint="off"` — the linter is not loaded for that diagram (no button).
- omitted — falls back to the global plugin setting `lint` (configurable in the
  DokuWiki admin under _Configuration Settings → Plugins → bpmnio_). The
  shipped default is `off`, which applies to both rendered pages and the
  editor.

## DW2PDF Support

DW2PDF export now has a pragmatic fallback for diagrams that have already been rendered in a browser. After a BPMN diagram or DMN DRD diagram is displayed on a page, the plugin caches a browser-rendered PNG copy and reuses that cached PNG during `dw2pdf` rendering.

Current limitations:

- The page must have been opened in a browser at least once before `dw2pdf` can include the diagram.
- Only BPMN and DMN DRD diagrams participate in the PNG fallback.
- DMN decision tables and literal expressions still do not have PDF fallback rendering.

Protections:

- **CSRF** — the PNG upload AJAX call requires a valid DokuWiki security token
  (`sectok`), delegated to `checkSecurityToken()`. Unauthenticated or
  cross-origin requests are rejected with HTTP 403.
- **ACL** — the caller must hold at least `AUTH_EDIT` on the page id supplied in
  the request. Read-only visitors cannot seed the cache.
- **Cross-page cache poisoning** — the cache key is derived from the page id in
  addition to the diagram type, zoom variant, and XML content. A key from page A
  is cryptographically invalid for page B, so an editor of one page cannot
  overwrite another page's cached diagram.
- **Filesystem isolation** — cached PNGs are stored under a per-page subdirectory
  (`data/cache/bpmnio/<md5(pageId)>/`). Even a key collision across pages cannot
  cause a file to land in the wrong page's storage bucket.
- **PNG validation** — uploaded data must be a valid PNG: the magic-byte header
  is checked, `getimagesizefromstring()` must confirm `image/png` with non-zero
  dimensions, and the payload is rejected if it exceeds 5 MB.
- **Key format validation** — cache keys are validated as 40-character lowercase
  hex strings (SHA1) before any filesystem path is constructed, preventing path
  traversal.

## Development

### Prerequisites

- PHP
- [Composer](https://getcomposer.org/)
- Node.js and npm

### Setup

```bash
# Install PHP dev dependencies (phpcs, phpstan)
composer install

# Install JS/CSS dev dependencies and vendor build packages
npm install
```

### Linting

```bash
# PHP code style
composer cs

# PHP static analysis
composer stan

# JavaScript lint
npm run lint:js

# LESS/CSS lint
npm run lint:css

# All JS + CSS lints
npm run lint
```

### Testing

Tests run within the DokuWiki test framework. Clone the plugin into a DokuWiki
installation's `lib/plugins/bpmnio/` directory, then run:

```bash
cd /path/to/dokuwiki
php vendor/bin/phpunit --group plugin_bpmnio
```

### Customising the BPMN linter

The lint rules are defined in [`.bpmnlintrc`](.bpmnlintrc) at the repo root and
are compiled into the committed viewer/modeler bundles at build time (bpmnlint
cannot resolve rules in the browser). The default config extends
[`bpmnlint:recommended`](https://github.com/bpmn-io/bpmnlint#built-in-rules).
After editing `.bpmnlintrc`, rebuild the bundles:

```bash
npm run build:vendor   # or ./update-vendor.sh
```

To add project-specific rules, create a local
[bpmnlint plugin](https://github.com/bpmn-io/bpmnlint#plugins) (a
`bpmnlint-plugin-<name>` package exporting `rules` and a `recommended` config),
add it to `package.json` (e.g. as a `file:` dependency), reference it from
`.bpmnlintrc` via `plugin:<name>/recommended`, and rebuild. The packing step
inlines the resolved rules so no resolver is needed at runtime.

### Updating vendor libraries

The committed `vendor/` bundles are generated locally from the npm packages
declared in [package.json](package.json). To update them:

```bash
# Change the pinned bpmn-js / dmn-js versions in package.json when needed
npm install

# Rebuild the committed vendor bundles and copied assets
npm run build:vendor

# Or use the compatibility wrapper
./update-vendor.sh
```

The build step emits the production browser bundles into `vendor/`, copies the
required LESS assets from `node_modules`, and refreshes the public `font/`
directory used by DokuWiki.
