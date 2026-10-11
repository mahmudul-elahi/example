---
paths:
  - '**/*.php'
---

# Rector

## Refactor with Rector, then format with Pint
Automated PHP refactoring runs through Rector (`rector/rector` + `driftingly/rector-laravel`), configured in `rector.php`. Check for pending refactors with `composer rector:dry` and apply them with `composer rector`. Rector and Pint both rewrite PHP, so always run Rector first and `vendor/bin/pint --dirty` second — Pint has the final say on style. Both must be clean before a change is finished.

## Keep rule sets version-driven in rector.php
Do not hardcode PHP or Laravel version sets. `withPhpSets()` follows the `php` constraint in composer.json, and `withComposerBased(laravel: true)` follows the installed framework version, so both stay current on their own. Add further prepared or Laravel sets in rector.php only when the team agrees.
