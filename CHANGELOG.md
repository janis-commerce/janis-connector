# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- The order notification payload includes the order's `storeViewId`, so Janis can resolve the order's REST scope without asking Magento for it again (JHUB-1361)
- System Information panel with the installed version, the PHP runtime, the Magento mode, the Magento and database clocks and the last runs of the Janis crons, also exposed for remote auditing at `GET /V1/janis-connector/system-information` (JHUB-1369)
- `composer.json` declares the module version, so an instance running the module from `app/code` reports it instead of nothing (JHUB-1369)
- The configuration screen follows the log live, appending only the bytes written since the previous poll, with the newest entries at the bottom (JHUB-1368)
- The whole Janis account is read and sent back on every configuration save, so the features, behaviours and OMS settings Janis rebuilds from the request are no longer reset by a save coming from Magento (JHUB-1387)
- A request timeout on the Janis client, so a slow endpoint cannot hang the cron (JHUB-1365)

### Changed
- The log rotates daily and keeps a rolling window of the last 30 days, and both readers walk the file backwards instead of loading it whole, so opening the configuration screen no longer exhausts PHP's memory (JHUB-1368)
- The notification crons read only the ids they need and process a capped batch per run, with an index on both notification columns, instead of loading the whole pending backlog into memory every minute (JHUB-1367)
- `schedule_lifetime` raised to 30 minutes, so a slow run no longer leaves the jobs behind it marked as missed (JHUB-1367)
- Every configuration getter reads the scope of the store it is called for, so the cron no longer notifies with the credentials of another scope (JHUB-1366)
- The whole Janis account is read and sent back on every configuration save, so the features, behaviours and OMS settings Janis rebuilds from the request are no longer reset by a save coming from Magento. The account travels as it comes, with no list of accepted fields to keep in sync: a field added to the account reaches Janis on its own (JHUB-1387)

### Fixed
- The API secret is stored encrypted instead of in clear text in `core_config_data`. A data patch encrypts the ones already saved, so credentials keep working without being re-entered (JHUB-1366)
- A freshly installed module no longer points at the production endpoints by default (JHUB-1366)
- A non 2xx answer from Janis no longer kills the whole cron run: the notification raises a typed exception the cron catches, so the remaining pending orders of that run are still notified (JHUB-1365)
- An order is flagged as notified only once Janis confirms with a RequestId, so a 2xx without one is retried on the next run instead of being lost (JHUB-1365)
- The order history note states what actually happened instead of claiming success on a failure, and no longer dumps the raw response (JHUB-1365)
- The order statuses now travel on every save, including when they are cleared: an emptied status reaches Janis as null instead of leaving the previous one in place, and the invoiced status follows the invoice notification switch (JHUB-1387)

### Removed
- The deprecated `setup_version` in `module.xml`, unused since Magento 2.3 and out of sync with the real version (JHUB-1369)

## [2.2.1] - 2025-11-24
### Fixed
- Fix Moving event to `adminhtml`

## [2.2.0] - 2025-11-24
### Added
- Add new observer when saving Janis Commerce settings

## [2.1.0] - 2025-10-27
### Added
- The order cron was divided into two different ones to differentiate the created orders from the invoiced ones.
- An Observer was added to be able to force historical records related to changes in order statuses,

## [2.0.1] - 2025-09-29
### Fixed
- Fix getting janis settings
- Remove unused code
- Improve logger messages

## [2.0.0] - 2025-09-28
### Added
- Added `LogViewer` in system configuration to display logs
- Added Ajax controller for log handling in admin
- Added new configuration sources: `Environment` and `OrderStatusByState`
- Improved field and view configuration in the system
- Added new settings `is_order_created_notified` and `is_order_invoice_notified`, also added as filters in the orders listing

### Changed
- Improved `OrdersToJanisSender` cron with additional functionalities
- Updated `JanisOrderService` with improvements in order handling
- Improved system configuration (system.xml) with new fields and better organization
- Updated general configuration (config.xml) with new parameters
- Improved UI component sales_order_grid.xml

### Removed
- Removed unnecessary frontend layout and template files `janis_error_index.xml`, `index.phtml`
- Removed `is_sync_with_janis` column configuration

## [1.0.0] - 2025-05-05
### Added
- Project inited