<?php

/**
 * Enhances backtrace and trace info readability.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.4
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PCTS\Debugger;

use PCTS\OutputText\OutputTextFormatter;

class Debugger {

    /**
     * @param OutputTextFormatter $formatter Output text formatter dependency.
     */
    public function __construct(
        public OutputTextFormatter $formatter
    ) {

    }

    /**
     * Generates a readable backtrace info message.
     *
     * @param bool $return Whether to return or echo the resulting message.
     * @param bool $exit   Whether to exit if $return is set to echo the
     *                     resulting message (false).
     */
    public function backtrace(
        bool $return = false,
        bool $exit = true
    ) {

        if( $this->formatter->format_html ) {

            $stack_trace_list = debug_backtrace();

            // Remove last call to this method.
            unset($stack_trace_list[0]);

            $result = sprintf(
                '<article class="msg code-msg">%s</article>',
                $this->richFormatStackTraceList($stack_trace_list)
            );

        } else {

            $trace_string = (new \Exception)->getTraceAsString();

            $result = $this->plainFormatStackTraceString($trace_string);
        }

        if( !$return ) {

            echo $result;

            if( $exit ) {
                exit;
            }

        } else {

            return $result;
        }
    }

    /**
     * HTML formats a given stack trace list.
     *
     * @param array $stack_trace_list A list of arrays with error info.
     * @return string HTML code with the info.
     */
    public function richFormatStackTraceList(
        array $stack_trace_list
    ): string {

        if( !$stack_trace_list ) {
            return '';
        }

        $result = '<p class="h">Stack trace list:</p>';
        $result .= '<ol class="stk-tr-l" reversed>';

        foreach( $stack_trace_list as $stack_trace_data ) {

            $result .= sprintf(
                '<li>%s</li>',
                $this->traceDataToCompactLine($stack_trace_data)
            );
        }

        $result .= '</ol>';

        return $result;
    }

    /**
     * Formats a stack trace string.
     *
     * @param string $trace_string Trace string.
     * @return string Formatted string.
     */
    public function plainFormatStackTraceString(
        string $trace_string
    ): string {

        $parts = explode("\n", $trace_string);

        unset(
            // Remove last call to this method.
            $parts[0],
            // "Main" line.
            $parts[array_key_last($parts)]
        );

        array_walk($parts, function( string &$part ): void {

            $part = preg_replace_callback(
                '/^#(\d+)\s/',
                function( array $matches ): string {
                    return sprintf(
                        "#%d ",
                        ((int)$matches[1] - 1)
                    );
                },
                $part
            );

            $part = $this->formatter->shortenFilenamesAll($part);

        });

        $result = "Stack trace list:\n"
            . implode("\n", $parts)
            . "\n";

        return $result;
    }

    /**
     * Converts an error info trace data into a compact string.
     *
     * @param array $trace_data Error info array.
     * @return string Either a HTML or plain text string, depending on the
     *                "format_html" option value in formatter dependency.
     */
    public function traceDataToCompactLine(
        array $trace_data
    ): string {

        $result = '';
        $filename = $trace_data['file'];
        $formatter = $this->formatter;
        $format = $formatter->format_html;
        $convert_links = $formatter->convert_links;

        if( $format ) {

            $result .= sprintf(
                '<span class="file-path">%s</span>',
                ( $trace_data['class']
                    ?? $formatter->shortenPathname($filename) )
            );

        } else {

            $result .= $formatter->shortenPathname($filename);
        }

        if( !isset($trace_data['class']) && $trace_data['line'] ) {
            $result .= ( $format )
                ? sprintf(
                    '<span class="line-num">:<span class="num">%d</span>'
                        . '</span>',
                    $trace_data['line']
                )
                : (':' . $trace_data['line']);
        }

        $result .= ( $trace_data['type'] ?? ' ' );
        $result .= ( $format )
            ? sprintf(
                '<span class="func-name">%s'
                    . '<span class="punc punc-brkt">(</span>'
                    . '<span class="punc punc-brkt">)</span>'
                    . '</span>',
                $trace_data['function']
            )
            : ($trace_data['function'] . '()');

        if( $format && $convert_links ) {

            $result = $formatter->buildIdeHtmlLink(
                $filename,
                $result,
                ((int)$trace_data['line'] ?? null),
                class_names: ['file']
            );
        }

        $arguments = [];

        if( !empty($trace_data['args']) ) {

            if( $format ) {
                $result .= '<ol class="arg-l">';
            }

            foreach( $trace_data['args'] as $index => $arg ) {

                $allow_html_entities = true;
                $length = null;

                if( is_object($arg) ) {

                    $argument = ( $convert_links )
                        ? $formatter->namespaceToIdeHtmlLink($arg::class)
                        : $arg::class;

                    if( !$argument ) {
                        $argument = $arg::class;
                    }

                    $allow_html_entities = false;

                /* Don't attempt to export and show text for the following
                types, eg. `var_export` will fail on large arrays containing
                objects with circular reference, etc. */
                } elseif( is_array($arg) && is_resource($arg) ) {

                    $argument = '';

                } else {

                    $argument = var_export($arg, return: true);
                }

                // String (can be file path or namespace name).
                if(
                    str_starts_with($argument, '\'')
                    && str_ends_with($argument, '\'')
                ) {

                    $trimmed_arg = trim($argument, '\'');
                    $length = strlen($trimmed_arg);

                    if( $formatter->isQualifiedPathname($trimmed_arg) ) {

                        $argument = $formatter->formatPathname(
                            $trimmed_arg,
                            class_names: ['file']
                        );
                        $allow_html_entities = false;

                    } elseif(
                        $formatter->isQualifiedNamespace($trimmed_arg)
                    ) {

                        $namespace = preg_replace(
                            '#\\\+#',
                            '\\',
                            $trimmed_arg
                        );
                        $argument = ( $convert_links )
                            ? $formatter->namespaceToIdeHtmlLink(
                                $namespace,
                                class_names: ['file']
                            )
                            : $namespace;
                        $allow_html_entities = false;
                    }

                    if( $allow_html_entities ) {

                        $argument = sprintf(
                            '"%s"',
                            ( ( strlen($trimmed_arg) > 25 )
                                ? (substr($trimmed_arg, 0, 25) . '...')
                                : $trimmed_arg )
                        );
                    }
                }

                $var_type = gettype($arg);
                $type = match( $var_type ) {
                    'boolean' => 'bool',
                    'integer' => 'int',
                    'double' => 'float',
                    'NULL' => 'null',
                    default => $var_type,
                };
                $text = $argument;
                $no_text_types = [
                    'null',
                    'array',
                    'resource',
                ];

                if( $format ) {

                    $result .= sprintf(
                        '<li class="%s">',
                        $type
                    );
                    $result .= sprintf(
                        '<code class="code-php"><span class="type">%s</span>',
                        $type
                    );
                    $code_open = true;

                } else {

                    $result .= ("\n\t" . ($index + 1) . '. ' . $type);
                }

                $info_types = [
                    'string',
                    'array',
                    'int',
                    'float',
                    'object',
                    'resource',
                ];

                if( in_array($type, $info_types) ) {

                    $value = $trace_data['args'][$index];

                    $info = match( $type ) {
                        'string' => $length,
                        'float' => strlen((string)(int)$value),
                        'int' => strlen((string)$value),
                        'object' => ('#' . spl_object_id($value)),
                        'resource' => get_resource_type($value),
                        default => count($value),
                    };

                    if( $type === 'float' ) {
                        $info .= ','
                            . strlen(explode('.', (string)$value, 2)[1]);
                    }

                    if( $format ) {

                        $result .= '</code>';
                        $code_open = false;

                        $result .= sprintf(
                            '<span class="info">'
                                . '<span class="punc punc-brkt">(</span>'
                                . '%s'
                                . '<span class="punc punc-brkt">)</span>'
                                . '</span>',
                            $info
                        );

                    } else {

                        $result .= ('(' . $info . ')');
                    }
                }

                if( !in_array($type, $no_text_types) ) {

                    if( !$allow_html_entities ) {

                        $value = $text;

                    } else {

                        $needle = 'array ';

                        if( !str_starts_with($text, $needle) ) {
                            $value = htmlentities($text);
                        } else {
                            $value = 'array';
                        }
                    }

                    if( $format) {

                        if( $type === 'bool' ) {

                            $result .= sprintf(
                                ' <span class="type">%s</span>',
                                $value
                            );

                        } else {

                            $result .= sprintf(
                                ' <span class="text">%s</span>',
                                $value
                            );
                        }

                        if( $code_open ) {
                            $result .= '</code>';
                            $code_open = false;
                        }

                        $result .= '</li>';

                    } else {

                        $result .= (' ' . $value);
                    }
                }

                if( $code_open ) {
                    $result .= '</code>';
                    $code_open = false;
                }
            }

            if( $format ) {
                $result .= '</ol>';
            }
        }

        return $result;
    }
}
