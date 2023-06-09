# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.7](https://github.com/TomasBagdanavicius/stonetable/releases/tag/v1.0.7) - 2023-06-09

### Changed

- Improved responsiveness of main file and file listing loading.
- When a file is loaded and you press “R” key (and the modifier key is not held), the reload function will be called automatically.
- Qualified file paths as well as qualified namespace names that are not backed up by an existing file in filesystem will be underlined using wavy style in message text.
- Advanced PHP syntax highlighter improvements.
- PHP's "intl" extension is no longer compulsory, but recommended.
- New notifications will be shown above existing ones, instead of below them.
- Various small changes and fixes.
- Bump PHP Code Test Suite version to 1.0.7
- Bump Project Directory version to 1.0.7

### Added

- More highlighted elements with colors introduced as part of a broader initiative to colorize demo output and code messages, most notably:
    - File path's basename;
    - Namespace name's last component;
    - Class names;
    - etc.
- It’s now possible to switch to demo or unit file’s source code and back again (not to be confused with source file and its source code). A new "Toggle view" button was added to file's controls menubar.
- New API endpoint [remove-special-comments.php](src/web/api/remove-special-comments.php) that removes all special comments in a given test file.

## [1.0.6](https://github.com/TomasBagdanavicius/stonetable/releases/tag/v1.0.6) - 2023-05-09

### Changed

- Fixed a few CSS issues considering `text-decoration`, `text-decoration-style`, and others in Safari.
- When cross-project jumping to another project’s directory in mobile it will now open/focus the aside panel.
- Excluded "node_modules/" and "dist/" directories from search results in workspace in VS Code specific [settings.json](.vscode/settings.json).
- Namespace names will now also be clickable in source code comments (similar to file path names and URLs).
- Reviewed sanitation efforts of data coming into the API files.
- Various small changes.
- Bump PHP Code Test Suite version to 1.0.6
- Bump Project Directory version to 1.0.6

### Added

- Added directory [`scripts/`](src/resources/project-blueprint/scripts/) to project blueprint. This directory contains 2 automated tasks/scripts: (1) [rebuild-special-comments-all.php](src/resources/project-blueprint/scripts/rebuild-special-comments-all.php) which rebuilds all special comments in all files and (2) [rebuild-special-comments-file.php](src/resources/project-blueprint/scripts/rebuild-special-comments-file.php) which rebuilds all special comments inside selected file. Additionally, [`.vscode/tasks.json`](src/resources/project-blueprint/.vscode/tasks.json) was added to show how these scripts can be run as tasks in VS Code IDE.

## [1.0.5](https://github.com/TomasBagdanavicius/stonetable/releases/tag/v1.0.5) - 2023-05-01

### Changed

- File iterator's sort handler will sort file names whose first character is a special character above files that start with a letter. What is more, the order of special characters is also specific.
- File search functionality will now include file extension into the searchable string. Previously it was ignoring the extension (including the dot prefix). This opens the possibility to search for file names that start with a dot prefix.
- When loaded file is not found (eg. deleted from filesystem) and there is an attempt to reload it, the “main” parameter will be removed from the URL.
- You can now copy source code line number by clicking on the line number next to the code line. Empty lines do not have this option.
- URLs will now be wrapped with hyperlinks inside comments in source code.
- Relevant filesystem path names will be wrapped into clickable elements that will navigate to target files inside the application.
- "Use" declaration namespaces that are not backed by an existing file will be marked with a wavy underline.
- Bump PHP Code Test Suite version to 1.0.5
- Bump Project Directory version to 1.0.5

## [1.0.4](https://github.com/TomasBagdanavicius/stonetable/releases/tag/v1.0.4) - 2023-04-24

### Changed

- Ini directive `zend.exception_ignore_args` will be set to `Off` to make sure that stack trace list arguments are visible on systems where the default configuration has it set to `On` (https://www.php.net/manual/en/ini.core.php#ini.zend.exception-ignore-args)
- Title attribute in favorite items will be populated with relative path names, instead of just file names.
- Added constants `Demo\SRC_PATH` and `Demo\TEST_PATH` which can be used in test files. The former holds project source directory’s path name, whereas the latter project test directory’s path name.
- Special comments will be added right away to a newly built demo file.
- Class reference will not be clickable into a class whose file does not exist.
- `<code>` element’s generic “php” class has been replaced with a more practical “code-php”. This allows to use this class semantically on DIV elements, which is required when presenting code in lines as nested DIV elements. `<code>` elements allows phrasing content only.
- Added `Cache-Control: no-cache` into the main HTML file. The current lightweight structure without service worker implementation does not suffer from this.
- Bump PHP Code Test Suite version to 1.0.4
- Bump Project Directory version to 1.0.4

### Added

- Introduced a new special comment "Static" that will be used in playground files to chain back to corresponding static demo file.

## [1.0.3](https://github.com/TomasBagdanavicius/stonetable/releases/tag/v1.0.3) - 2023-04-16

### Changed

- Various changes to stack trace list text and style formatting as part of a broader initiative to colorize demo output and code messages.
- Standalone demo output page has its responsive mode turned on now.
- Search query string will not be transferred when jumping cross-project into a new tab.
- Position of popups and similar elements will be adjusted on window resize.
- Fixed toolbar and divider styling issue in light color mode in standalone demo output pages.
- Rebuilt the “options” icon in a hope that it will look crisper and aligned better on some browsers in Windows.
- Title attribute in file listing items will be populated with relative path names, instead of just file names.
- Added HTML title to control buttons that empty input fields.
- Added “Quick Run” subsection under “Installation” section in [README.md](README.md#quick-run). It will describe how to run the source code right away.
- Updated representational app screenshot in [README.md](README.md).
- Bump PHP Code Test Suite version to 1.0.3
- Bump Project Directory version to 1.0.3

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
