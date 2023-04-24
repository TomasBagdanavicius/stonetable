<?php

/**
 * Trait for classes requiring an "on finished" hook.
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

namespace PCTS\PhpTokens\Language;

trait OnFinishedHookTrait {

    /** A list of callbacks. */
    protected array $on_finished_callbacks = [];

    /** Callback accepter. */
    public function onFinished( \Closure $callback ): int {

        $this->on_finished_callbacks[] = $callback;

        return array_key_last($this->on_finished_callbacks);
    }

    /**
     * Callback shooter.
     *
     * @param mixed $payload Payload to pass over to each callback.
     */
    public function fireOnFinishedCallbacks( mixed $payload ): void {

        foreach( $this->on_finished_callbacks as $callback ) {
            $callback($payload);
        }
    }
}
