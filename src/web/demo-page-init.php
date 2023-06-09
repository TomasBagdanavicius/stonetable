<?php

/**
 * Demo page starter.
 *
 * Variables that can be inherited:
 * string $demo_filename Path to file that needs to be demoed
 * string $demo_format Output format, either "html" or "json"
 * string $demo_projects_pathname Path to projects directory
 */

declare(strict_types=1);

namespace Demo;

require_once 'utilities.php';
require_once (LIB_PATH . '/project-directory/Autoload.php');
require_once (LIB_PATH . '/php-code-test-suite/Autoload.php');
require_once (LIB_PATH . '/php-code-test-suite/utilities/utilities.php');

$demo_filename = ( !isset($demo_filename) )
    ? $_SERVER['SCRIPT_FILENAME']
    : realpath($demo_filename);

if( !isset($demo_format) ) {

    if(
        isset($_GET['format'])
        && in_array($_GET['format'], ['json', 'html'])
    ) {
        $demo_format = $_GET['format'];
    } else {
        $demo_format = 'html';
    }
}

use AssertionError;
use PD\PlaygroundFile;
use PD\ProjectRootDirectory;
use PD\StaticFile;
use PD\TestFile;
use PCTS\Debugger\Debugger;
use PCTS\PhpErrors\PhpErrorHandler;
use PCTS\OutputText\OutputTextFormatter;

if( !isset($demo_projects_pathname) ) {

    $dpi_relative_pathname = trim(
        substr($demo_filename, strlen(PROJECTS_PATH)),
        '/\\'
    );
    $separator_pos = strpos($dpi_relative_pathname, DIRECTORY_SEPARATOR);

    $dpi_project_name = ( $separator_pos !== false )
        ? substr($dpi_relative_pathname, 0, $separator_pos)
        : $dpi_relative_pathname;

    $demo_projects_pathname = (PROJECTS_PATH
        . DIRECTORY_SEPARATOR
        . $dpi_project_name);
}

$dpi_project_root_directory = new ProjectRootDirectory($demo_projects_pathname);

define(
    __NAMESPACE__ . '\SRC_PATH',
    $dpi_project_root_directory->source_dirname
);

define(
    __NAMESPACE__ . '\TEST_PATH',
    $dpi_project_root_directory->tests_dirname
);

[$dpi_output_text_formatter, ]
    = $dpi_project_root_directory->produceOutputTextFormatter(
        format_html: true,
        convert_links: is_local_server()
    );

$dpi_debugger = new Debugger(
    formatter: $dpi_output_text_formatter
);

enum ContentFormatEnum {
    case OUTPUT;
    case MESSAGE;
}

function get_assets_url(): string {
    return (get_web_url() . '/assets');
}

function get_stylesheet_url(): string {
    return (get_assets_url() . '/css/output.css');
}

function get_script_url(): string {
    return (get_assets_url() . '/scripts/output.js');
}

$dpi_parts = [];

$dpi_put = function(
    string $contents,
    ContentFormatEnum $content_format
) use( $demo_format, &$dpi_parts ): void {

    if( $demo_format === 'html' ) {

        echo $contents;

    } elseif( $demo_format === 'json' ) {

        $format_readable = match( $content_format ) {
            ContentFormatEnum::OUTPUT => 'output',
            ContentFormatEnum::MESSAGE => 'message',
        };

        $dpi_parts[] = [
            'format' => $format_readable,
            'contents' => $contents,
        ];
    }
};

function echo_if_html( string $contents ): void {

    if( $GLOBALS['demo_format'] === 'html' ) {
        echo $contents;
    }
}

$dpi_json_result_formatter = null;

function register_json_result_formatter( \Closure $formatter ) {
    $GLOBALS['dpi_json_result_formatter'] = $formatter;
}

/* If the "lang" attribute value is the empty string (lang=""), the language is
set to unknown. */
const OUTPUT_ELEM_OPEN_TAG = '<div class="o" lang="">';

