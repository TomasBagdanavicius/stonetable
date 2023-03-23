<?php

/**
 * Anonymous function builder.
 *
 * Requires PHP 8.1 or higher.
 *
 * @package PHP Code Test Suite
 * @author Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpTokens\Language;

use PCTS\PhpTokens\Tokenizer;

class AnonymousFunctionBuilder extends AbstractFunctionBuilder {

    /**
     * @param bool      $has_reference Defines if function has reference symbol.
     * @param Tokenizer $tokenizer     Tokenizer dependency.
     */
    public function __construct(
        bool $has_reference,
        Tokenizer $tokenizer
    ) {

        parent::__construct($has_reference, $tokenizer);
    }

    /** Returns the finished anonymous function object product. */
    public function getProduct(): ?AnonymousFunctionDefinition {

        if( $this->state !== parent::STATE_FINISHED ) {
            return null;
        }

        return new AnonymousFunctionDefinition(
            $this->parameters,
            $this->return_types,
            ($this->compound_statement ?? null)
        );
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

        /* Deny when "function" keyword is used inside a "namespace use
        declaration". */
        if( $tokenizer->isNamespaceUseDeclarationContext() ) {
            return false;
        }

        foreach( $token_cache as $token ) {

            if( $token->is('(') ) {
                return true;
            }
        }

        return null;
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

        if( !self::verify($tokenizer, $token_cache) ) {
            return null;
        }

        $has_reference = false;

        foreach( $token_cache as $key => $token ) {

            if( $token->is(T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG) ) {
                $has_reference = true;
            }
        }

        $instance = new static($has_reference, $tokenizer);
        $instance->params_open_level = $tokenizer->getParenthesesLevel();
        // Go straight into params state.
        $instance->function_state = self::STATE_PARAMS;

        return $instance;
    }
}
