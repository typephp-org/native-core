# Changelog

All notable changes to this project will be documented in this file.

## 0.1.0-alpha.2

- Add the resilient Worker Kit with explicit work results, bounded exponential
  retry, failure health, and cooperative shutdown.
- Add the `native new:worker` project scaffolder and `native doctor`
  prerequisite/source-manifest checks.
- Make Windows AOT runtime gates resilient to Opcache ASLR collisions and
  require explicit runtime success markers instead of trusting exit status.
- Publish the Composer package as `typephp-org/native-core` to align with the
  GitHub organization and avoid a Packagist vendor-name collision.
- Validate the Windows AOT build and runtime smokes with TypePHP v0.9.3 and
  its bundled PHP 8.4.26; align project output names with build scripts.

## 0.1.0-alpha.1

- Require PHP 8.4 or newer to match the TypePHP toolchain and generated programs.
- Introduce the AOT-first Application and Module lifecycle.
- Add explicit services, configuration, events, logging and cancellation.
- Add Console, foreground Daemon and Windows Desktop Host adapters.
- Add Zend regressions and TypePHP/Windows native integration smokes.
