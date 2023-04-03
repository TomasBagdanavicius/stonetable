<p align="center"><img src="https://www.lwis.net/stonetable/images/stonetable-logo.svg" width="75" height="75" alt="Stonetable logo"></p>

<h1 align="center">Stonetable</h1>

Stonetable is lightweight, dependency-free, conceptual PHP project build and code testing and debugging suite, which offers 3 main components:

1. [Stonetable web application that combines all features into one UI driven place](#stonetable-web-application)
2. [Advanced PHP code tokenizer and syntax highlighter](#advanced-php-code-tokenizer-and-syntax-highlighter)
3. [PHP error handling, viewing, and readability enhancements](#php-error-handling-and-readability-enhancements)

## Stonetable Web Application

![Meet Stonetable](https://www.lwis.net/stonetable/images/meet-stonetable.webp)

Stonetable's web application is the core component of the suite. This web app helps you navigate and visualize your PHP project builds by providing incentives to maintain organized demo and unit test files that can be outputted in a user friendly manner with enhanced state and error reporting.

### Quick Start

If you want to look through a more visual presentation of Stonetable Web App, please visit the official website at https://www.lwis.net/stonetable/

There is also a live demo at https://www.lwis.net/stonetable/demo/app/ to get a good feel for how this system is working in real life. Please note that live demo does not connect to projects on your local machine, for obvious reasons, hence the absence of some features (see: [Connection to API Hosted Locally vs. API Hosted Externally](#connection-to-api-hosted-locally-vs-api-hosted-externally)).

### Features

- Smart demo file outputs with visually improved, readable PHP error reporting
- Fast, easy to understand unit test runner
- Remote project listing
- Advanced PHP syntax highlighting
- Class reference navigation in source code
- Switching between projects in class reference navigation
- Simple interface to any IDE that supports file open URI implementation
- Special comments in source, demo, unit, and playground files with links to each other as an additional chain navigation feature
- Playground area branch to both demo and unit test files for isolated probing
- Clickable file names and namespace names in text and messages
- Responsive web design
- Remote file tree navigation
- Accent-insensitive project and file search
- Light and dark color themes
- Full-screen support
- Favorites storage
- Word-wrapping in source files
- etc.

### Installation

First off, it's important to decide where you will want to install Stonetable. The recommended option is to install on the machine where you will have read and write access to your project files, because as we mentioned in the [Features](#features) Stonetable can jump back and forth from your IDE. It's also an option to install on external server, but then it won't talk to your IDE. What is more, web app and API with project files access are separatable, meaning that you could install the API locally and the web app externally. For additional consideration please see [Connection to API Hosted Locally vs. API Hosted Externally](#connection-to-api-hosted-locally-vs-api-hosted-externally)

#### Requirements

- Any web server
- PHP 8.1 or higher
- Any major web browser that is up to date

#### Steps

- Fork the GitHub repository or download a copy of archived files.
- Go to the `dist/` directory, familiarize quickly with the tree structure, and move the files for web use:
    - Files under the `web/` subdirectory must be copied over to your public web directory, eg. public_web/stonetable.
    - Files under the `stonetable/` subdirectory should be stored somewhere outside your public web directory, though this is only a recommendation.
        - You could easily move contents from the `stonetable/` subdirectory to `web/stonetable`, if you don't want to split public from private files, especially if you are installing it on your local machine.
- Prepare your projects directory:
    - It's recommended to have the projects directory web accessible, because Stonetable Web App, for instance, has a feature to open project files in new tab.
    - It must be a directory which contains individual project directories.
    - Each project directory must comply with structure requirements (see section: [Compatible Project Directory Blueprint](#compatible-project-directory-blueprint)).
- Modify `web/stonetable/config.php` file:
    - Amend path to your projects directory in constant `PROJECTS_PATH`.
    - Check and verify the path to the library directory in constant `LIB_PATH`:
        - React appropriately, if you moved contents from the `stonetable/` subdirectory into public directory.
- If you want to separate Web app from project file API, you can move `web/stonetable/app/` to any other web location - same or another web server. Files inside `web/stonetable/app/` are not dependent upon other files outside this directory.

## The Concept of Source-Test-Playground Chaining

The Stonetable web application follows the so called *Source-Test-Playground* chaining concept. It implies the following:

1. There is a source file point, which produces capabilities (eg. function, class, any other calculation, etc.).
2. Those capabilities should be tested in isolation:
    - As a static demonstration (normally stable, seldom changed point).
    - As a static unit test (crafted stable point).
3. To probe issues in tests a separate playground area should be used, which is unstable, often amended point to fix issues.
    - When desired results are achieved in playground, there are ported over back to static tests.

Stonetable web app provides a visual interface where it seamlessly allows to control this chain.

## Compatible Project Directory Structure

The Stonetable web app and other parts of the Stonetable suite (eg. the project directory component) works only with PHP files organized into projects. It also requires project files to be systematically structured, for example:

```
project-name/
    .config/
        project-directory-config.php
    src/
    test/
        demo/
            static/
            playground/
        units/
            static/
            playground/
```

You can find a prepped blueprint directory in [`src/resources/project-blueprint/`](src/resources/project-blueprint/).

Here `project-directory-config.php` is a file which stores configuration for the project directory. For more info, please consult the sample file in the blueprint directory, see [`src/resources/project-blueprint/.config/project-directory-config.php`](src/resources/project-blueprint/.config/project-directory-config.php).

`src/` is a common name for a source directory, whereas `test/` is where test files are stored. Keep in mind that this is the default naming convention, but it can be customized in the `project-directory-config.php` file.

`test/` directory branches into 2 other directories: `demo/` and `units/`. The former is where you store files which demonstrate capabilities of associated source files. The latter `units/` is for unit tests. Each of these directories is further branched into `static/` and `playground/` categories. `static/` holds files which should not change, or change rarely, and the `playground/` category is were you can play with and optimize the static files.

For example, say we have file `src/file.php` that performs a calculation function. Now, to test that calculation you would create `test/demo/static/file.php` which would be a direct associate to the source file. In case there was an issue in the static file that you want to troubleshoot, you would create an associate playground file in `test/demo/playground/file.php`.

Chaining hasn't been fully implemented for the `units/` directory, therefore for now the common practise is to create custom directories with unit files inside `test/units/static/` directory, eg. `test/units/static/category-1/unit-test-1.php`.

Find the [starter demo file](src/resources/project-blueprint/test/demo/static/%2Bnew.php) and the [starter unit test file](src/resources/project-blueprint/test/units/%2Bnew.php) to create your demo and unit test files.

---

**Important to note:** the Stonetable web app will do most of the above things automatically for you. The above is just a guideline info to understand the basics of compatible project structure.

## Connection to API Hosted Locally vs. API Hosted Externally

Stonetable web app reads projects and files remotely by the help of a built-in API. The API is separated from the web app, meaning that you can host the web app at one location and the API with project access in another. Or you can host both together. Since the API is reading projects, it must be where the projects are.

It is important to understand that there is the so called **local development API access** which implies that machine which is accessing API is the host of that API and also the host of project files. When this is true, Stonetable web app will show additional options accross the app that will allow to establish a basic interface with your IDE through the file open implementation to jump from app to IDE and vice versa. This feature is not available when you are connecting to an API which is not running on your local machine.

To install see: [Installation](#installation).

## Building Your Own Version of Stonetable Web App

- All source code of this package is organized into the `src/` directory.
- Most probably, you will want to make amends to files in `src/web/` directory, though you can modify anything you want, really.
- To build from the source code, you will have to:
    - Install [Node.js](https://nodejs.org/en/download/) and [npm package manager](https://www.npmjs.com/).
        - The most common way is to [install via package manager](https://nodejs.org/en/download/package-manager/).
    - Open your OS's terminal emulator;
    - Navigate to the directory where you have your Stonetable package files;
    - Run `npm install` to install all required [Node.js](https://nodejs.org/) dev dependencies (they will be saved into a new `node_modules/` directory).
    - Run `node ./scripts/build.js` to process all build tasks.
        - If you run into issues with any dev dependency package, search for it and read its documentation on https://www.npmjs.com/
- It will build into the `dist/` directory.
- Install for web use from `dist/`, see: [Installation](#installation).

# Advanced PHP Code Tokenizer and Syntax Highlighter

Stonetable's PHP code tokenizer is build on top of the `PhpToken` class (see: https://www.php.net/manual/en/class.phptoken). Stonetable adds features that allow to organize tokens into more meaningful categories (see: [src/lib/php-code-test-suite/PhpTokens/PhpTokenCategoryEnum.php](src/lib/php-code-test-suite/PhpTokens/PhpTokenCategoryEnum.php)) and then achieve such capabilities as advanced syntax highlighting.

## Usage Example

```php
include '/path/to/stonetable/lib/php-code-test-suite/Autoloader.php';

use PCTS\PhpTokens\Tokenizer;

$code = '<?php echo "Hello World!";';
/* Or better off:
$code = file_get_contents('/path/to/file.php'); */

// Initializes Tokenizer with options
$tokenizer = new Tokenizer(
    $code,
    (Tokenizer::SPLIT_SPLITABLE_TOKENS
    | Tokenizer::KEY_AS_LINE_NUMBER
    | Tokenizer::CURRENT_AS_HTML)
);

// Includes customizable stylesheet with CSS variables
echo '<link rel="stylesheet" href="/path/to/stonetable/lib/php-code-test-suite/misc/php-code-highlight.css">';

// Generates code block
echo '<code class="php">';
foreach ($tokenizer as $line_number => $html) {
    echo $html;
}
echo '</code>';
```

The above will output something similar to this:

```html
<link rel="stylesheet" href="/path/to/stonetable/lib/php-code-test-suite/misc/php-code-highlight.css">

<code class="php"><span class="open-tag">&lt;?php</span><span class="whitespace"> </span><span class="function-like-keyword keyword-echo">echo</span><span class="whitespace"> </span><span class="string">&quot;Hello World!&quot;</span><span class="punctuation">;</span></code>
```

Notable things:

- Check paths prefixed with `/path/to/` to be replaced with your working path names.
- CSS file `php-code-highlight.css` is just a starter, find this sample file in [src/lib/php-code-test-suite/misc/php-code-highlight.css](src/lib/php-code-test-suite/misc/php-code-highlight.css).
    - You can develop your own stylesheet with CSS variables and color schemes.
    - You will find all available class names to work with in this file.

More info:

Check out the [advanced PHP code highlighting test in Stonetable web app demo](https://www.lwis.net/stonetable/demo/app/?project=test-project-1&side=src&main=src%2Fcode-highlighting.php).

# PHP Error Handling and Readability Enhancements

## Usage Example

Say, we have the following contents of a `/Users/JohnDoe/stonetable/snippet.php` file:

```php
<?php

declare(strict_types=1);

/* Set up the handler at the top */

include '/path/to/stonetable/lib/php-code-test-suite/Autoloader.php';

use PCTS\Debugger\Debugger;
use PCTS\OutputText\OutputTextFormatter;
use PCTS\PhpErrors\PHPErrorHandler;

$output_text_formatter = new OutputTextFormatter(
    // A list of absolute paths that should be replaced with an ellipsis in
    // extended paths, eg. /foo/bar would make /foo/bar/baz/file.php become
    // .../baz/file.php
    shorten_paths: [
        __DIR__,
    ],
    // A list of: "VendorName" => "/path/to/vendor/project"
    vendor_data: [
        'VendorName' => __DIR__,
    ],
    // Setup interface to IDE via file open URI
    ide_uri_format: 'vscode://file/{file}[:{line}][:{column}]',
    // Whether to return output in HTML format
    format_html: php_sapi_name() !== 'cli',
    // Whether to convert text to links when "format_html" is set to true
    convert_links: true
);

$debugger = new Debugger(
    formatter: $output_text_formatter
);

$error_handler = new PhpErrorHandler(
    debugger: $debugger,
    // Defines how output should be handled
    output_handler: function(string $output): void {
        echo $output;
    }
);

/* Custom code to handle */

echo $unexisting_variable;

$filename = pathinfo(__FILE__, PATHINFO_FILENAME);

throw new Error(
    message: "This is a simulated error: VendorName\\$filename",
    code: 1,
    previous: new Exception("This is the reason")
);
```

The above will output something similar to:

```html
<article class="msg code-msg code-msg-warn">
    <p class="text"><strong>Undefined variable <code class="var">$unexisting_variable</code></strong></p>
    <dl>
        <dt>Location</dt>
        <dd><a href="vscode://file//Users/JohnDoe/stonetable/snippet.php:46">.../snippet.php:46</a></dd>
        <dt>Type</dt>
        <dd>E_WARNING</dd>
    </dl>
</article>
<article class="msg code-msg code-msg-err">
    <p class="text"><strong>This is a simulated error: <a href="vscode://file//Users/JohnDoe/stonetable/snippet.php">VendorName\snippet</a></strong></p>
    <dl>
        <dt>Location</dt>
        <dd><a href="vscode://file//Users/JohnDoe/stonetable/snippet.php:50">.../snippet.php:50</a></dd>
        <dt>Error Class</dt>
        <dd>Error</dd>
        <dt>Error Code</dt>
        <dd>1</dd>
    </dl>
    <article class="msg code-msg code-msg-err">
        <p class="text"><strong>This is the reason</strong></p>
        <dl>
            <dt>Location</dt>
            <dd><a href="vscode://file//Users/JohnDoe/stonetable/snippet.php:53">.../snippet.php:53</a></dd>
            <dt>Error Class</dt>
            <dd>Exception</dd>
            <dt>Error Code</dt>
            <dd>0</dd>
        </dl>
    </article>
</article>
```

To take away:

- Since we asked to shorten absolute paths that start with the path where this snippet is located, system has replaced those with ellipsis.
- Absolute paths that resolve to an existing file in the filesystem have been wrapped into an IDE file open hyperlinks.
- Namespace names that start with recornized vendor names have also been converted to clickable links that open in IDE.
- There is no dedicated stylesheet solution here, but you can develop your own, or grab one from the source (see: [src/web/assets/css/code-message.css](src/web/assets/css/code-message.css)).

# Licensing

Stonetable is released under the MIT License. The build code has no dependencies whatsoever and thus is not dependent upon any 3rd party licensing conditions.