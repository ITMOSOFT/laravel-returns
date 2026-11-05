# GitHub Release: v1.0.1

**Title:** v1.0.0 — Initial Release

**Repository:** `ITMOSOFT/laravel-returns`

Initial release of ITMOSOFT Laravel Returns, a reusable RMA workflow package for Laravel 12 and 13.

### Included
- Return request and return item records.
- Order and optional polymorphic customer references.
- Status lifecycle with guarded transitions.
- Transactional updates and row locking.
- Status history and lifecycle events.
- Configurable table prefix and order model.

### Out of scope
Refund processing, inventory movements, return-shipping integrations, carrier labels, and policy/eligibility enforcement.

### Requirements
PHP 8.3+, Laravel 12 or 13.

Run `composer test` before publishing to Packagist.


## Patch release notes
Version 1.0.1 adds the package migration for the three return tables. The initial 1.0.0 archive did not include the migration file, which caused the feature tests to fail with a missing `itmosoft_return_requests` table.
