<?php

/**
 * The enhanced PHP token object.
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

namespace PCTS\PhpTokens;

use PCTS\PhpTokens\Language\AbstractFunctionBuilder;
use PCTS\PhpTokens\Language\NamespaceUseDeclarationBuilder;
use PCTS\PhpTokens\Language\CategoryHintInterface;
use PCTS\PhpTokens\Language\NamespaceDefinitionBuilder;
use PCTS\PhpTokens\Language\NamespaceUseDeclarationTypeEnum as NUDTE;

class EnhancedPhpToken {

    /** Known tags that should not be confused with strings. */
    public const KNOWN_TAGS = [
        'true',
        'false',
        'null',
        'string',
        'array',
        'int',
        'bool',
        'object',
        'float',
        'mixed',
        'void',
        'binary',
        'unset',
        'never',
        'noreturn',
        'parent',
        'self',
        '__COMPILER_HALT_OFFSET__',
    ];

    /** A list of cast type PHP token identifiers. */
    public const CAST_TOKENS = [
        T_BOOL_CAST,
        T_INT_CAST,
        T_DOUBLE_CAST,
        T_STRING_CAST,
        T_ARRAY_CAST,
        T_OBJECT_CAST,
        T_UNSET_CAST,
    ];

    /** Cache from the stream provider. */
    public readonly array $cache;

    /** Key index of this token in the stream provider. */
    public readonly int $cache_key;

    /** Caches the resolved token category. */
    public readonly PhpTokenCategoryEnum $reusable_category;

    /**
     * @param \PhpToken $token  The standard PHP token to be enhanced.
     * @param Tokenizer $stream Token stream.
     */
    public function __construct(
        public readonly \PhpToken $token,
        public readonly Tokenizer $stream,
    ) {

        $this->cache = $stream->getCache();
        $this->cache_key = $stream->getKey();
    }

    /** Tells if token supports level counting. */
    public function isLeveled(): bool {

        return Tokenizer::isLeveled($this->token);
    }

    /**
     * A generic function to perform a look ahead in the token cache.
     *
     * @param array|string $while Look while any of these is met.
     * @param array|string $until Look until any of these is found.
     * @return bool True when found, and false when not found.
     */
    public function lookAhead(
        array|string $while,
        array|string $until
    ): bool {

        $key = ($this->cache_key + 1);
        $found = false;

        while(
            isset($this->cache[$key])
            && $this->cache[$key]->is($while)
        ) {

            if( $this->cache[$key]->is($until) ) {
                $found = true;
                break;
            }

            $key++;
        }

        return $found;
    }

    /**
     * A generic function to perform a look behind in the token cache.
     *
     * @param array|string $while Look while any of these is met.
     * @param array|string $until Look until any of these is found.
     * @return bool True when found, and false when not found.
     */
    public function lookBehind(
        int|string|array $while,
        int|string|array $until
    ): bool {

        $key = ($this->cache_key - 1);
        $found = false;

        while(
            isset($this->cache[$key])
            && $this->cache[$key]->is($while)
        ) {

            if( $this->cache[$key]->is($until) ) {
                $found = true;
                break;
            }

            $key--;
        }

        return $found;
    }

    /**
     * Tells if current token might represent a function name by performing a
     * look ahead for tokens usually following function names.
     */
    public function isFunctionLookAhead(): bool {

        return ( !$this->token->is([T_STRING, T_ARRAY]) )
            ? false
            : $this->lookAhead([T_WHITESPACE, '('], '(');
    }

    /**
     * Tells if current token might represent a class name by performing a look
     * behind for tokens usually preceeding class names.
     */
    public function isClassNameLookBehind(): bool {

        if( !$this->token->is(T_STRING) ) {
            return false;
        }

        $non_whitespace_tokens = [
            T_CLASS,
            T_NEW,
            T_INSTANCEOF,
            T_EXTENDS,
            T_INTERFACE,
            T_TRAIT,
            T_ENUM,
            // Traits
            T_USE,
            T_INSTEADOF,
            T_IMPLEMENTS,
        ];

        return $this->lookBehind([
            T_WHITESPACE,
            ',',
            T_STRING,
            ...$non_whitespace_tokens
        ], $non_whitespace_tokens);
    }

    /**
     * A generic look behind function that will look until the given token is
     * found.
     */
    public function isNakedLookBehind( int $token_id ): bool {

        $non_whitespace_tokens = [
            $token_id
        ];

        return $this->lookBehind([
            T_WHITESPACE,
            ...$non_whitespace_tokens
        ], $non_whitespace_tokens);
    }

    /**
     * Tells if current token might be a class name in catch clause.
     */
    public function isClassNameLookBehindForCatch(): bool {

        return ( !$this->token->is(T_STRING) )
            ? false
            : $this->lookBehind([
                T_WHITESPACE,
                '|',
                T_STRING,
                T_NAME_FULLY_QUALIFIED,
                T_NAME_QUALIFIED,
                '(',
                T_CATCH,
            ], [
                T_CATCH,
            ]);
    }

    /**
     * Tells if current token might represent a class name by performing a look
     * ahead for tokens usually following class names.
     */
    public function isClassNameLookAhead(): bool {

        if( !$this->token->is(T_STRING) ) {
            return false;
        }

        $key = ($this->cache_key + 1);

        if(
            isset($this->cache[$key])
            && $this->cache[$key]->is([T_DOUBLE_COLON, T_PAAMAYIM_NEKUDOTAYIM])
            // Exclude object properties.
            && !$this->cache[$this->cache_key - 1]->is([T_OBJECT_OPERATOR])
        ) {
            return true;
        }

        return $this->lookAhead(
            [T_WHITESPACE, T_VARIABLE, '|'],
            [T_VARIABLE, '|'],
        );
    }

    /**
     * Tells if current token might represent a varname close bracket by
     * performing a look behind.
     */
    public function isVarnameCloseLookBehind(): bool {

        if( !$this->token->is('}') ) {
            return false;
        }

        $key = ($this->cache_key - 1);

        return (
            isset($this->cache[$key])
            && $this->cache[$key]->is(T_STRING_VARNAME)
        );
    }

    /**
     * Tells if current token might represent a varname by performing a look
     * behind for tokens usually preceeding varnames.
     */
    public function isVarnameLookBehind(): bool {

        if( !$this->token->is(T_STRING) ) {
            return false;
        }

        $key = ($this->cache_key - 1);

        return (
            isset($this->cache[$key])
            && $this->cache[$key]->is([
                T_OBJECT_OPERATOR,
                T_NULLSAFE_OBJECT_OPERATOR
            ])
        );
    }

    /** Determines category token belongs to. */
    public function getCategory(): PhpTokenCategoryEnum {

        if(
            ($context = $this->stream->getContext())
            && ( $context instanceof CategoryHintInterface )
            && $context->category_hint !== null
        ) {
            return $context->category_hint;
        }

        /* Prevents keywords (like default, function, etc) from being marked as
        language keywords when they are preceeded by "case" keyword (especially
        in enums). */
        if(
            !$this->token->is([
                T_CONSTANT_ENCAPSED_STRING,
                '"',
                T_LNUMBER,
                T_DNUMBER
            ]) && (
                $this->isNakedLookBehind(T_CASE)
            )
        ) {
            return PhpTokenCategoryEnum::GENERIC_STRING;
        }

        $token_name = $this->token->getTokenName();

        if( $this->token->id === T_WHITESPACE_LINE_BREAK ) {
            $token_name = 'T_WHITESPACE_LINE_BREAK';
        }

        switch( $token_name ) {
            case 'T_OPEN_TAG':
            case 'T_OPEN_TAG_WITH_ECHO':
                $category = PhpTokenCategoryEnum::OPEN_TAG;
                break;
            case 'T_CLOSE_TAG':
                $category = PhpTokenCategoryEnum::CLOSE_TAG;
                break;
            case 'T_VARIABLE':
                $category = PhpTokenCategoryEnum::VARIABLE;
                break;
            case 'T_WHITESPACE':
            case 'T_WHITESPACE_LINE_BREAK':
                $category = PhpTokenCategoryEnum::WHITESPACE;
                break;
            case 'T_COMMENT':
            case 'T_DOC_COMMENT':
                $category = PhpTokenCategoryEnum::COMMENT;
                break;
            case 'T_BOOL_CAST':
            case 'T_INT_CAST':
            case 'T_DOUBLE_CAST':
            case 'T_STRING_CAST':
            case 'T_ARRAY_CAST':
            case 'T_OBJECT_CAST':
            case 'T_UNSET_CAST':
                $category = PhpTokenCategoryEnum::CAST;
                break;
            case 'T_CONSTANT_ENCAPSED_STRING':
            case 'T_ENCAPSED_AND_WHITESPACE':
            // Opening or closing of a string that contains a variable inside.
            case '"':
                $category = PhpTokenCategoryEnum::STRING;
                break;
            case 'T_LNUMBER':
            case 'T_DNUMBER':
            case 'T_NUM_STRING':
                $category = PhpTokenCategoryEnum::NUMBER;
                break;
            case 'T_START_HEREDOC':
                $category = PhpTokenCategoryEnum::HEREDOC_OPEN;
                break;
            case 'T_END_HEREDOC':
                $category = PhpTokenCategoryEnum::HEREDOC_CLOSE;
                break;
            case 'T_INLINE_HTML':
                $category = PhpTokenCategoryEnum::INLINE_HTML;
                break;
            case 'T_FUNCTION':
            case 'T_FN':
            case 'T_CLASS':
            case 'T_TRAIT':
            case 'T_INTERFACE':
            case 'T_ENUM':
            case 'T_PUBLIC':
            case 'T_ABSTRACT':
            case 'T_FUNCTION':
            case 'T_READONLY':
            case 'T_NEW':
            case 'T_USE':
            case 'T_IMPLEMENTS':
            case 'T_NAMESPACE':
            case 'T_PROTECTED':
            case 'T_PRIVATE':
            case 'T_PUBLIC':
            case 'T_STATIC':
            case 'T_FINAL':
            case 'T_AS':
            case 'T_CLONE':
            case 'T_CONST':
            case 'T_EXTENDS':
            case 'T_GLOBAL':
            case 'T_CALLABLE':
            case 'T_VAR':
                $category = PhpTokenCategoryEnum::KEYWORD;
                break;
            case 'T_INCLUDE':
            case 'T_INCLUDE_ONCE':
            case 'T_REQUIRE':
            case 'T_REQUIRE_ONCE':
            case 'T_FOR':
            case 'T_ENDFOR':
            case 'T_FOREACH':
            case 'T_ENDFOREACH':
            case 'T_CONTINUE':
            case 'T_IF':
            case 'T_ENDIF':
            case 'T_ELSE':
            case 'T_ELSEIF':
            case 'T_WHILE':
            case 'T_ENDWHILE':
            case 'T_SWITCH':
            case 'T_ENDSWITCH':
            case 'T_DECLARE':
            case 'T_ENDDECLARE':
            case 'T_EXIT':
            case 'T_CASE':
            case 'T_BREAK':
            case 'T_DO':
            case 'T_TRY':
            case 'T_THROW':
            case 'T_CATCH':
            case 'T_FINALLY':
            case 'T_RETURN':
            case 'T_YIELD':
            case 'T_YIELD_FROM':
            case 'T_DEFAULT':
            case 'T_MATCH':
            case 'T_GOTO':
                $category = PhpTokenCategoryEnum::EXPRESSION_KEYWORD;
                break;
            case 'T_ECHO':
            case 'T_PRINT':
            case 'T_UNSET':
            case 'T_ISSET':
            case 'T_EMPTY':
            case 'T_LIST':
            case 'T_HALT_COMPILER':
            case 'T_EVAL':
                $category = PhpTokenCategoryEnum::FUNCTION_LIKE_KEYWORD;
                break;
            case 'T_ARRAY':
                $category = match(true) {
                    $this->isFunctionLookAhead() =>
                        PhpTokenCategoryEnum::FUNCTION_LIKE_KEYWORD,
                    default => PhpTokenCategoryEnum::GENERIC_STRING,
                };
                break;
            case 'T_LINE':
            case 'T_FILE':
            case 'T_DIR':
            case 'T_CLASS_C':
            case 'T_TRAIT_C':
            case 'T_METHOD_C':
            case 'T_FUNC_C':
            case 'T_NS_C':
                $category = PhpTokenCategoryEnum::COMPILE_TIME_CONSTANT;
                break;
            case '=':
            case '+':
            case '>':
            case '<':
            case '*':
            case '%':
            case '|':
            case '^':
            case '~':
            case 'T_PLUS_EQUAL':
            case 'T_DOUBLE_COLON':
            case 'T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG':
            case 'T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG':
            case 'T_AND_EQUAL':
            case 'T_INC':
            case 'T_DEC':
            case 'T_IS_IDENTICAL':
            case 'T_IS_NOT_EQUAL':
            case 'T_IS_NOT_IDENTICAL':
            case 'T_IS_SMALLER_OR_EQUAL':
            case 'T_LOGICAL_AND':
            case 'T_LOGICAL_OR':
            case 'T_LOGICAL_XOR':
            case 'T_BOOLEAN_AND':
            case 'T_BOOLEAN_OR':
            case 'T_POW':
            case 'T_POW_EQUAL':
            case 'T_OBJECT_OPERATOR':
            case 'T_NULLSAFE_OBJECT_OPERATOR':
            case 'T_INSTANCEOF':
            case 'T_INSTEADOF':
            case 'T_IS_EQUAL':
            case 'T_DOUBLE_ARROW':
            case 'T_MINUS_EQUAL':
            case 'T_MUL_EQUAL':
            case 'T_MOD_EQUAL':
            case 'T_COALESCE_EQUAL':
            case 'T_IS_GREATER_OR_EQUAL':
            case 'T_SPACESHIP':
            case 'T_COALESCE':
            case 'T_DIV_EQUAL':
            case 'T_CONCAT_EQUAL':
            case 'T_OR_EQUAL':
            case 'T_XOR_EQUAL':
            case 'T_SL':
            case 'T_SR':
            case 'T_SL_EQUAL':
            case 'T_SR_EQUAL':
            case 'T_ELLIPSIS':
                $category = PhpTokenCategoryEnum::OPERATOR;
                break;
            case '(':
            case ')':
            case '{':
            case '}':
            case '[':
            case ']':
            case ',':
            case ';':
            case ':':
            case '.':
            case '!':
            case '?':
            case 'T_CURLY_OPEN':
            case 'T_NS_SEPARATOR':
                $category = (
                    $token_name === '}'
                    && $this->isVarnameCloseLookBehind()
                )
                    ? PhpTokenCategoryEnum::VARNAME_CLOSE
                    : PhpTokenCategoryEnum::PUNCTUATION;
                break;
            case 'T_STRING':
                $is_known_tag = in_array($this->token, self::KNOWN_TAGS);
                $category = match(true) {
                    !$is_known_tag && ( $this->isClassNameLookBehind()
                        || $this->isClassNameLookBehindForCatch() )
                        => PhpTokenCategoryEnum::CLASS_NAME,
                    !$is_known_tag && $this->isClassNameLookAhead()
                        => PhpTokenCategoryEnum::CLASS_NAME,
                    $this->isFunctionLookAhead()
                        => PhpTokenCategoryEnum::FUNCTION,
                    $this->isVarnameLookBehind()
                        => PhpTokenCategoryEnum::VARNAME,
                    !$is_known_tag
                    && ($context = $this->stream->getContext())
                    && ($context instanceof AbstractFunctionBuilder)
                    && $context->is_union_types
                        => PhpTokenCategoryEnum::CLASS_NAME,
                    default => PhpTokenCategoryEnum::GENERIC_STRING,
                };
                break;
            case 'T_NAME_QUALIFIED':
            case 'T_NAME_FULLY_QUALIFIED':
            case 'T_NAME_RELATIVE':
                $category = PhpTokenCategoryEnum::NAMESPACE;
                break;
            case 'T_DOLLAR_OPEN_CURLY_BRACES':
            case '$':
                $category = PhpTokenCategoryEnum::VARNAME_OPEN;
                break;
            case 'T_STRING_VARNAME':
                $category = PhpTokenCategoryEnum::VARNAME;
                break;
            case 'T_ATTRIBUTE':
                $category = PhpTokenCategoryEnum::ATTRIBUTE_OPEN;
                break;
            default:
                $category = PhpTokenCategoryEnum::UNKNOWN;
                break;
        }

        return $category;
    }

    /** Gets cached category or caches one when not available. */
    public function getReusableCategory(): PhpTokenCategoryEnum {

        $this->reusable_category ??= $this->getCategory();

        return $this->reusable_category;
    }

    /** Gets a list of class names that should be applies to this token. */
    public function getClassNames(): array {

        $category = $this->getReusableCategory();
        $token = $this->token;

        $classes = [
            $category->value
        ];

        if( $this->isLeveled() ) {

            $level = $this->stream->getUniversalLevel();

            // Explicit level.
            $classes[] = ('level-' . $level);
            // Chunks of 3 (eg. 1n, 2n, 3n, 1n, 2n...).
            $classes[] = ('level-' . ((($level - 1) % 3) + 1) . 'n');
        }

        // Keyword.
        if(
            $category === PhpTokenCategoryEnum::KEYWORD
            || $category === PhpTokenCategoryEnum::EXPRESSION_KEYWORD
            || $category === PhpTokenCategoryEnum::FUNCTION_LIKE_KEYWORD
        ) {

            $classes[] = ('keyword-'
                . self::tokenNameToClassName($token->getTokenName()));

        // Specific operator.
        } elseif(
            $category == PhpTokenCategoryEnum::OPERATOR
            && $token->is([
                T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,
            ])
        ) {

            $classes[] = 'operator-reference';

        // Compile time constant.
        } elseif( $category === PhpTokenCategoryEnum::COMPILE_TIME_CONSTANT ) {

            $classes[] = 'constant';

        // String tag.
        } elseif( $category === PhpTokenCategoryEnum::GENERIC_STRING ) {

            if( in_array($token->text, self::KNOWN_TAGS) ) {
                $classes[] = 'string-key';
            }

        // Unknown.
        } elseif( $category === PhpTokenCategoryEnum::UNKNOWN ) {

            $classes[] = $token->getTokenName();
        }

        return $classes;
    }

    /** Gets outer HTML string that describes this token. */
    public function getOuterHtml(): string {

        return sprintf(
            '<span class="%s">%s</span>',
            implode(' ', $this->getClassNames()),
            $this->getInnerHtml()
        );
    }

    /** Gets inner HTML string that describes this token. */
    public function getInnerHtml(): string {

        $category = $this->getReusableCategory();

        if( $category === PhpTokenCategoryEnum::NAMESPACE ) {

            return self::getNamespaceInnerHtml(
                $this->token->text,
                $this->getNamespaceLastComponentType()
            );

        } elseif( $category === PhpTokenCategoryEnum::HEREDOC_OPEN ) {

            return self::getHeredocInnerHtml($this->token->text);

        } else {

            return htmlentities($this->token->text);
        }
    }

    /**
     * Gets type of the last namespace name component mainly to determine if
     * it's a partial namespace name (eg. preceeds a namespace group).
     */
    public function getNamespaceLastComponentType(): ?NUDTE {

        $type = NUDTE::CLASS_LIKE;

        if( $context = $this->stream->getContext() ) {

            if( $context instanceof NamespaceUseDeclarationBuilder ) {

                /* If there is a block statement in front, do not mark the last
                namespace component, because the namespace is partial. */
                $type = ( !$context->hasBlockStatementLookAhead() )
                    ? $context->getCurrentType()
                    : null;

            } elseif(
                ($context instanceof NamespaceDefinitionBuilder)
                && !isset($context->compound_statement_open_level)
            ) {

                // Namespace definition will be presented as generic string.
                $type = null;
            }
        }

        return $type;
    }

    /** Convert token name to class name. */
    public static function tokenNameToClassName( string $token_name ): string {

        if( !str_starts_with($token_name, 'T_') ) {
            throw new \UnexpectedValueException(
                "Token name must start with \"T_\""
            );
        }

        return strtolower(
            str_replace('_', '-', substr($token_name, 2))
        );
    }

    /** Formats heredoc beginning string in HTML format. */
    public static function getHeredocInnerHtml(
        string $heredoc_open_str
    ): string {

        return sprintf(
            '<span class="%s">%s</span><span class="%s">%s</span>',
            'heredoc-operator',
            htmlentities('<<<'),
            'heredoc-identifier',
            ltrim($heredoc_open_str, '<')
        );
    }

    /**
     * Builds HTML for a namespace name.
     *
     * @param string     $name         Namespace name.
     * @param NUDTE|null $mark_last_as Whether to mark last component as use
     *                                 type.
     * @return string HTML string.
     */
    public static function getNamespaceInnerHtml(
        string $name,
        ?NUDTE $mark_last_as = NUDTE::CLASS_LIKE,
    ): string {

        $divider = '\\';
        $name_parts = explode($divider, $name);
        $name_parts_count = count($name_parts);
        $format = '<span class="%s">%s</span>';
        $result = '';

        // Single part.
        if(
            $name_parts_count === 1
            || ($name_parts_count === 2 && $name_parts[0] === '')
        ) {

            if( $name_parts_count === 2 ) {
                $result .= sprintf(
                    $format,
                    PhpTokenCategoryEnum::PUNCTUATION->value,
                    $divider
                );
            }

            $last_part = $name_parts[array_key_last($name_parts)];

        // Multi part.
        } else {

            /* Special case when the first component of the name is the keyword
            "namespace". */
            if( strtolower($name_parts[0]) === 'namespace' ) {

                $result .= sprintf($format, 'base', $name_parts[0]);
                unset($name_parts[0]);
            }

            if( $mark_last_as ) {

                $last_part = array_pop($name_parts);
            }

            if( $name_parts ) {

                $result .= sprintf(
                    $format,
                    PhpTokenCategoryEnum::GENERIC_STRING->value,
                    ( ($result) ? $divider : '' )
                        . implode($divider, $name_parts)
                );
            }

            if( isset($last_part) ) {

                $result .= sprintf(
                    $format,
                    PhpTokenCategoryEnum::PUNCTUATION->value,
                    $divider
                );
            }
        }

        if( isset($last_part) ) {

            if( !$mark_last_as ) {

                $result .= sprintf(
                    $format,
                    PhpTokenCategoryEnum::GENERIC_STRING->value,
                    $last_part
                );

            } else {

                $class_name = match($mark_last_as) {
                    NUDTE::CLASS_LIKE
                        => PhpTokenCategoryEnum::CLASS_NAME->value,
                    NUDTE::FUNCTION
                        => PhpTokenCategoryEnum::FUNCTION->value,
                    NUDTE::CONSTANT
                        => PhpTokenCategoryEnum::CONSTANT->value,
                };

                $result .= sprintf($format, $class_name, $last_part);
            }
        }

        return $result;
    }
}