function start(
    bool $include_toolbar = true
): void {

    if( ob_get_level() <= 1 ) {

        if( $GLOBALS['demo_format'] === 'html' ) {

            $demo_filename = $GLOBALS['demo_filename'];
            $project_root = $GLOBALS['dpi_project_root_directory'];
            $test_file = $project_root->findByAbsolutePathname($demo_filename);
            $file_tag = match(true) {
                ( $test_file instanceof PlaygroundFile ) => 'playground-file',
                ( str_starts_with(
                    $test_file->getRelativePathname(),
                    'test/units/'
                ) ) => 'unit-file',
                default => 'demo-file',
            };

            echo sprintf(
                '<!DOCTYPE html><html lang="en-US">'
                . '<head><meta charset="utf-8"><title>%s</title>'
                . '<meta name="viewport" content="width=device-width,'
                    . 'initial-scale=1,user-scalable=yes">'
                . '<meta name="robots" content="noindex,nofollow">'
                . '<meta name="referrer" content="no-referrer">'
                . '<link href="%s" rel="stylesheet">'
                . '<link rel="icon" href="%s/%s-favicon.svg"'
                    . ' type="image/svg+xml">'
                . '</head><body>',
                basename($demo_filename),
                get_stylesheet_url(),
                get_assets_url() . '/images',
                $file_tag
            );

            if( $include_toolbar ) {

                $is_local_server = is_local_server();
                $links = [
                    [
                        'url' => $test_file->getParentProjectDirectory()
                            ->getUrl(),
                        'text' => "Go Up",
                    ]
                ];

                if( $is_local_server ) {

                    $links[] = [
                        'url' => $test_file->getIdeUri(),
                        'text' => "Open in IDE",
                    ];
                }

                if( $test_file instanceof StaticFile ) {

                    if( $is_local_server ) {

                        $source_file = $test_file->getSourceFileInstance();

                        if( $source_file ) {

                            $links[] = [
                                'url' => $source_file->getIdeUri(),
                                'text' => "Open Source in IDE",
                            ];
                        }

                        $links[] = [
                            'url' => get_playground_file_create_endpoint(
                                $test_file
                            ),
                            'text' => "Open Playground in IDE",
                            'id' => 'openInPlayground',
                        ];
                    }
                }

                if( $test_file instanceof TestFile ) {

                    $links[] = [
                        'url' => get_app_url(
                            $project_root,
                            main_file: $test_file
                        ),
                        'text' => "Open in App",
                    ];
                }

                if( $links ) {

                    echo '<div class="tb"><nav><ul>';

                    foreach( $links as $link_data ):

                        echo sprintf(
                            '<li><a href="%s"%s>%s</a></li>',
                            $link_data['url'],
                            (( isset($link_data['id']) )
                                ? ' id="' . $link_data['id'] . '"'
                                : '' ),
                            $link_data['text']
                        );

                    endforeach;

                    echo '</ul></nav></div>';
                }
            }
        }

        ob_start();
        echo_if_html(OUTPUT_ELEM_OPEN_TAG);
    }
}

function check_contents( \Closure $putter, bool $end_output = false ): void {

    $contents = ob_get_clean();

    if( $contents && strip_tags($contents) !== '' ) {

        $putter($contents, ContentFormatEnum::OUTPUT);

        if( $end_output ) {
            echo_if_html('<div>');
        }
    }
}

$dpi_error_handler = new PhpErrorHandler(
    debugger: $dpi_debugger,
    output_handler: function( string $contents ) use( $dpi_put ): void {

        $dpi_put($contents, ContentFormatEnum::MESSAGE);

    },
    on_error: function( array $error_data ) use( $dpi_put ): void {

        // Exited before "start" was called in application.
        if( ob_get_length() === 0 ) {
            start();
        }

        echo_if_html('</div>');
        check_contents($dpi_put);

    },
    on_error_end: function( array $error_data ): void {

        ob_start();
        echo_if_html(OUTPUT_ELEM_OPEN_TAG);

    },
    on_before_shutdown: function( ?array $last_error ) use( $dpi_put ): void {

        // Exited before "start" was called in application.
        if( ob_get_length() === 0 ) {
            start();
        }

        echo_if_html('</div>');
        check_contents($dpi_put);

    },
    on_shutdown: function(
        ?array $last_error
    ) use(
        &$dpi_parts,
        $demo_format,
        &$dpi_json_result_formatter,
        $dpi_put,
    ): void {

        if( ob_get_length() !== false ) {
            ob_end_flush();
        }

        check_contents($dpi_put, end_output: true);

        if( $demo_format === 'html' ) {

            echo sprintf(
                '<script src="%s"></script>'
                    . '</body></html>',
                get_script_url()
            );

        } elseif( $demo_format === 'json' ) {

            header('Content-Type: application/json; charset=utf-8');

            try {

                /* Weirdly enough, for unknown reason, an element with a key
                name "path" and value "./this:that" appears in the array.
                Working theory is that PHP or its output buffer is doing this.
                */
                if( array_key_exists('path', $dpi_parts) ) {
                    unset($dpi_parts['path']);
                }

                if( $dpi_json_result_formatter ) {
                    $dpi_parts = $dpi_json_result_formatter($dpi_parts);
                }

                echo json_encode(
                    $dpi_parts,
                    flags: (
                        JSON_THROW_ON_ERROR
                        // Passed through binary data to mimic HTML formats
                        // behavior
                        | JSON_INVALID_UTF8_SUBSTITUTE
                    )
                );

            } catch( \Throwable $exception ) {

                echo json_encode([
                    'status' => 0,
                    'message' => "Could not encode the output to JSON format:"
                        . " {$exception->getMessage()}",
                ]);
            }
        }
    },
    ignore_trace_file: [
        (__DIR__ . DIRECTORY_SEPARATOR . 'api'
        . DIRECTORY_SEPARATOR . 'file-handler.php')
    ]
);

/**
 * Test a value for a true boolean.
 *
 * @param mixed  $value         The value to test.
 * @param string $error_message Error message to pass on failure.
 * @throws \AssertionError When value is not a true type boolean.
 */
function assert_true( mixed $value, string $error_message ): void {

    if( $value !== true ) {
        throw new \AssertionError($error_message, code: 1);
    } else {
        echo 'ok';
    }
}

define(__NAMESPACE__ . '\ERROR_HANDLER', $dpi_error_handler);
define(__NAMESPACE__ . '\DEBUGGER', $dpi_debugger);
