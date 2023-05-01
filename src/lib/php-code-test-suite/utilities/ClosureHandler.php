<?php

/**
 * Handles a closure by capturing its output and error info.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.5
 * @since     1.0.0
 */

declare(strict_types=1);

require_once 'AbstractHandler.php';
require_once (__DIR__ . '/../PhpErrors/ErrorClassEnum.php');

use PCTS\PhpErrors\ErrorClassEnum;

class ClosureHandler extends AbstractHandler {

    public function __construct() {

        parent::__construct();
    }

    /** Executes a given closure and provides info about errors and outputs. */
    public function execute(
        \Closure $closure
    ): array {

        $this->beginExecuting();

        ob_start();

        try {

            $response = $closure();

        } catch( Throwable $exception ) {

            $this->addToErrorBuffer(
                ErrorClassEnum::EXCEPTION,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                exception_class: $exception::class
            );

        } finally {

            $output = ob_get_contents();
            ob_end_clean();

            $this->endExecuting();
        }

        $result = [];

        if( $this->error_buffer ) {

            $result['status'] = 0;
            $result['errors'] = $this->error_buffer;

            $this->flushErrorBuffer();

        } else {

            $result['status'] = 1;
        }

        if( isset($response) ) {
            $result['result'] = $response;
        }

        if( isset($output) && $output ) {
            $result['output'] = $output;
        }

        return $result;
    }
}
