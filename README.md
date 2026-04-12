# dokuwiki-plugin-bpmnio

Renders using the bpmn.io js libraries within dokuwiki:

* BPMN v2.0 diagrams
* DMN v1.3 decision requirement diagrams, decision tables and literal expressions

Refer to this page for details: https://www.dokuwiki.org/plugin:bpmnio

## Development

### Prerequisites

* PHP 8.1+
* [Composer](https://getcomposer.org/)
* Node.js 20+ and npm

### Setup

```bash
# Install PHP dev dependencies (phpcs, phpstan)
composer install

# Install JS/CSS dev dependencies (eslint, stylelint)
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

The `vendor/` directory contains committed copies of bpmn-js and dmn-js.
To update them to the versions specified in `vendor/*/url.txt`:

```bash
./update-vendor.sh
```

After updating, edit the `url.txt` files if you want to target a different version.
