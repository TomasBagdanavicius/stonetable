<?php

/**
 * Function definition builder.
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

namespace PCTS\PhpTokens\Language;

use PCTS\PhpTokens\Tokenizer;

class FunctionDefinitionBuilder extends AbstractFunctionBuilder {

    /**
     * @param string    $function_name Function name.
     * @param bool      $has_reference Defines if function has reference symbol.
     * @param Tokenizer $tokenizer     Tokenizer dependncy.
     */
    public function __construct(
        public readonly string $function_name,
        bool $has_reference,
        Tokenizer $tokenizer
    ) {

        parent::__construct($has_reference, $tokenizer);
    }

    /** Gets the product language feature object. */
    public function getProduct(): ?FunctionDefinition {

        if( $this->state !== parent::STATE_FINISHED ) {
            return null;
        }

        return new FunctionDefinition(
            $this->function_name,
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

            if( $token->is(T_STRING) ) {
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

            if( $token->is(T_STRING) ) {
                $name_key = $key;
                break;
            }

            if( $token->is(T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG) ) {
                $has_reference = true;
            }
        }

        return new self(
            $token_cache[$name_key]->text,
            $has_reference,
            $tokenizer
        );
    }
}
