# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2](https://github.com/TomasBagdanavicius/stonetable/releases/tag/v1.0.2) - 2023-04-10

### Changed

- Improved Windows compatibility, namely by strengthening support for Windows style backward slashes in path names when running in Windows.
- File search functionality will no longer fail with a “too many files” error, when project contains a lot of files in any of the categories.
- Project loaded notification message will no longer be shown when navigating backwards or forwards.
- The app will navigate back to outside location, when it is visited from another location and then backwards functionality is used.
- Improved backwards and forwards state navigation, though more reviews will be required in the future.
- Stonetable brand icon will now work in Chrome web app (it appears that at least 512x512 icon is required for Chrome PWA).
- Bump Project Directory version to 1.0.2
- Bump PHP Code Test Suite version to 1.0.2
- Added PHP extensions requirements in the [README.md](README.md#requirements)
- Various small amendments.

## [1.0.1](https://github.com/TomasBagdanavicius/stonetable/releases/tag/v1.0.1) - 2023-04-03

### Changed

- Toggle fullscreen button will not be shown when fullscreen mode or fullscreen toggling is not supported, eg. running PWA in fullscreen display mode.
- Current app's version number will be dynamically fetched from app's meta data into the About Stonetable dialog window.
- Various small improvements to the About Stonetable dialog window's content.
- Added abbreviations for words “current” and “version” to the list of arbitrary abbreviations.
- Sorted the list of arbitrary abbreviations alphabetically.
- Some improvements to the JSDoc documentation comments in [app.js](src/web/app/assets/scripts/app.js).
- Bump Project Directory version to 1.0.1
- Bump PHP Code Test Suite version to 1.0.1
- Changed stonetable web app preview image embedding method in [README.md](README.md).
- Changed the way header part is centered in [README.md](README.md) using the not so semantic `align="center"` syntax.

## [1.0.0](https://github.com/TomasBagdanavicius/stonetable/releases/tag/v1.0.0) - 2023-03-23

### Added

- First release files.
