# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- System Information panel with the installed version, the PHP runtime, the Magento mode, the Magento and database clocks and the last runs of the Janis crons, also exposed for remote auditing at `GET /V1/janis-connector/system-information` (JHUB-1369)
- `composer.json` declares the module version, so an instance running the module from `app/code` reports it instead of nothing (JHUB-1369)

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