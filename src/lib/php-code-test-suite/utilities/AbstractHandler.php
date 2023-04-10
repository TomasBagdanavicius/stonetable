<?php

/**
 * Abstract entity handler to capture output and error info.
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

require_once (__DIR__ . '/../PhpErrors/ErrorClassEnum.php');

use PCTS\PhpErrors\ErrorClassEnum;

abstract class AbstractHandler {

    /** Tells if it's currently executing. */
    protected bool $is_executing = false;

    /** Saved error reporting setting. */
    protected int $original_error_reporting;

    /** A list of error info groups. */
    protected array $error_buffer = [];

    public function __construct(
        ?Closure $on_shutdown = null
    ) {

        set_error_handler(function(
            int $error_level,
            string $message,
            string $filename,
            int $line_number
        ): bool {

            if( $this->is_executing ) {

                $this->addToErrorBuffer(
                    ErrorClassEnum::ERROR,
                    $message,
                    $filename,
                    $line_number,
                    error_level: $error_level
                );
            }

            return true;

        });
    }

    /** Begins the execution phase. */
    public function beginExecuting(): bool {

        $this->original_error_reporting = error_reporting();
        error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);

        $this->is_executing = true;

        return true;
    }

    /** Ends the execution phase. */
    public function endExecuting(): bool {

        error_reporting($this->original_error_reporting);

        $this->is_executing = false;

        return true;
    }

    /** Tells if the execution phase is running. */
    public function isExecuting(): bool {

        return $this->is_executing;
    }

    /** Adds error to the error buffer. */
    public function addToErrorBuffer(
        ErrorClassEnum $error_class,
        string $message,
        string $filename,
        int $line_number,
        ?int $error_level = null,
        ?string $exception_class = null
    ): array {

        $result = [
            'error_class' => $error_class,
            'message' => $message,
            'filename' => $filename,
            'line_number' => $line_number,
        ];

        if( $error_level !== null ) {
            $result['error_level'] = $error_level;
        }

        if( $exception_class !== null ) {
            $result['exception_class'] = $exception_class;
        }

        $this->error_buffer[] = $result;

        return $result;
    }

    /** Truncates the error buffer. */
    public function flushErrorBuffer(): void {

        $this->error_buffer = [];
    }
}
