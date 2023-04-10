<?php

/**
 * Namespace definition builder.
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

class NamespaceDefinitionBuilder extends AbstractBuilder {

    /** Captured namespace name. */
    public readonly string $namespace_name;

    /** Namespace's compound statement. */
    public readonly CompoundStatement $compound_statement;

    /** Level at which compound statement was opened. */
    public readonly int $compound_statement_open_level;

    /** PHP token that was used to open compound statement. */
    public readonly \PhpToken $compound_statement_open_token;

    /**
     * A list of callback functions to be fired when namespace name is captured.
     */
    public array $on_namespace_name_callbacks = [];

    /**
     * @param Tokenizer $tokenizer Tokenizer dependency.
     */
    public function __construct(
        Tokenizer $tokenizer
    ) {

        parent::__construct($tokenizer);
    }

    /**
     * Accepts a stream of PHP tokens that will eventually build the language
     * feature.
     *
     * @param \PhpToken $token PHP token to feed.
     * @return int|null Null when it has already finished building, otherwise 1.
     */
    public function feed( \PhpToken $token ): ?int {

        if( $this->state === parent::STATE_FINISHED ) {
            return null;
        }

        $this->setStateBuilding();

        if( !$token->is([T_WHITESPACE, T_WHITESPACE_LINE_BREAK]) ) {

            /* First condition for optimization purposes: this potentially will
            be true the most of the time. */
            if( isset($this->compound_statement_open_level) ) {

                // Compound statement closing.
                if(
                    $token->is('}')
                    && $this->compound_statement_open_level
                        === $this->tokenizer->getCurlyBracketsLevel()
                ) {

                    $this->compound_statement = new CompoundStatement(
                        $this->compound_statement_open_level,
                        $this->compound_statement_open_token,
                        $token
                    );

                    $this->setStateFinished();
                    return 0;
                }

            // Namespace name.
            } elseif( !isset($this->namespace_name) ) {

                $this->namespace_name = $token->text;
                $this->fireOnNamespaceNameCallbacks($this->namespace_name);

            // Semicolon closing punctuation.
            } elseif( $token->is(';') ) {

                $this->setStateFinished();
                return 0;

            // Compound statement opening.
            } elseif(
                !isset($this->compound_statement_open_level)
                && $token->is('{')
            ) {

                $this->compound_statement_open_level
                    = $this->tokenizer->getCurlyBracketsLevel();
                $this->compound_statement_open_token = $token;
            }
        }

        return 1;
    }

    /** Gets the product language feature object. */
    public function getProduct(): ?NamespaceDefinition {

        if( $this->state !== parent::STATE_FINISHED ) {
            return null;
        }

        return new NamespaceDefinition(
            $this->namespace_name,
            ($this->compound_statement ?? null)
        );
    }

    /** Hooks that works when namespace name is captured. */
    public function onNamespaceName( \Closure $callback ): int {

        $this->on_namespace_name_callbacks[] = $callback;

        return array_key_last($this->on_namespace_name_callbacks);
    }

    /** Fires all callbacks when given namespace name is captured. */
    public function fireOnNamespaceNameCallbacks(
        string $namespace_name
    ): void {

        foreach( $this->on_namespace_name_callbacks as $callback ) {
            $callback($namespace_name);
        }
    }

    /**
     * Verifies that language feature can be started building.
     *
     * @param Tokenizer $tokenizer   Tokenizer dependency.
     * @param array     $token_cache Tokenizer's open token cache.
     * @return bool|null "false" implies that there shouldn't be further
     *                   attempts to use token cache, true - verified, null -
     *                   unverified.
     */
    public static function verify(
        Tokenizer $tokenizer,
        array $token_cache = []
    ): ?bool {

        /* Except for white space and declare-statement, the first occurrence of
        a namespace-definition in a script must be the first thing in that
        script. */
        return ( $tokenizer->getCurlyBracketsLevel() === 0 );
    }

    /**
     * Creates an instance of the builder.
     *
     * @param Tokenizer $tokenizer   Tokenizer dependency.
     * @param array     $token_cache Tokenizer's open token cache.
     * @return self|null Null when cannot verify.
     */
    public static function create(
        Tokenizer $tokenizer,
        array $token_cache = []
    ): ?self {

        return new self($tokenizer);
    }
}
