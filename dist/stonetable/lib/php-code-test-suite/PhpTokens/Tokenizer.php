<?php

/**
 * Advanced PHP tokenizer.
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

namespace PCTS\PhpTokens;

use PCTS\PhpTokens\Language\{
    NamespaceDefinitionBuilder as NDB,
    NamespaceUseDeclarationBuilder as NUDB,
    NamespaceUseDeclarationTypeEnum as NUDTE,
    FunctionDefinitionBuilder as FDB,
    AnonymousFunctionBuilder as AFB,
    ArrowAnonymousFunctionBuilder as AAFB
};

if( !defined('T_OPEN_PARENTHESIS') ) {
    define('T_OPEN_PARENTHESIS', 40);
}

if( !defined('T_CLOSE_PARENTHESIS') ) {
    define('T_CLOSE_PARENTHESIS', 41);
}

if( !defined('T_OPEN_SQUARE_BRACKET') ) {
    define('T_OPEN_SQUARE_BRACKET', 91);
}

if( !defined('T_CLOSE_SQUARE_BRACKET') ) {
    define('T_CLOSE_SQUARE_BRACKET', 93);
}

define('T_WHITESPACE_LINE_BREAK', 10001);

class Tokenizer implements \Iterator {

    /** Makes Tokenizer::key() return the line number. */
    public const KEY_AS_LINE_NUMBER = 1;

    /** Makes Tokenizer::current() return enhanced token. */
    public const CURRENT_AS_ENHANCED_TOKEN = 2;

    /** Makes Tokenizer::current() return HTML code. */
    public const CURRENT_AS_HTML = 4;

    /** Option to skip EOL whitespace characters. */
    public const SKIP_LINE_TRAILING_WS = 8;

    /** Option to split specific tokens into multiple tokens. */
    public const SPLIT_SPLITABLE_TOKENS = 16;

    /** Collection of whitespace characters. */
    public const WHITESPACE_CHARS = " \t\n\r\0\x0B";

    /** Key index used in the iterator. */
    private int $key = 0;

    /** A list of PHP tokens produced from the code. */
    private array $token_cache;

    /** Current line number. */
    private int $line_number = 1;

    /**
     * How many additional tokens are waiting to be placed into the iterator
     * sequence.
     */
    private int $queue_size = 0;

    /** Current level number of parentheses. */
    private int $parentheses_level = 0;

    /** Current level number of curly brackets. */
    private int $curly_brackets_level = 0;

    /** Current level number of squeare brackets. */
    private int $square_brackets_level = 0;

    /** Current level number of any brackets. */
    private int $universal_level = 0;

    /**
     * A list of cached tokens kept while it's determined if they can be used
     * to open a language feature builder. */
    private array $open_token_cache = [];

    /** A stack of language feature builders each collecting tokens. */
    private array $stack = [];

    /** A list of namespaces. */
    private array $namespaces = [];

    /** Class import table. */
    private array $class_import_table = [];

    /** Function import table. */
    private array $function_import_table = [];

    /** Constant import table. */
    private array $constant_import_table = [];

    /**
     * @param string $code  PHP code string.
     * @param int    $flags Options to be used.
     */
    public function __construct(
        public readonly string $code,
        public int $flags = 0,
    ) {

        $this->token_cache = \PhpToken::tokenize($code);
    }

    /** Gets the full token cache. */
    public function getCache(): array {

        return $this->token_cache;
    }

    /** Gets the next PHP token from the full token cache. */
    public function nextInTokenCache(): ?\PhpToken {

        return $this->token_cache[$this->key + 1] ?? null;
    }

    /** Gets the current index key number. */
    public function getKey(): int {

        return $this->key;
    }

    /** Gets the current token element. */
    public function getElement(): ?\PhpToken {

        return ( $this->token_cache[$this->key] ?? null );
    }

    /**
     * Gets the current acting namespace name.
     *
     * @return string|null "null" or <empty string> indicates a global
     *                     namespace, otherwise it's namespace name.
     */
    public function getCurrentNamespaceName(): ?string {

        return ( $this->namespaces )
            ? $this->namespaces[array_key_last($this->namespaces)]['name']
            : null;
    }

    /** Gets the class import table. */
    public function getClassImportTable(): array {

        return $this->class_import_table;
    }

    /** Retrieves item from the class import table by a given name. */
    public function getFromClassImportTable( string $name ): ?string {

        return ( $this->class_import_table[$name] ?? null );
    }

    /** Gets the function import table. */
    public function getFunctionImportTable(): array {

        return $this->function_import_table;
    }

    /** Retrieves item from the function import table by a given name. */
    public function getFromFunctionImportTable( string $name ): ?string {

        return ( $this->function_import_table[$name] ?? null );
    }

    /** Gets the constant import table. */
    public function getConstantImportTable(): array {

        return $this->constant_import_table;
    }

    /** Retrieves item from the constant import table by a given name. */
    public function getFromConstantImportTable( string $name ): ?string {

        return ( $this->constant_import_table[$name] ?? null );
    }

    /** Gets import table for a given "namespace use declaration" type. */
    public function getImportTableFor( NUDTE $type ): array {

        return match($type) {
            NUDTE::CLASS_LIKE => $this->getClassImportTable(),
            NUDTE::FUNCTION => $this->getFunctionImportTable(),
            NUDTE::CONSTANT => $this->getConstantImportTable(),
        };
    }

    /** Empties storages of all import tables. */
    protected function flushAllImportTables(): void {

        $this->class_import_table
            = $this->function_import_table
            = $this->constant_import_table
            = [];
    }

    /**
     * Resolves a given namespace name according to resolution rules.
     *
     * @param string $name Namespace name to resolve.
     * @param NUDTE $type Namespace use declaration type.
     * @return string Resolved namespace name.
     * @see https://www.php.net/manual/en/language.namespaces.rules.php
     */
    public function resolveNamespaceName(
        string $name,
        NUDTE $type = NUDTE::CLASS_LIKE
    ): string {

        $separator = '\\';
        $current_namespace = $this->getCurrentNamespaceName();

        // Fully qualified.
        if( str_starts_with($name, $separator) ) {

            // Resolve to the name without leading namespace separator.
            return substr($name, 1);

        // Other than fully qualified.
        } else {

            $parts = explode($separator, $name);
            $namespace_keyword = 'namespace';

            // Starts with the special "namespace" keyword.
            if( strtolower($parts[0]) === $namespace_keyword ) {

                $stripped_off = substr($name, (strlen($namespace_keyword) + 1));

                // Namespace available.
                return ( $current_namespace )
                    ? ($current_namespace . $separator . $stripped_off)
                    // Global namespace.
                    : $stripped_off;

            } else {

                $first_separator_pos = strpos($name, $separator);
                $contains_separator = ($first_separator_pos !== false);
                $import_table = $this->getImportTableFor($type);

                // Qualified name.
                if( $contains_separator ) {

                    if( isset($import_table[$parts[0]]) ) {
                        return (ltrim($import_table[$parts[0]], $separator)
                            . substr($name, (strlen($parts[0]) + 1)));
                    } elseif( $current_namespace ) {
                        return ($current_namespace . $separator . $name);
                    } else {
                        return $name;
                    }

                // Unqualified name.
                } else {

                    // Import rule applies.
                    if( isset($import_table[$name]) ) {

                        return ltrim($import_table[$name], $separator);

                    // No import rule applies.
                    } else {

                        // Class-like symbol.
                        if( $type === NUDTE::CLASS_LIKE ) {

                            return ( $current_namespace )
                                ? ($current_namespace . $separator . $name)
                                : $name;

                        // Function or constant.
                        } else {

                            /* Note: this should normally be resolved at
                            runtime, but this feature is not available here,
                            hence a simplified approach. */
                            return ( $current_namespace )
                                ? ($current_namespace . $separator . $name)
                                : $name;
                        }
                    }
                }
            }
        }
    }

    /** Rewinds the index key back to the start. */
    public function rewind(): void {

        $this->key = 0;
    }

    /**
     * Tells whether this is the last element in the full token cache iterator.
     */
    public function isLast(): bool {

        return count($this->token_cache) <= ($this->key + 1);
    }

    /**
     * Inserts additional PHP tokens into the full token cache for the iterator
     * to catch.
     *
     * @param array $data A list of tokens to insert.
     */
    public function insertToCollection( array $data ): void {

        $data_count = count($data);

        if( $data_count ) {

            array_splice($this->token_cache, ($this->key + 1), 0, $data);
            $this->queue_size = $data_count;
        }
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

        $key = ($this->key - 1);
        $found = false;

        while(
            isset($this->token_cache[$key])
            && $this->token_cache[$key]->is($while)
        ) {

            if( $this->token_cache[$key]->is($until) ) {
                $found = true;
                break;
            }

            $key--;
        }

        return $found;
    }

    /** Return the current element (depends on options set). */
    public function current(): mixed {

        $token = $this->getElement();

        if( $this->flags & self::SKIP_LINE_TRAILING_WS ) {

            if( $token->is(T_WHITESPACE) ) {

                if( !$this->isLast() ) {

                    $token->text = self::trimLineTrailingWhitespace(
                        $token->text
                    );

                } else {

                    $token->text = str_replace(
                        [' ', "\t", "\v", "\0"],
                        '',
                        $token->text
                    );
                }

            } elseif( $token->is(T_OPEN_TAG) ) {

                if( $token->text === '<?php ' ) {
                    $token->text = '<?php';
                }
            }
        }

        if( !$this->queue_size ) {

            if(
                $this->flags & self::SPLIT_SPLITABLE_TOKENS
                && ($data = $this->isSplitable($token))
            ) {

                $params = [
                    $token,
                ];

                if( isset($data['arguments']) ) {
                    $params = [...$params, ...$data['arguments']];
                }

                $tokens = ($data['callable'])(...$params);

                if( $tokens ) {

                    switch ($data['placement']) {
                        case PlacementEnum::REPLACE:

                            $token = array_shift($tokens);
                            $this->token_cache[$this->key] = $token;
                            $this->insertToCollection($tokens);

                        break;
                        case PlacementEnum::INSERT:

                            $this->insertToCollection($tokens);

                        break;
                    }
                }
            }

        } else {

            $this->queue_size--;
        }

        $this->incrementLevel();

        $map = self::tokenToClassNameMap();

        /* Start accumulating tokens to qualify for opening a given language
        feature. */
        if(
            !$this->open_token_cache
            && isset($map[$token->id])
            /* Token not preceeded by double colon. The idea is that it won't
            open anything, as it's attached to the thing that is before double
            colon. */
            && !$this->lookBehind(
                [T_WHITESPACE, T_DOUBLE_COLON],
                T_DOUBLE_COLON
            )
            /* Token not preceeded by case. The idea is that it won't open
            anything, as it's attached to the case keyword. */
            && !$this->lookBehind([T_WHITESPACE, T_CASE], T_CASE)
        ) {

            $this->open_token_cache = [
                $token,
            ];

            $classes = (array)$map[$token->id];

        // Keep accumulating tokens for open.
        } elseif(
            $this->open_token_cache
            && !$token->is([T_WHITESPACE, T_WHITESPACE_LINE_BREAK])
        ) {

            $this->open_token_cache[] = $token;
            $classes = (array)$map[$this->open_token_cache[0]->id];
        }

        if( isset($classes) ) {

            $builder = $this->openBuilderClass($classes);

            if( $builder ) {

                // Namespace definition.
                if( $builder instanceof NDB ) {

                    // Record the active namespace name.
                    $builder->onNamespaceName(
                        function( string $namespace_name ): void {

                            $this->flushAllImportTables();

                            $this->namespaces[] = [
                                'name' => $namespace_name,
                            ];
                        }
                    );

                // Namespace use declaration.
                } elseif( $builder instanceof NUDB ) {

                    $ns = $this->namespaces;

                    /* Add the position of the first occurrence of a namespace
                    use declaration in a given namespace area. */
                    if( $ns && !isset($ns[array_key_last($ns)]['first_use']) ) {

                        $this->namespaces[array_key_last($ns)]['first_use']
                            = $token->pos;
                    }

                    $builder->onFinished(
                        function( array $payload ) use( $builder ): void {
                            $this->addToImportTable($payload, $builder);
                        }
                    );
                }
            }
        }

        /* Feed in a token into the current top-most open language feature.
        Note: currently this does not feed into all open language features. */
        if( $this->stack && (!isset($classes) || $builder === null) ) {

            $class = $this->getContext();

            // Finished feeding.
            if( $class->feed($token) === 0 ) {
                array_pop($this->stack);
            }
        }

        if( $this->flags & self::CURRENT_AS_ENHANCED_TOKEN ) {

            return new EnhancedPhpToken($token, $this);

        } elseif( $this->flags & self::CURRENT_AS_HTML ) {

            return (new EnhancedPhpToken($token, $this))->getOuterHtml();

        } else {

            return $token;
        }
    }

    /** Attempts to open a language builder. */
    private function openBuilderClass( array $classes ): ?object {

        $class = null;

        foreach( $classes as $class_name ) {

            $can_create = $class_name::verify($this, $this->open_token_cache);

            if( $can_create === true ) {

                $class = $class_name::create($this, $this->open_token_cache);

                $this->open_token_cache = [];
                $this->stack[] = $class;

                break;

            /* "false" return value implies that there shouldn't be further
            attempts to use token cache. */
            } elseif( $can_create === false ) {

                $this->open_token_cache = [];
            }
        }

        return $class;
    }

    /**
     * Gets the inner-most language element from the stack of open elements.
     */
    public function getContext(): ?object {

        return ( $this->stack )
            ? $this->stack[array_key_last($this->stack)]
            : null;
    }

    /** Tells if currently the stack ends with a namespace use declaration. */
    public function isNamespaceUseDeclarationContext(): bool {

        return (
            ($context = $this->getContext())
            && ($context instanceof NUDB)
        );
    }

    /** Tells if currently the stack ends with a namespace definition. */
    public function isNamespaceDefinitionContext(): bool {

        return (
            ($context = $this->getContext())
            && ($context instanceof NDB)
        );
    }

    /**
     * Tells if tokenizer is currently walking inside the namespace definition
     * name.
     */
    public function isNamespaceDefinitionNamePhase(): bool {

        $context = $this->getContext();

        return (
            ($context instanceof NDB)
            && !isset($context->compound_statement_open_level)
        );
    }

    /**
     * Adds given payload (normally from the NamespaceUseDeclarationBuilder
     * builder) to the import tables.
     *
     * @param array     $payload Builder's resulting data payload.
     * @param NUDB|null $builder Builder instance.
     */
    protected function addToImportTable(
        array $payload,
        ?NUDB $builder = null
    ): void {

        foreach( $payload as $data ) {

            if( !isset($data['group']) ) {

                [$name, $full] = NUDB::metaDataToImportTableElement($data);

                $table_property_name = self::typeToImportTablePropertyName(
                    $data['type'] ?? NUDTE::CLASS_LIKE
                );

                $this->{$table_property_name}[$name] = $full;

            } else {

                foreach( $data['group'] as $group_data ) {

                    [$name, $full] = NUDB::metaDataToImportTableElement(
                        $group_data, $data['name']
                    );

                    $table_property_name = self::typeToImportTablePropertyName(
                        $group_data['type']
                    );

                    $this->{$table_property_name}[$name] = $full;
                }
            }
        }
    }

    /** Gets the current key element. */
    public function key(): mixed {

        return ( $this->flags & self::KEY_AS_LINE_NUMBER )
            ? $this->line_number
            : $this->key;
    }

    /** Move forward to next element. */
    public function next(): void {

        $this->decrementLevel();

        $this->key++;
    }

    /** Checks if current position is valid. */
    public function valid(): bool {

        $valid = ($this->queue_size)
            ? true
            : ( $this->getElement() !== null );

        if( $valid ) {
            $this->line_number = $this->getElement()->line;
        }

        return $valid;
    }

    /** Increment bracket levels. */
    public function incrementLevel(): void {

        $token = $this->getElement();

        $individual_level_changed = false;

        // Parentheses.
        if( $token->is('(') ) {

            $this->parentheses_level++;
            $individual_level_changed = true;

        // Curly brackets.
        } elseif( $token->is([
            '{',
            T_CURLY_OPEN,
            T_DOLLAR_OPEN_CURLY_BRACES
        ]) ) {

            $this->curly_brackets_level++;
            $individual_level_changed = true;

        // Square brackets.
        } elseif( $token->is(['[', T_ATTRIBUTE]) ) {

            $this->square_brackets_level++;
            $individual_level_changed = true;
        }

        if( $individual_level_changed ) {
            $this->universal_level++;
        }
    }

    /** Decrement bracket levels. */
    public function decrementLevel(): void {

        if( $token = $this->getElement() ) {

            $individual_level_changed = false;

            if( $token->is(')') ) {

                $this->parentheses_level--;
                $individual_level_changed = true;

            } elseif( $token->is('}') ) {

                $this->curly_brackets_level--;
                $individual_level_changed = true;

            } elseif( $token->is(']') ) {

                $this->square_brackets_level--;
                $individual_level_changed = true;
            }

            if( $individual_level_changed ) {
                $this->universal_level--;
            }
        }
    }

    /** Returns level number for the parentheses. */
    public function getParenthesesLevel(): int {

        return $this->parentheses_level;
    }

    /** Returns level number for the curly brackets. */
    public function getCurlyBracketsLevel(): int {

        return $this->curly_brackets_level;
    }

    /** Returns level number for the square brackets. */
    public function getSquareBracketsLevel(): int {

        return $this->square_brackets_level;
    }

    /** Gets level number for any token that supports level counting. */
    public function getLevel(): ?int {

        $token = $this->getElement();

        return match( true ) {
            // Parentheses.
            $token->is(['(', ')']) => $this->getParenthesesLevel(),
            // Curly brackets.
            $token->is([
                '{',
                '}',
                T_CURLY_OPEN,
                T_DOLLAR_OPEN_CURLY_BRACES
            ]) => $this->getCurlyBracketsLevel(),
            // Square brackets.
            $token->is([
                '[',
                ']',
                T_ATTRIBUTE
            ]) => $this->getSquareBracketsLevel(),
            default => null,
        };
    }

    /** Gets the universal level number. */
    public function getUniversalLevel(): int {

        return $this->universal_level;
    }

    /**
     * Gets token atomization parameters.
     *
     * @param \PhpToken $token Token to atomize.
     * @return false|array False when given token cannot be atomized, or
     *                     associative array with atomization parameters.
     */
    public function isSplitable( \PhpToken $token ): false|array {

        if( $token->is(T_WHITESPACE) ) {
            return [
                'callable' => [self::class, 'whitespaceStringToLineTokens'],
                'placement' => PlacementEnum::REPLACE,
            ];
        }

        if( $token->is(EnhancedPhpToken::CAST_TOKENS) ) {
            return [
                'callable' => [self::class, 'splitCast'],
                'placement' => PlacementEnum::REPLACE,
            ];
        }

        if( $token->is(T_ATTRIBUTE) ) {
            return [
                'callable' => [self::class, 'splitAttribute'],
                'placement' => PlacementEnum::REPLACE,
            ];
        }

        if( $token->is(T_OPEN_TAG) ) {
            return [
                'callable' => [self::class, 'splitOpenTag'],
                'placement' => PlacementEnum::REPLACE,
            ];
        }

        if( $token->is(T_CLOSE_TAG) ) {
            return [
                'callable' => [self::class, 'splitCloseTag'],
                'placement' => PlacementEnum::REPLACE,
            ];
        }

        if( $token->is(T_START_HEREDOC) ) {
            return [
                'callable' => [self::class, 'splitHeredocStart'],
                'placement' => PlacementEnum::REPLACE,
            ];
        }

        if( $token->is(T_END_HEREDOC) ) {
            return [
                'callable' => [self::class, 'splitHeredocEnd'],
                'placement' => PlacementEnum::REPLACE,
            ];
        }

        if( $token->is([T_COMMENT, T_DOC_COMMENT]) ) {
            return [
                'callable' => [self::class, 'splitComment'],
                'placement' => PlacementEnum::REPLACE,
                'arguments' => [
                    !!($this->flags & self::SKIP_LINE_TRAILING_WS)
                ]
            ];
        }

        if( $token->is(T_ENCAPSED_AND_WHITESPACE) ) {
            return [
                'callable' => [self::class, 'splitStringWithWhitespace'],
                'placement' => PlacementEnum::REPLACE,
                'arguments' => [
                    T_CONSTANT_ENCAPSED_STRING,
                    !!($this->flags & self::SKIP_LINE_TRAILING_WS)
                ]
            ];
        }

        if( $token->is(T_INLINE_HTML) ) {
            return [
                'callable' => [self::class, 'splitStringWithWhitespace'],
                'placement' => PlacementEnum::REPLACE,
                'arguments' => [
                    T_INLINE_HTML,
                    !!($this->flags & self::SKIP_LINE_TRAILING_WS)
                ]
            ];
        }

        return false;
    }

    /**
     * Atomized a given whitespace token at line breaks.
     *
     * @param \PhpToken $token T_WHITESPACE token.
     * @return array A list of whitespace tokens were line breaks are
     *               represented by the custom T_WHITESPACE_LINE_BREAK.
     */
    public static function whitespaceStringToLineTokens(
        \PhpToken $token,
    ): array {

        if( !$token->is(T_WHITESPACE) ) {
            throw new \UnexpectedValueException(
                "Token must be of T_WHITESPACE type"
            );
        }

        $line = $token->line;
        $pos = $token->pos;
        $to_ignore = [' ', "\t", "\v", "\0"];
        $non_line_break_ws = $line_break_ws = '';
        $text_len = strlen($token->text);
        $result = [];

        for ($i = 0; $i < $text_len; $i++) {

            $char = $token->text[$i];

            if( in_array($char, $to_ignore) ) {

                $non_line_break_ws .= $char;

                if( $line_break_ws ) {

                    $result[] = new \PhpToken(
                        T_WHITESPACE_LINE_BREAK, $line_break_ws, $line, $pos
                    );

                    $pos += strlen($line_break_ws);
                    $line += substr_count($line_break_ws, "\n");
                    $line_break_ws = '';
                }

            } else {

                $line_break_ws .= $char;

                if( $non_line_break_ws ) {

                    $result[] = new \PhpToken(
                        T_WHITESPACE, $non_line_break_ws, $line, $pos
                    );

                    $pos += strlen($non_line_break_ws);
                    $non_line_break_ws = '';
                }
            }
        }

        if( $non_line_break_ws ) {

            $result[] = new \PhpToken(
                T_WHITESPACE,
                $non_line_break_ws,
                $line,
                $pos
            );

            $pos += strlen($non_line_break_ws);
        }

        if( $line_break_ws ) {

            $result[] = new \PhpToken(
                T_WHITESPACE_LINE_BREAK, $line_break_ws, $line, $pos
            );

            $pos += strlen($line_break_ws);
        }

        return $result;
    }

    /**
     * Atomizes cast token.
     *
     * @param \PhpToken $token Any of the cast tokens, eg. T_BOOL_CAST.
     * @return array Containing T_OPEN_PARENTHESIS, T_WHITESPACE (optional),
     *               T_STRING, T_WHITESPACE (optional), and T_CLOSE_PARENTHESIS.
     * @throws \UnexpectedValueException When given token is not of cast type.
     */
    public static function splitCast( \PhpToken $token ): array {

        if( !$token->is(EnhancedPhpToken::CAST_TOKENS) ) {
            throw new \UnexpectedValueException("Token must be of cast type.");
        }

        $text = $token->text;
        $line = $token->line;
        $pos = $token->pos;
        $contents = trim($text, '()');

        [
            $leading_whitespace,
            $trailing_whitespace,
            $leading_whitespace_len,
            $trailing_whitespace_len
        ] = self::getLeadingAndTrailingWhitespace($contents);

        $contents = trim($contents);

        $result = [
            new \PhpToken(T_OPEN_PARENTHESIS, '(', $line, $pos)
        ];

        $pos++;

        if( $leading_whitespace_len ) {

            $result[] = new \PhpToken(
                T_WHITESPACE,
                $leading_whitespace,
                $line,
                $pos
            );

            $pos += strlen($leading_whitespace);
        }

        $result[] = new \PhpToken(T_STRING, $contents, $line, $pos);
        $pos += strlen($contents);

        if( $trailing_whitespace_len ) {

            $result[] = new \PhpToken(
                T_WHITESPACE,
                $trailing_whitespace,
                $line,
                $pos
            );

            $pos += strlen($trailing_whitespace);
        }

        $result[] = new \PhpToken(T_CLOSE_PARENTHESIS, ')', $line, $pos);

        return $result;
    }

    /**
     * Atomizes attribute token.
     *
     * @param \PhpToken $token T_ATTRIBUTE token.
     * @return array Containing hash as T_STRING and T_OPEN_SQUARE_BRACKET.
     * @throws \UnexpectedValueException When given token is not of T_ATTRIBUTE
     *                                   type.
     */
    public static function splitAttribute( \PhpToken $token ): array {

        if( !$token->is(T_ATTRIBUTE) ) {
            throw new \UnexpectedValueException(
                "Token must be of T_ATTRIBUTE type"
            );
        }

        return [
            new \PhpToken(T_STRING, '#', $token->line, $token->pos),
            new \PhpToken(
                T_OPEN_SQUARE_BRACKET,
                '[',
                $token->line,
                ($token->pos + 1)
            ),
        ];
    }

    /**
     * Atomizes PHP's opening tag.
     *
     * @param \PhpToken $token T_OPEN_TAG token.
     * @return array Containing T_OPEN_TAG and optionally T_WHITESPACE.
     */
    public static function splitOpenTag( \PhpToken $token ): array {

        $start_tag_length = match(true) {
            /* Full opening tag will always be succeeded by a single whitespace
            character (unless it's a \r\n combination). Having said that, it's
            illegal to have any other characters succeeding this tag. */
            str_starts_with($token->text, '<?php') => 5,
            str_starts_with($token->text, '<?') => 2,
            default => null,
        };

        if( !$start_tag_length ) {
            throw new \Error("Unrecognized start tag");
        }

        $result = [
            new \PhpToken(
                T_OPEN_TAG,
                substr($token->text, 0, $start_tag_length),
                $token->line,
                $token->pos
            ),
        ];

        $tail = substr($token->text, $start_tag_length);

        if( $tail ) {

            $tail_tokens = self::whitespaceStringToLineTokens(
                new \PhpToken(
                    T_WHITESPACE,
                    $tail,
                    $token->line,
                    $start_tag_length
                )
            );

            $result = [
                ...$result,
                ...$tail_tokens
            ];
        }

        return $result;
    }

    /**
     * Atomizes PHP's closing tag.
     *
     * @param \PhpToken $token T_CLOSE_TAG token.
     * @return array Containing T_CLOSE_TAG and optionally
     *               T_WHITESPACE_LINE_BREAK.
     * @throws \UnexpectedValueException When given token is not of T_CLOSE_TAG
     *                                   type.
     */
    public static function splitCloseTag( \PhpToken $token ): array {

        if( !$token->is(T_CLOSE_TAG) ) {
            throw new \UnexpectedValueException(
                "Token must be of T_CLOSE_TAG type"
            );
        }

        $result = [
            new \PhpToken(
                T_CLOSE_TAG,
                substr($token->text, 0, 2),
                $token->line,
                $token->pos
            ),
        ];

        if( strlen($token->text) > 2 ) {

            /* The assumtion is that only a single line break can succeed a
            closing tag. */
            $result[] = new \PhpToken(
                T_WHITESPACE_LINE_BREAK,
                substr($token->text, 2),
                $token->line,
                ($token->pos + 2)
            );
        }

        return $result;
    }

    /**
     * Atomizes heredoc start string.
     *
     * @param \PhpToken $token T_START_HEREDOC token.
     * @return array Containing T_START_HEREDOC and T_WHITESPACE_LINE_BREAK.
     * @throws \UnexpectedValueException When given PHP token is not of
     *                                   T_START_HEREDOC type.
     */
    public static function splitHeredocStart( \PhpToken $token ): array {

        if( !$token->is(T_START_HEREDOC) ) {
            throw new \UnexpectedValueException(
                "Token must be of T_START_HEREDOC type"
            );
        }

        $text = $token->text;
        // Heredoc identifier must be succeeded by a newline.
        $trailing_whitespace = self::getTrailingWhitespace($text);
        $text = substr($text, 0, -strlen($trailing_whitespace));

        $result = [
            new \PhpToken(
                T_START_HEREDOC,
                $text,
                $token->line,
                $token->pos
            ),
            new \PhpToken(
                T_WHITESPACE_LINE_BREAK,
                $trailing_whitespace,
                $token->line,
                $token->pos
            )
        ];

        return $result;
    }

    /**
     * Atomizes heredoc end string.
     *
     * @param \PhpToken $token T_END_HEREDOC token.
     * @return array Containing T_WHITESPACE (optional) and T_END_HEREDOC.
     * @throws \UnexpectedValueException When given PHP token is not of
     *                                   T_END_HEREDOC type.
     */
    public static function splitHeredocEnd( \PhpToken $token ): array {

        if( !$token->is(T_END_HEREDOC) ) {
            throw new \UnexpectedValueException(
                "Token must be of T_END_HEREDOC type"
            );
        }

        [
            $leading_whitespace,
            $trailing_whitespace,
            $leading_whitespace_len,
            $trailing_whitespace_len
        ] = self::getLeadingAndTrailingWhitespace($token->text);

        $result = [];

        if( $leading_whitespace_len ) {

            $result[] = new \PhpToken(
                T_WHITESPACE,
                $leading_whitespace,
                $token->line,
                $token->pos
            );
        }

        $result[] = new \PhpToken(
            T_END_HEREDOC,
            substr($token->text, $leading_whitespace_len),
            $token->line,
            ($token->pos + $leading_whitespace_len)
        );

        return $result;
    }

    /**
     * Atomizes comment string.
     *
     * @param \PhpToken $token T_COMMENT or T_DOC_COMMENT token.
     * @return array A list of comment strings (T_COMMENT) with intervening
     *               T_WHITESPACE or T_WHITESPACE_LINE_BREAK tokens.
     * @throws \UnexpectedValueException When given PHP token is not of
     *                                   T_COMMENT or T_DOC_COMMENT type.
     */
    public static function splitComment(
        \PhpToken $token,
        bool $skip_line_trailing_ws = false
    ): array {

        if( !$token->is([T_COMMENT, T_DOC_COMMENT]) ) {
            throw new \UnexpectedValueException(
                "Token must be of T_COMMENT or T_DOC_COMMENT type"
            );
        }

        return self::splitStringWithWhitespace(
            $token,
            T_COMMENT,
            $skip_line_trailing_ws
        );
    }

    /**
     * Atomizes any string with possible whitespace characters
     *
     * @param \PhpToken $token         Any token.
     * @param int       $main_token_id Token to use for non-whitespace
     *                                 substrings.
     * @return array A list of chosen token substrings with intervening
     *               T_WHITESPACE or T_WHITESPACE_LINE_BREAK tokens.
     */
    public static function splitStringWithWhitespace(
        \PhpToken $token,
        int $main_token_id,
        bool $skip_line_trailing_ws = false
    ): array {

        $parts = explode("\n", $token->text);
        $parts_count = count($parts);

        if( $parts_count === 1 ) {
            return [$token];
        }

        $result = [];
        $line = $token->line;
        $pos = $token->pos;

        foreach( $parts as $i => $part ) {

            if( $part !== '' ) {

                [
                    $leading_whitespace,
                    $trailing_whitespace,
                    $leading_whitespace_len,
                    $trailing_whitespace_len
                ] = self::getLeadingAndTrailingWhitespace($part);

                $part = trim($part);

                if(
                    $leading_whitespace_len
                    && ($part !== '' || !$skip_line_trailing_ws)
                ) {

                    $result[] = new \PhpToken(
                        T_WHITESPACE,
                        $leading_whitespace,
                        $line,
                        $pos
                    );
                }

                $pos += $leading_whitespace_len;

                if( $part !== '' ) {

                    $result[] = new \PhpToken(
                        $main_token_id,
                        $part,
                        $line,
                        $pos
                    );

                    $pos += strlen($part);
                }

                if( $trailing_whitespace_len ) {

                    if( substr($trailing_whitespace, -1) === "\r" ) {

                        $trailing_whitespace = substr(
                            $trailing_whitespace,
                            offset: 0,
                            length: -1
                        );
                    }

                    if( !$skip_line_trailing_ws ) {

                        $result[] = new \PhpToken(
                            T_WHITESPACE,
                            $trailing_whitespace,
                            $line,
                            $pos
                        );
                    }
                }

                $pos += $trailing_whitespace_len;
            }

            if( $parts_count !== ($i + 1) ) {

                $result[] = new \PhpToken(
                    T_WHITESPACE_LINE_BREAK,
                    "\n",
                    $line,
                    $pos
                );

                $pos++;
            }

            $line++;
        }

        return $result;
    }

    /** Tells if level number will be counted for a given token. */
    public static function isLeveled( \PhpToken $token ): bool {

        return $token->is([
            '(',
            ')',
            '{',
            '}',
            '[',
            ']',
            T_CURLY_OPEN,
            T_DOLLAR_OPEN_CURLY_BRACES,
            T_ATTRIBUTE,
        ]);
    }

    /**
     * Returns a map of PHP token IDs associated to one or multiple language
     * feature classes.
     */
    public static function tokenToClassNameMap(): array {

        return [
            T_NAMESPACE => NDB::class,
            T_USE => NUDB::class,
            T_FUNCTION => [
                FDB::class,
                AFB::class,
            ],
            T_FN => AAFB::class,
        ];
    }

    /**
     * Replaces any whitespace characters positioned anywhere before the last
     * new line break in a given string.
     */
    public static function trimLineTrailingWhitespace( string $str ): string {

        if( str_contains($str, "\n") ) {

            $last_pos = strrpos($str, "\n");

            return (str_replace(
                [' ', "\t", "\v", "\0"],
                '',
                substr($str, 0, $last_pos)
            ) . substr($str, $last_pos));

        } else {

            return $str;
        }
    }

    /**
     * Translates given "namespace use declaration" type into a corresponding
     * class property name representing an import table.
     */
    public static function typeToImportTablePropertyName(
        NUDTE $type
    ): string {

        return match($type) {
            NUDTE::CLASS_LIKE => 'class_import_table',
            NUDTE::FUNCTION => 'function_import_table',
            NUDTE::CONSTANT => 'constant_import_table',
        };
    }

    /** Retrieves and returns trailing whitespace from a given string. */
    public static function getTrailingWhitespace( string $string ): string {

        $string_len = strlen($string);

        return substr(
            $string,
            $string_len - strspn(strrev($string), self::WHITESPACE_CHARS)
        );
    }

    /**
     * Retrieves and returns leading and trailing whitespace substring from a
     * given string.
     *
     * @param string $string String to analyze.
     * @return array Containing leading whitespace string, trailing whitespace
     *               string, leading ws length, and trailing ws length.
     */
    public static function getLeadingAndTrailingWhitespace(
        string $string
    ): array {

        $leading_whitespace = substr(
            $string,
            offset: 0,
            length: strspn($string, self::WHITESPACE_CHARS)
        );

        $leading_whitespace_len = strlen($leading_whitespace);
        $string_len = strlen($string);

        $trailing_whitespace = ( $leading_whitespace_len !== $string_len )
            ? self::getTrailingWhitespace($string)
            : '';

        return [
            $leading_whitespace,
            $trailing_whitespace,
            $leading_whitespace_len,
            strlen($trailing_whitespace)
        ];
    }
}
