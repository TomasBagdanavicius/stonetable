<?php

/**
 * Enhances PHP error reporting and readability.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.3
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpErrors;

use PCTS\OutputText\OutputTextFormatter;
use PCTS\Debugger\Debugger;

class PHPErrorHandler {

    /** Defines the output text formatter to be used. */
    public OutputTextFormatter $formatter;

    /** Defines whether resulting string should be formatted in HTML. */
    public bool $format_html;

    /**
     * @param Debugger      $debugger           Debugger dependency.
     * @param \Closure      $output_handler     A callback function that will
     *                                          receive the formatted output.
     * @param \Closure|null $on_error           A callback function that will be
     *                                          run on error.
     * @param \Closure|null $on_error_end       Similar to $on_error, but will
     *                                          run after $output_handler has
     *                                          run.
     * @param \Closure|null $on_shutdown        A callback function that will be
     *                                          run at the end of shutdown.
     * @param \Closure|null $on_before_shutdown A callback function that will be
     *                                          run at the beginning of
     *                                          shutdown.
     * @param array         $ignore_trace_file  A list of file paths to exclude
     *                                          from trace info.
     */
    public function __construct(
        public Debugger $debugger,
        public \Closure $output_handler,
        ?\Closure $on_error = null,
        ?\Closure $on_error_end = null,
        ?\Closure $on_shutdown = null,
        ?\Closure $on_before_shutdown = null,
        public readonly array $ignore_trace_file = []
    ) {

        // Prevent duplicate printing fatal errors.
        error_reporting(0);

        $this->formatter = $this->debugger->formatter;
        $this->format_html = $this->debugger->formatter->format_html;

        set_error_handler(function(
            int $error_number,
            string $message,
            string $filename,
            int $line_number
        ) use(
            $on_error,
            $on_error_end,
        ): bool {

            $error_array = [
                'message' => $message,
                'filename' => $filename,
                'line_number' => $line_number,
                'error_category' => 'error',
            ];

            if( $on_error ) {
                $on_error($error_array);
            }

            ($this->output_handler)($this->formatErrorMessage(
                $message,
                $filename,
                $line_number,
                $error_number
            ));

            if( $on_error_end ) {
                $on_error_end($error_array);
            }

            return true;

        });

        set_exception_handler(function(
            \Throwable $exception
        ) use(
            $on_error,
            $on_error_end,
        ): void {

            $error_array = [
                'message' => $exception->getMessage(),
                'filename' => $exception->getFile(),
                'line_number' => $exception->getLine(),
                'error_category' => 'exception',
            ];

            if( $on_error ) {
                $on_error($error_array);
            }

            ($this->output_handler)(
                $this->formatException($exception)
            );

            if( $on_error_end ) {
                $on_error_end($error_array);
            }

        });

        register_shutdown_function(
            function(
                ?\Closure $on_shutdown,
                ?\Closure $on_before_shutdown
            ): void {

                $last_error = error_get_last();

                if( $on_before_shutdown ) {
                    $on_before_shutdown($last_error);
                }

                if( $last_error ) {

                    ($this->output_handler)($this->formatErrorMessage(
                        $last_error['message'],
                        $last_error['file'],
                        $last_error['line'],
                        $last_error['type']
                    ));
                }

                if( $on_shutdown ) {
                    $on_shutdown($last_error);
                }
            },
            $on_shutdown,
            $on_before_shutdown
        );
    }

    /**
     * Formats an error message.
     *
     * @param string   $message     Error message string.
     * @param string   $filename    Path to file where error occured.
     * @param int      $line_number Line number in the file.
     * @param int|null $error_type  Optionally, error type.
     * @return string Formatted string based on the formatting type that was set
     *                in output text formatter dependency.
     */
    public function formatErrorMessage(
        string $message,
        string $filename,
        int $line_number,
        ?int $error_type = null
    ): string {

        if( $this->format_html ) {

            return $this->richFormatErrorMessage(
                $message,
                $filename,
                $line_number,
                $error_type
            );

        } else {

            return $this->plainFormatErrorMessage(
                $message,
                $filename,
                $line_number,
                $error_type
            );
        }
    }

    /** Builds HTML opening tag for a given error type. */
    public function buildMessageOpeningTag( int $error_type ): string {

        $type_map = $this->getErrorTypeMap();
        $code_msg = 'code-msg';

        $class_names = [
            'msg',
            $code_msg,
        ];

        $class_names[] = match($type_map[$error_type][2]) {
            ErrorCategoryEnum::ERROR => ($code_msg . '-err'),
            ErrorCategoryEnum::WARNING => ($code_msg . '-warn'),
            ErrorCategoryEnum::NOTICE => ($code_msg . '-note'),
            ErrorCategoryEnum::DEPRECATED => ($code_msg . '-dep'),
        };

        return sprintf(
            '<article class="%s">',
            implode(' ', $class_names)
        );
    }

    /**
     * HTML formats error message.
     *
     * @param string   $message         Error message text.
     * @param string   $filename        Filename where error occured.
     * @param int      $line_number     Line number of error location.
     * @param int|null $error_type      Error type.
     * @param bool     $close_html_tags Whether return HTML string should close
     *                                  tags of wrappers.
     * @return string HTML formatter error message.
     */
    public function richFormatErrorMessage(
        string $message,
        string $filename,
        int $line_number,
        ?int $error_type = null,
        bool $close_html_tags = true
    ): string {

        $result = $this->buildMessageOpeningTag($error_type);
        $result .= sprintf(
            '<p class="text"><strong>%s</strong></p>',
            $this->formatMessageText(
                $this->formatter->format($message)
            )
        );
        $result .= '<dl>';
        $result .= '<dt>Location</dt>';
        $line_number_suffix = sprintf(
            '<span class="line-num">:<span class="num">%d</span></span>',
            $line_number
        );
        $result .= sprintf(
            '<dd>%s</dd>',
            $this->formatter->formatPathname(
                $filename,
                $line_number_suffix,
                $line_number,
                class_names: ['file']
            )
        );

        if( $error_type !== null ) {

            $result .= '<dt>Type</dt>';
            $result .= sprintf(
                '<dd>%s</dd>',
                self::errorTypeToString($error_type)
            );
        }

        if( $close_html_tags ) {

            $result .= '</dl>';
            $result .= '</article>';
        }

        return $result;
    }

    /** Plain text formats error message. */
    public function plainFormatErrorMessage(
        string $message,
        string $filename,
        int $line_number,
        ?int $error_type = null
    ): string {

        $result = self::errorTypeToReadableString($error_type)
            . ': ';
        $result .= $message;
        $result .= "\n";
        $result .= $this->formatter->formatPathname(
            $filename,
            ':' . $line_number
        );
        $result .= "\n";

        return $result;
    }

    /** Builds an error message from a given throwable object. */
    public function formatException(
        \Throwable $exception
    ): string {

        return ( $this->format_html )
            ? $this->richFormatException($exception)
            : $this->plainFormatException($exception);
    }

    /** Builds a HTML error message from a given throwable object. */
    public function richFormatException(
        \Throwable $exception
    ): string {

        $result = $this->richFormatErrorMessage(
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            error_type: E_RECOVERABLE_ERROR,
            close_html_tags: false
        );

        $exception_namespace = $exception::class;

        $result .= '<dt>Error Class</dt>';
        $result .= '<dd>';

        if( $this->formatter->isQualifiedNamespace($exception_namespace) ) {

            $result .= $this->formatter->namespaceToIdeHtmlLink(
                $exception_namespace
            );

        } else {

            $result .= $exception_namespace;
        }

        $result .= '</dd>';
        $result .= '<dt>Error Code</dt>';
        $result .= '<dd>';
        $result .= $exception->getCode();
        $result .= '</dd>';
        $result .= '</dl>';

        if( $trace_list = $exception->getTrace() ) {

            $trace_data_str = '';

            foreach( $trace_list as $trace_data ) {

                if( !in_array($trace_data['file'], $this->ignore_trace_file) ) {

                    $trace_data_str .= sprintf(
                        '<li>%s</li>',
                        $this->debugger->traceDataToCompactLine(
                            $trace_data,
                            true
                        )
                    );
                }
            }

            if( $trace_data_str ) {

                $result .= '<p class="h">Stack trace list:</p>';
                $result .= '<ol class="stk-tr-l" reversed>';
                $result .= $trace_data_str;
                $result .= '</ol>';
            }
        }

        $previous_error = $exception->getPrevious();

        if( $previous_error ) {
            $result .= $this->formatException($previous_error);
        }

        $result .= '</article>';

        return $result;
    }

    /** Builds a plain text error message from a given throwable object. */
    public function plainFormatException(
        \Throwable $exception
    ): string {

        $exception_string = $exception->__toString();

        $exception_string = $this->formatter->shortenFilenamesAll(
            $exception_string
        );

        return $exception_string;
    }

    /** Formats error message text. */
    public function formatMessageText( string $text ): string {

        // Wrap variables into the code tag.
        return preg_replace(
            '/\$+[a-zA-Z0-9_]+/',
            '<code class="php"><span class="var">$0</span></code>',
            $text
        );
    }

    /**
     * Returns a data map where each PHP error type is mapped to additional info
     * about that error.
     */
    public static function getErrorTypeMap(): array {

        return [
            E_ERROR => [
                "E_ERROR",
                "Fatal error",
                ErrorCategoryEnum::ERROR,
            ],
            E_WARNING => [
                "E_WARNING",
                "Warning",
                ErrorCategoryEnum::WARNING,
            ],
            E_PARSE => [
                "E_PARSE",
                "Parse error",
                ErrorCategoryEnum::ERROR,
            ],
            E_NOTICE => [
                "E_NOTICE",
                "Notice",
                ErrorCategoryEnum::NOTICE,
            ],
            E_CORE_ERROR => [
                "E_CORE_ERROR",
                "Fatal error",
                ErrorCategoryEnum::ERROR,
            ],
            E_CORE_WARNING => [
                "E_CORE_WARNING",
                "Warning",
                ErrorCategoryEnum::WARNING,
            ],
            E_COMPILE_ERROR => [
                "E_COMPILE_ERROR",
                "Fatal error",
                ErrorCategoryEnum::ERROR,
            ],
            E_COMPILE_WARNING => [
                "E_COMPILE_WARNING",
                "Warning",
                ErrorCategoryEnum::WARNING,
            ],
            E_USER_ERROR => [
                "E_USER_ERROR",
                "Fatal error",
                ErrorCategoryEnum::ERROR,
            ],
            E_USER_WARNING => [
                "E_USER_WARNING",
                "Warning",
                ErrorCategoryEnum::WARNING,
            ],
            E_USER_NOTICE => [
                "E_USER_NOTICE",
                "Notice",
                ErrorCategoryEnum::NOTICE,
            ],
            E_STRICT => [
                "E_STRICT",
                "Error",
                ErrorCategoryEnum::ERROR,
            ],
            E_RECOVERABLE_ERROR => [
                "E_RECOVERABLE_ERROR",
                "Error",
                ErrorCategoryEnum::ERROR,
            ],
            E_DEPRECATED => [
                "E_DEPRECATED",
                "Deprecated",
                ErrorCategoryEnum::DEPRECATED,
            ],
            E_USER_DEPRECATED => [
                "E_USER_DEPRECATED",
                "Deprecated",
                ErrorCategoryEnum::DEPRECATED,
            ],
            E_ALL => [
                "E_ALL",
                "All",
                ErrorCategoryEnum::ERROR,
            ],
        ];
    }

    /**
     * Translates PHP error type integer into a readable constant string, eg.
     * "E_ERROR".
     */
    public static function errorTypeToString(
        int $error_type
    ): ?string {

        $type_map = self::getErrorTypeMap();

        if( !isset($type_map[$error_type]) ) {
            return null;
        }

        return $type_map[$error_type][0];
    }

    /**
     * Translates PHP error type integer into a readable string, eg. "Fatal
     * error".
     */
    public static function errorTypeToReadableString(
        int $error_type
    ): string {

        $type_map = self::getErrorTypeMap();

        if( !isset($type_map[$error_type]) ) {
            return null;
        }

        return $type_map[$error_type][1];
    }
}
