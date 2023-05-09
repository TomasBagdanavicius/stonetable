<?php

/**
 * Namespace use declaration builder.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.6
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpTokens\Language;

use PCTS\PhpTokens\Tokenizer;
use PCTS\PhpTokens\PhpTokenCategoryEnum;
use PCTS\PhpTokens\Language\NamespaceUseDeclarationTypeEnum as NUDTE;

class NamespaceUseDeclarationBuilder extends AbstractBuilder implements
    CategoryHintInterface
{

    /** All captured data. */
    protected array $data = [];

    /** Hint about the current token category. */
    public ?PhpTokenCategoryEnum $category_hint = null;

    use OnFinishedHookTrait;

    /**
     * @param Tokenizer $tokenizer Tokenizer dependency.
     */
    public function __construct(
        Tokenizer $tokenizer
    ) {

        parent::__construct($tokenizer);
    }

    /** Captured data getter. */
    public function getData(): array {

        return $this->data;
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

        $this->category_hint = null;

        $this->setStateBuilding();

        if( !$token->is([T_WHITESPACE, T_WHITESPACE_LINE_BREAK]) ) {

            // No data collected yet.
            if( !$this->data ) {

                // Leading "function" keyword.
                if( $token->is(T_FUNCTION) ) {
                    $type = NamespaceUseDeclarationTypeEnum::FUNCTION;
                // Leading "const" keyword.
                } elseif( $token->is(T_CONST) ) {
                    $type = NamespaceUseDeclarationTypeEnum::CONSTANT;
                // Mixed namespace.
                } else {

                    /* Either "Vendor" or "Vendor\". Make a hint, because a look
                    behind might identify it as a class. */
                    if(
                        $token->is(T_STRING)
                        && $this->hasBlockStatementLookAhead()
                    ) {
                        $this->category_hint
                            = PhpTokenCategoryEnum::GENERIC_STRING;
                    }

                    $this->data[] = [
                        'name' => $token->text,
                    ];
                }

                if( isset($type) ) {
                    $this->data[] = [
                        'type' => $type,
                    ];
                }

            // Primary data has been collected.
            } else {

                if( $token->is(';') ) {

                    $this->fireOnFinishedCallbacks($this->data);
                    $this->setStateFinished();

                    return 0;
                }

                // Reference to the last data element.
                $data_last_key = array_key_last($this->data);
                $data_last_ref =& $this->data[$data_last_key];

                if( !isset($data_last_ref['name']) ) {

                    $data_last_ref['name'] = $token->text;

                    // Inherit type.
                    if(
                        $data_last_key > 0
                        && isset($this->data[0]['type'])
                    ) {
                        $data_last_ref['type'] = $this->data[0]['type'];
                    }

                } else {

                    // Start the group.
                    if( $token->is('{') ) {

                        $data_last_ref['group'] = [];

                    // End the group.
                    } elseif( $token->is('}') ) {

                        $group_ref =& $data_last_ref['group'];

                        // Cleanup after trailing comma.
                        if( !$group_ref[array_key_last($group_ref)] ) {
                            unset($group_ref[array_key_last($group_ref)]);
                        }

                    // Inside group.
                    } elseif( isset($data_last_ref['group']) ) {

                        $g_ref =& $data_last_ref['group'];

                        if(
                            // Empty group.
                            !$g_ref
                            // Empty last element in group.
                            || !($le_ref =& $g_ref[array_key_last($g_ref)])
                        ) {

                            $type = match(true) {
                                $token->is(T_FUNCTION) => NUDTE::FUNCTION,
                                $token->is(T_CONST) => NUDTE::CONSTANT,
                                default => NUDTE::CLASS_LIKE,
                            };

                            if( !$g_ref ) {
                                $g_ref[] = [];
                                $le_ref =& $g_ref[array_key_last($g_ref)];
                            }

                            $le_ref['type'] = ( isset($this->data[0]['type']) )
                                ? $this->data[0]['type']
                                : $type;

                            if( $type === NUDTE::CLASS_LIKE ) {

                                $le_ref['name'] = $token->text;

                                if( $token->is(T_STRING) ) {
                                    $this->setCategoryHint($le_ref['type']);
                                }
                            }

                        // Last element still doesn't have namespace.
                        } elseif( !isset($le_ref['name']) ) {

                            $le_ref['name'] = $token->text;
                            $this->setCategoryHint($le_ref['type']);

                        // Start a new group item.
                        } elseif( $token->is([',']) ) {

                            $g_ref[] = [];

                        // Otherwise an alias.
                        } elseif( $token->is(T_STRING) ) {

                            $le_ref['alias'] = $token->text;
                            $this->setCategoryHint($le_ref['type']);
                        }

                    // Otherwise an alias.
                    } elseif( $token->is(T_STRING) ) {

                        $data_last_ref['alias'] = $token->text;
                        $this->setCategoryHint(
                            $data_last_ref['type'] ?? NUDTE::CLASS_LIKE
                        );

                    // Start a new data element.
                    } elseif( $token->is(',') ) {

                        $this->data[] = [];
                    }
                }
            }
        }

        return 1;
    }

    /**
     * Determined if block statement is used by performing a look ahead in the
     * token cache.
     */
    public function hasBlockStatementLookAhead(): ?bool {

        if( $this->state === parent::STATE_FINISHED ) {
            return null;
        }

        $token_cache = $this->tokenizer->getCache();
        $key = ($this->tokenizer->getKey() + 1);

        while(
            isset($token_cache[$key])
            && $token_cache[$key]->is([T_WHITESPACE, '\\', '{'])
        ) {

            if( $token_cache[$key]->is('{') ) {
                return true;
            }

            $key++;
        }

        return false;
    }

    /** Gets the product language feature object. */
    public function getProduct(): ?NamespaceUseDeclaration {

        if( $this->state !== parent::STATE_FINISHED ) {
            return null;
        }

        return new NamespaceUseDeclaration(
            $this->data['type'] ?? NamespaceUseDeclarationTypeEnum::CLASS_LIKE,
            $this->data,
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

        $curly_brackets_level = $tokenizer->getCurlyBracketsLevel();

        // Top level.
        if( $curly_brackets_level === 0 ) {
            return true;
        }

        $context = $tokenizer->getContext();

        // Directly in the context of a namespace-definition.
        return (
            ($context instanceof NamespaceDefinitionBuilder)
            && $context->compound_statement_open_level === $curly_brackets_level
        );
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

    /** Sets token category hint. */
    public function setCategoryHint( NUDTE $type ): void {

        if( $type === NUDTE::FUNCTION ) {
            $this->category_hint = PhpTokenCategoryEnum::FUNCTION;
        } elseif( $type === NUDTE::CONSTANT ) {
            $this->category_hint = PhpTokenCategoryEnum::CONSTANT;
        } else {
            $this->category_hint = PhpTokenCategoryEnum::CLASS_NAME;
        }
    }

    /** Gets the current active type. */
    public function getCurrentType(
        ?NUDTE $fallback = NUDTE::CLASS_LIKE
    ): ?NUDTE {

        if( !$this->data ) {
            return null;
        }

        $last_elem = $this->data[array_key_last($this->data)];

        if( !$last_elem ) {
            return null;
        }

        return ( !isset($last_elem['group']) )
            ? ( $last_elem['type'] ?? $fallback )
            : $last_elem['group'][array_key_last($last_elem['group'])]['type'];
    }

    /** Returns the current full namespace name (eg. when group is used). */
    public function getNamespace(): ?string {

        if( !$this->data ) {
            return null;
        }

        $last_elem = $this->data[array_key_last($this->data)];

        if( !$last_elem || !isset($last_elem['name']) ) {
            return null;
        }

        $namespace = $last_elem['name'];

        if( isset($last_elem['group']) && $last_elem['group'] ) {

            $last_key = array_key_last($last_elem['group']);
            $last_group_elem = $last_elem['group'][$last_key];

            if( isset($last_group_elem['name']) ) {
                $namespace .= ('\\' . $last_group_elem['name']);
            }
        }

        return $namespace;
    }

    /**
     * Converts meta data to an array suitable for import table storage.
     *
     * @param array       $metadata Array containing name and alias elements.
     * @param string|null $prefix   A prefix to be added to the name element.
     * @return array Two element array.
     */
    public static function metaDataToImportTableElement(
        array $metadata,
        string $prefix = null
    ): array {

        $full = $metadata['name'];

        $name = ( isset($metadata['alias']) )
            ? $metadata['alias']
            : ( ( ($pos = strrpos($full, '\\')) !== false )
                ? substr($full, $pos + 1)
                : $full );

        return [
            $name,
            ( !$prefix )
                ? $full
                : ($prefix . '\\' . $full)
        ];
    }
}
