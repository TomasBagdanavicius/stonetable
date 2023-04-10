<?php

/**
 * Abstract PHP language feature contents builder.
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

namespace PCTS\PhpTokens\Language;

use PCTS\PhpTokens\Tokenizer;

abstract class AbstractBuilder {

    /** Initiated, but not building yet. */
    public const STATE_PENDING = 0;

    /** Building in progress. */
    public const STATE_BUILDING = 1;

    /** Finished building - product available. */
    public const STATE_FINISHED = 2;

    /** Current state. */
    protected int $state = self::STATE_PENDING;

    /**
     * @param Tokenizer $tokenizer Tokenizer dependency.
     */
    public function __construct(
        public readonly Tokenizer $tokenizer
    ) {

    }

    /**
     * Accepts a stream of PHP tokens that will eventually build the language
     * feature.
     *
     * @param \PhpToken $token PHP token to feed.
     * @return int|null Null when it has already finished building, otherwise 1.
     */
    abstract public function feed( \PhpToken $token ): ?int;

    /** Gets the product language feature object. */
    abstract public function getProduct(): ?object;

    /**
     * Verifies that language feature can be started building.
     *
     * @param Tokenizer $tokenizer   Tokenizer dependency.
     * @param array     $token_cache Tokenizer's open token cache.
     * @return bool|null "false" implies that there shouldn't be further
     *                   attempts to use token cache, true - verified, null -
     *                   unverified.
     */
    abstract public static function verify(
        Tokenizer $tokenizer,
        array $token_cache = [],
    ): ?bool;

    /**
     * Creates an instance of the builder.
     *
     * @param Tokenizer $tokenizer   Tokenizer dependency.
     * @param array     $token_cache Tokenizer's open token cache.
     * @return self|null Null when cannot verify.
     */
    abstract public static function create(
        Tokenizer $tokenizer,
        array $token_cache = [],
    ): ?self;

    /** State getter. */
    public function getState(): int {

        return $this->state;
    }

    /** Sets the state to building. */
    public function setStateBuilding(): void {

        $this->state = self::STATE_BUILDING;
    }

    /** Sets the state to finished. */
    public function setStateFinished(): void {

        $this->state = self::STATE_FINISHED;
    }
}
