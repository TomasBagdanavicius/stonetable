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
 * @version   1.0.2
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
        $result .= '<ol reversed>';

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

            $result .= ( $trace_data['class']
                ?? $formatter->shortenPathname($filename) );

        } else {

            $result .= $filename;

            if( $trace_data['line'] ) {
                $result .= (':' . $trace_data['line']);
            }
        }

        $result .= ( $trace_data['type'] ?? ' ' );
        $result .= $trace_data['function'];

        if( $format && $convert_links ) {

            $result = $formatter->buildIdeHtmlLink(
                $filename,
                $result,
                ((int)$trace_data['line'] ?? null)
            );
        }

        $arguments = [];

        if( !empty($trace_data['args']) ) {

            foreach( $trace_data['args'] as $arg ) {

                $allow_html_entities = true;

                if( is_object($arg) ) {

                    $argument = ( $convert_links )
                        ? $formatter->namespaceToIdeHtmlLink($arg::class)
                        : $arg::class;

                    if( !$argument ) {
                        $argument = $arg::class;
                    }

                    $allow_html_entities = false;

                } else {

                    $argument = var_export($arg, return: true);
                }

                if(
                    str_starts_with($argument, '\'')
                    && str_ends_with($argument, '\'')
                ) {

                    $trimmed_arg = trim($argument, '\'');

                    if( $formatter->isQualifiedPathname($trimmed_arg) ) {

                        $argument = $formatter->formatPathname(
                            $trimmed_arg
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
                            ? $formatter->namespaceToIdeHtmlLink($namespace)
                            : $namespace;

                        $allow_html_entities = false;
                    }
                }

                $arguments[] = [
                    'type' => gettype($arg),
                    'text' => $argument,
                    'allow_html_entities' => $allow_html_entities,
                ];
            }
        }

        if( $arguments ) {

            if( $format ) {

                $result .= '<ol class="arg-l">';

                foreach( $arguments as $argument ) {

                    $result .= '<li>';
                    $result .= sprintf(
                        '<span class="type">%s</span>'
                            . ' ',
                        $argument['type']
                    );

                    if(
                        $argument['type'] !== 'NULL'
                        && $argument['type'] !== 'array'
                    ) {

                        if( !$argument['allow_html_entities'] ) {

                            $text = $argument['text'];

                        } else {

                            $needle = 'array ';

                            if( !str_starts_with($argument['text'], $needle) ) {
                                $text = htmlentities($argument['text']);
                            } else {
                                $text = 'array';
                            }
                        }

                        $result .= sprintf(
                            '<span class="text">%s</span>',
                            $text
                        );
                    }

                    $result .= '</li>';
                }

                $result .= '</ol>';

            } else {

                $result .= '(';
                $i = 0;

                foreach( $arguments as $argument ) {

                    if( $i ) {
                        $result .= ', ';
                    }

                    $result .= $argument['text'];

                    $i++;
                }

                $result .= ')';
            }
        }

        return $result;
    }
}
