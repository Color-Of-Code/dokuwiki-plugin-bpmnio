# dokuwiki-plugin-bpmnio

Renders using the bpmn.io js libraries within dokuwiki:

* BPMN v2.0 diagrams
* DMN v1.3 decision requirement diagrams, decision tables and literal expressions

Refer to this page for details: <https://www.dokuwiki.org/plugin:bpmnio>

## DW2PDF Support

DW2PDF export now has a pragmatic fallback for diagrams that have already been rendered in a browser. After a BPMN diagram or DMN DRD diagram is displayed on a page, the plugin caches a browser-rendered PNG copy and reuses that cached PNG during `dw2pdf` rendering.

Current limitations:

* The page must have been opened in a browser at least once before `dw2pdf` can include the diagram.
* Only BPMN and DMN DRD diagrams participate in the PNG fallback.
* DMN decision tables and literal expressions still do not have PDF fallback rendering.

## Development

### Prerequisites

* PHP 8.1+
* [Composer](https://getcomposer.org/)
* Node.js 20+ and npm

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
