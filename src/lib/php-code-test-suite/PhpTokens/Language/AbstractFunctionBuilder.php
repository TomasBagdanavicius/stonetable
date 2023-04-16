<?php

/**
 * Abstract PHP function language feature contents builder.
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

abstract class AbstractFunctionBuilder extends AbstractBuilder {

    /**
     * Function definition header has been initialized.
     * @see https://phplang.org/spec/13-functions.html#function-definitions
     */
    public const STATE_INIT = 1;

    /** Function definition header has been closed. */
    public const STATE_CLOSED = 0;

    /** Function definition header in params phase. */
    public const STATE_PARAMS = 2;

    /** Function definition header in post-params state. */
    public const STATE_POST_PARAMS = 3;

    /** Tracks the function definition header building state. */
    public int $function_state = self::STATE_INIT;

    /** Array with parameters. */
    public array $parameters = [];

    /** Level of the bracket that opened parameters. */
    protected int $params_open_level;

    /** Is currently closing parameters with a ")" bracket. */
    protected bool $is_closing_params = false;

    /** Tells if currently in union types phase. */
    public bool $is_union_types = false;

    /** A list of return types. */
    public array $return_types = [];

    /** String representing the use variable name list. */
    public string $use_string;

    /** Function's compound statement. */
    public readonly CompoundStatement $compound_statement;

    /** Compound statement open level. */
    public readonly int $compound_statement_open_level;

    /** PHP token that opened the compound statement. */
    public readonly \PhpToken $compound_statement_open_token;

    /**
     * @param bool      $has_reference Defines if function has reference symbol.
     * @param Tokenizer $tokenizer     Tokenizer dependency.
     */
    public function __construct(
        public readonly bool $has_reference,
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

        $tokenizer = $this->tokenizer;

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

        } elseif(
            $this->function_state < self::STATE_POST_PARAMS
            && !$this->is_closing_params
        ) {

            if(
                $this->function_state === self::STATE_INIT
                && $token->is('(')
            ) {

                $this->params_open_level = $tokenizer->getParenthesesLevel();
                $this->function_state = self::STATE_PARAMS;

            } elseif(
                $this->function_state === self::STATE_PARAMS
                && $token->is(')')
                && $this->params_open_level
                    === $tokenizer->getParenthesesLevel()
            ) {

                $this->is_closing_params = true;

            } else {

                $is_param_separator = $token->is(',');
                $is_level_match = (
                    $this->params_open_level
                        === $tokenizer->getUniversalLevel()
                );

                if(
                    // No parameters yet.
                    !$this->parameters
                    // Next parameter.
                    || ($is_param_separator && $is_level_match)
                ) {

                    $this->parameters[] = [
                        0 => [],
                    ];
                }

                if( !$is_param_separator || !$is_level_match ) {

                    $last_key = array_key_last($this->parameters);
                    $ref = &$this->parameters[$last_key];

                    if(
                        !isset($ref[1])
                        && $token->is([
                            T_STRING,
                            T_ARRAY,
                            T_NAME_QUALIFIED,
                            T_NAME_FULLY_QUALIFIED,
                            '?'
                        ])
                    ) {

                        $this->is_union_types = true;

                        $ref[0][] = $token->text;

                    } elseif( $token->is(T_VARIABLE) ) {

                        $this->is_union_types = false;

                        $ref[1] = $token->text;

                    } elseif( isset($ref[1]) ) {

                        if( !isset($ref[2]) ) {

                            if( !$token->is(['=', T_WHITESPACE]) ) {
                                $ref[2] = $token->text;
                            }

                        } else {

                            $ref[2] .= $token->text;
                        }
                    }
                }
            }

        } else {

            $this->function_state = self::STATE_POST_PARAMS;
            $this->is_closing_params = false;

            if( $token->is('=>') ) {

                $this->setStateFinished();

                return 0;

            } elseif( $token->is('{') ) {

                $this->function_state = self::STATE_CLOSED;
                $this->is_union_types = false;

                $this->compound_statement_open_level
                    = $tokenizer->getCurlyBracketsLevel();
                $this->compound_statement_open_token = $token;

            } elseif( $token->is(':') ) {

                $this->is_union_types = true;

            } elseif( $token->is(T_USE) || !$this->is_union_types ) {

                if( !isset($this->use_string) ) {
                    $this->use_string = $token->text;
                } else {
                    $this->use_string .= $token->text;
                }

            } elseif( !$token->is(':') ) {

                $this->is_union_types = true;

                if( !$token->is(['|', T_WHITESPACE]) ) {
                    $this->return_types[] = $token->text;
                }
            }
        }

        return 1;
    }
}
