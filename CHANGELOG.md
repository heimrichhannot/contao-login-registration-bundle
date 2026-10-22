# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]
- Allow Symfony 7 dependencies while retaining Symfony 5.4 and 6 support.
- Delegate password upgrades and preserve return values from public provider methods.
- Route the legacy username loader through `loadUserByIdentifier()` and remove the frontend login constant shim.
- Declare the bundle path return type required by Symfony 7.
- Preserve the concrete ContaoUserProvider contract required by Contao 5.7 WebAuthn, with explicit ID lookup delegation and signatures compatible with Contao 4.13.
- Add standalone provider regression tests and a PHP/Contao/Symfony compatibility workflow.

## [0.2.1] - 2025-01-09
- Added: exception handling for invalid registrations
- Added: show errors to user why registration not worked (like invalid password, configuration errors, ...)

## [0.2.0] - 2025-01-09
- Added: new jumpTo fields (need manual adjustments!)
- Changed: reworked the registration and jumpTo handling
- Changed: load bundle after member bundle
- Fixed: frontend module name

## [0.1.3] - 2025-01-07
- Added: BeforeParseModuleEvent
- Changed: require php >= 8.1

## [0.1.2] - 2024-01-15
- Fixed: execution due invalid parameter passing

## [0.1.1] - 2023-10-18
- Fixed: dependency

## [0.1.0] - 2023-10-18
Initial release
