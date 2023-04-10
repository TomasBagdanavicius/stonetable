<?php

/**
 * Handles an included file by capturing its output and error info.
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

require_once 'AbstractHandler.php';
require_once (__DIR__ . '/../PhpErrors/ErrorClassEnum.php');

class IncludeHandler extends AbstractHandler {

    /** Path name to file that is being executed. */
    private ?string $filename_executing = null;

    public function __construct() {

        parent::__construct();
    }

    /**
     * Executes a given include file and provides info about errors and
     * outputs.
     */
    public function execute(
        string $filename
    ): array {

        if( !file_exists($filename) ) {

            throw new RuntimeException(
                "File $filename was not found"
            );
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if( $extension !== 'php' ) {

            throw new RuntimeException(
                "File $filename is not a PHP file"
            );
        }

        $filename = realpath($filename);

        $this->filename_executing = $filename;

        $this->beginExecuting();

        ob_start();

        try {

            // Isolate from outside environment.
            (static function() use( $filename ): void {

                include $filename;

            })();

        /* Not all issues can be caught, eg. notices or warnings. */
        } catch( Throwable $exception ) {

            $this->addToErrorBuffer(
                ErrorClassEnum::EXCEPTION,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                exception_class: $exception::class,
            );

        } finally {

            $output = ob_get_contents();
            ob_end_clean();

            $this->filename_executing = null;
            $this->endExecuting();
        }

        $result = [];

        if( $this->error_buffer ) {

            $result['status'] = 0;
            $result['errors'] = $this->error_buffer;

        } else {

            $result['status'] = 1;
        }

        if( isset($output) && $output ) {
            $result['output'] = $output;
        }

        return $result;
    }
}
