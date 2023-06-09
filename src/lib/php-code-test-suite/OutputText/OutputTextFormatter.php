<?php

/**
 * Enhances output text readability.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.7
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PCTS\OutputText;

use PCTS\PhpTokens\EnhancedPhpToken;

class OutputTextFormatter {

    /** Files with the following file formats will be shortened. */
    public array $editable_file_formats = [
        'php',
        'html',
        'js',
        'txt',
    ];

    /**
     * @param array  $shorten_paths  A list of paths that should be shortened.
     * @param array  $vendor_data    An associative array with vendor data where
     *                               key should represent vendor name, and value
     *                               path to vendor project.
     * @param string $ide_uri_format IDE's file open uri format, eg.
     *                               vscode://file/{file}[:{line}][:{column}]
     * @param bool   $format_html    Whether to format resulting strings in HTML
     *                               format.
     * @param bool   $convert_links  Whether to convert file paths to links,
     *                               when $format_html is set to true.
     */
    public function __construct(
        public array $shorten_paths,
        public array $vendor_data,
        public string $ide_uri_format,
        public bool $format_html = true,
        public bool $convert_links = true
    ) {

        usort(
            $this->shorten_paths,
            [self::class, 'compareTwoNamespaceNames']
        );

        $this->shorten_paths = array_reverse($this->shorten_paths);
    }

    /** Tells if the given value is recognized as a qualified pathname. */
    public function isQualifiedPathname(
        string $value
    ): bool {

        if( !$this->shorten_paths ) {
            return false;
        }

        foreach( $this->shorten_paths as $shorten_path ) {

            if( str_starts_with($value, $shorten_path) ) {
                return true;
            }
        }

        return false;
    }

    /** Shortens a given pathname. */
    public function shortenPathname(
        string $pathname
    ): string {

        if( !$this->shorten_paths ) {
            return $pathname;
        }

        foreach( $this->shorten_paths as $shorten_path ) {

            if( str_starts_with($pathname, $shorten_path) ) {

                return ('...'
                    . DIRECTORY_SEPARATOR
                    . substr($pathname, strlen($shorten_path) + 1));
            }
        }

        return $pathname;
    }

    /**
     * Formats a given pathname by attempting to shorten it and wrap into an IDE
     * HTML link.
     *
     * @param string     $pathname      Path name to format.
     * @param string     $text_suffix   An optional suffix to to be added to the
     *                                  text part of the resulting string.
     * @param int|null   $line_number   Optional line number to add to IDE link.
     * @param int|null   $column_number Optional column number to add to IDE
     *                                  link.
     * @param array|null $class_names   When links are used, add these classes.
     * @return string If global option is set to format in HTML and pathname
     *                represents an existing file, results in HTML format.
     */
    public function formatPathname(
        string $pathname,
        string $text_suffix = '',
        ?int $line_number = null,
        ?int $column_number = null,
        ?array $class_names = null
    ): string {

        if( !$this->isQualifiedPathname($pathname) ) {
            return ($pathname . $text_suffix);
        }

        return $this->formatPathnameWithText(
            $pathname,
            $this->shortenPathname($pathname),
            $text_suffix,
            $line_number,
            $column_number,
            $class_names
        );
    }

    /**
     * Formats qualified/verified pathname.
     *
     * @param string     $pathname      Path name reference.
     * @param string     $text          Text representation of the pathname.
     * @param string     $suffix        Text suffix to add after formatted text.
     * @param int|null   $line_number   Line number.
     * @param int|null   $column_number Column number.
     * @param array|null $class_names   When links are used, add these classes.
     * @return string If HTML formatting is enabled, HTML formatted text,
     *                otherwise just plain text
     */
    protected function formatPathnameWithText(
        string $pathname,
        string $text,
        string $suffix = '',
        ?int $line_number = null,
        ?int $column_number = null,
        ?array $class_names = null
    ): string {

        $file_exists = file_exists($pathname);

        if( $this->format_html ) {
            $path_classes = [];
            if( !$file_exists ) {
                $path_classes[] = 'no-file';
            }
            $text = self::formatPathnameAddWrappers($text, $path_classes);
        }

        $text = ($text . $suffix);
        $is_link = (
            $this->format_html
            && $file_exists
            && $this->convert_links
        );

        return ( $is_link )
            ? self::buildIdeHtmlLink(
                $pathname,
                $text,
                $line_number,
                $column_number,
                $class_names
            )
            : $text;
    }

    /** Adds outer and inner HTML wrappers to the given pathname. */
    public static function formatPathnameAddWrappers(
        string $pathname,
        array $path_classes = [],
        array $base_classes = []
    ): string {

        $basename = basename($pathname);
        $basename_len = strlen($basename);
        $extension = pathinfo($pathname, PATHINFO_EXTENSION);
        $path_classes = ['path', ...$path_classes];
        $base_classes = ['base', ...$base_classes];

        if( $extension === 'php' ) {
            $base_classes[] = 'ext-php';
        }

        return sprintf(
                '<span class="%s">',
                implode(' ', $path_classes)
            )
            . substr($pathname, 0, -$basename_len)
            . sprintf(
                '<span class="%s">%s</span>',
                implode(' ', $base_classes),
                substr($pathname, -$basename_len)
            )
            . '</span>';
    }

    /** Tells if the given value is recognized as a qualified namespace. */
    public function isQualifiedNamespace(
        string $value
    ): bool {

        $divider_pos = strpos($value, '\\');

        if( $divider_pos === false ) {
            return false;
        }

        $vendor_name = substr($value, 0, $divider_pos);

        return isset($this->vendor_data[$vendor_name]);
    }

    /** Runs all formatting tasks on the given text. */
    public function format(
        string $text
    ): string {

        $result = $this->shortenFilenamesAll($text);
        $result = $this->formatNamespaces($result);

        return $result;
    }

    /**
     * Shortens all filenames by all available path prefixes in the given text
     * string.
     */
    public function shortenFilenamesAll(
        string $text
    ): string {

        if( !$this->shorten_paths ) {
            return $text;
        }

        foreach( $this->shorten_paths as $shorten_path ) {
            $text = $this->shortenFilenamesByPath($text, $shorten_path);
        }

        return $text;
    }

    /** Formats all namespaces to HTML. */
    public function formatNamespaces(
        string $text,
        ?\Closure $match_handler = null
    ): string {

        if( !$text || !$this->vendor_data ) {
            return $text;
        }

        $vendor_names = array_map(
            fn( string $value ): string => preg_quote($value . '\\'),
            array_keys($this->vendor_data)
        );

        $regex_str = sprintf(
            // Mind the "preceeded by" character group.
            // Curly brackets "{}" are used to include "{closure}"
            '#(\s|^|\?|\(|"|\'|\||:|&)((%s)[A-Za-z0-9_{}\\\\]+)#m',
            implode('|', $vendor_names)
        );

        preg_match_all($regex_str, $text, $matches, PREG_OFFSET_CAPTURE);

        if( $matches ) {

            $offset = 0;

            foreach( $matches[2] as $index => $match ) {

                [$ns_name, $line_number] = $match;
                [$vendor_name, $ns_start_pos] = $matches[3][$index];
                $vendor_name = rtrim($vendor_name, '\\');
                $ns_name_len = strlen($ns_name);

                if( !$match_handler ) {
                    $ns_end_pos = ($ns_start_pos + $ns_name_len + $offset);
                    $is_func = (
                        isset($text[$ns_end_pos])
                        && $text[$ns_end_pos] === '('
                    );
                    $is_closure = (
                        !$is_func
                        && EnhancedPhpToken::namespaceToParts($ns_name)['base']
                            === '{closure}'
                    );
                    $ns_name_classes = ( !$is_func )
                        ? []
                        : ['func'];
                    if( !$is_func && !$is_closure ) {
                        $filename = $this->namespaceToFilename($ns_name);
                        if( !$filename || !file_exists($filename) ) {
                            $ns_name_classes[] = 'no-file';
                            $wrapper = $ns_name;
                        } elseif( $this->convert_links ) {
                            $wrapper = self::buildIdeHtmlLink(
                                $filename,
                                $ns_name
                            );
                        } else {
	                        $wrapper = $ns_name;
                        }
                    } else {
                        $wrapper = $ns_name;
                    }
                    $wrapper = $this->formatClassNameOrNamespace(
                        $wrapper,
                        $ns_name_classes
                    );
                    if( $is_func || $is_closure ) {
                        $wrapper .= '<span class="punc-brkt">(</span>'
                        . '<span class="punc-brkt">)</span>';
                        $ns_name_len += 2;
                    }
                } else {
                    $wrapper = $match_handler($ns_name, $vendor_name);
                }

                $text = substr_replace(
                    $text,
                    $wrapper,
                    ((int)$line_number + $offset),
                    $ns_name_len
                );

                $offset += (strlen($wrapper) - $ns_name_len);
            }
        }

        return $text;
    }

    /**
     * Shortens all filenames by a given path prefix in the given text string.
     */
    public function shortenFilenamesByPath(
        string $text,
        string $path_prefix
    ): string {

        if( !$text || !$path_prefix ) {
            return $text;
        }

        $regex_str = sprintf(
            // Not preceeded by "file/".
            '#(?<!file\/)(%s('
            // Accepted filepath characters.
            . '[\p{L}0-9' . preg_quote('_-!?.*|/\\') . ']+'
            . '(\.(%s))?))(\son\sline\s(\d+))?(:(\d+))?#mu',
            preg_quote($path_prefix . '/', '/'),
            implode('|', $this->editable_file_formats)
        );

        preg_match_all($regex_str, $text, $matches, PREG_OFFSET_CAPTURE);

        if( $matches ) {

            $offset = 0;

            foreach( $matches[1] as $index => $match ) {

                [$filename, $line_number] = $match;
                $relative_path = $matches[2][$index][0];
                $extension = $matches[3][$index][0];
                $text_relative_path = (
                    '...'
                    . DIRECTORY_SEPARATOR
                    . $relative_path
                );
                $filename_length = strlen($filename);
                $is_on_line = (
                    isset($matches[6][$index])
                    && !empty($matches[6][$index][0])
                    && $matches[6][$index][1] !== -1
                );
                $is_column_num = (
                    isset($matches[8][$index])
                    && !empty($matches[8][$index][0])
                    && $matches[8][$index][1] !== -1
                );
                $replace_text = $this->formatPathnameWithText(
                    $filename,
                    $text_relative_path,
                    line_number: match(true) {
                        $is_on_line => (int)$matches[6][$index][0],
                        $is_column_num => (int)$matches[8][$index][0],
                        default => null
                    },
                    class_names: ['file']
                );
                $text = substr_replace(
                    $text,
                    $replace_text,
                    ((int)$match[1] + $offset),
                    $filename_length
                );
                $offset_add = (strlen($replace_text) - $filename_length);
                $offset += $offset_add;

                // Captured line number.
                if(
                    $this->format_html
                    && ($is_on_line || $is_column_num)
                ) {

                    if( $is_on_line ) {
                        [$line_number, $line_number_pos] = $matches[6][$index];
                    } else {
                        [$line_number, $line_number_pos] = $matches[8][$index];
                    }

                    $line_number_length = strlen($line_number);
                    $replace_text = sprintf(
                        '<span class="line-num">%s</span>',
                        $line_number
                    );
                    $text = substr_replace(
                        $text,
                        $replace_text,
                        ((int)$line_number_pos + $offset),
                        $line_number_length
                    );
                    $offset += (strlen($replace_text) - $line_number_length);
                }
            }
        }

        return $text;
    }

    /** Converts a namespace to IDE open file HTML hyperlink. */
    public function namespaceToIdeHtmlLink(
        string $namespace,
        ?array $class_names = null,
        ?string $text = null,
    ): ?string {

        if( !$filename = $this->namespaceToFilename($namespace) ) {
            return null;
        }

        if( !file_exists($filename) ) {
            return null;
        }

        return self::buildIdeHtmlLink(
            $filename,
            ( $text ?: $namespace ),
            class_names: $class_names
        );
    }

    /**
     * Converts namespace string to PHP filename string according to global
     * vendor data.
     *
     * @return string|null Null when namespace does not contain an individual
     *                     vendor name component, or vendor name was not
     *                     recognized.
     */
    public function namespaceToFilename( string $namespace ): ?string {

        $divider_pos = strpos($namespace, '\\');

        if( $divider_pos === false ) {
            return null;
        }

        $vendor_name = substr($namespace, 0, $divider_pos);

        if( !isset($this->vendor_data[$vendor_name]) ) {
            return null;
        }

        return self::namespaceToFilenameStatic(
            $namespace,
            $vendor_name,
            $this->vendor_data[$vendor_name]
        );
    }

    /** Builds IDE open file URI string from a given file path */
    public function buildIdeUri(
        string $filename,
        ?int $line_number = null,
        ?int $column_number = null
    ): string {

        return self::parseIdeUriFormat(
            $this->ide_uri_format,
            $filename,
            $line_number,
            $column_number
        );
    }

    /** Builds IDE open file HTML hyperlink from a given file path. */
    public function buildIdeHtmlLink(
        string $filename,
        ?string $text = null,
        ?int $line_number = null,
        ?int $column_number = null,
        ?array $class_names = null
    ): string {

        $html_link = sprintf(
            '<a href="%s"%s>',
            $this->buildIdeUri($filename, $line_number, $column_number),
            ( ( !$class_names )
                ? ''
                : sprintf(
                    ' class="%s"',
                    implode(' ', $class_names)
                ) )
        );

        $html_link .= ( $text ?: $filename );
        $html_link .= '</a>';

        return $html_link;
    }

    /** Converts file URI to IDE open file URI. */
    public function fileUriToIdeUri(
        string $file_uri
    ): string {

        $prefix = 'file://';

        if( !str_starts_with($file_uri, $prefix) ) {
            throw new \ValueError(sprintf(
                "File URI must start with \"%s\" prefix",
                $prefix
            ));
        }

        return $this->buildIdeUri(
            substr($file_uri, strlen($prefix))
        );
    }

    /**
     * A static method that converts namespace string to PHP filename.
     *
     * @param string $namespace Namespace name.
     * @param string $prefix    Prefix to shift off the namespace (eg. vendor
     *                          name).
     * @param string $base_uri  Base file uri (eg. path name to vendor project).
     * @return string [description]
     */
    public static function namespaceToFilenameStatic(
        string $namespace,
        string $prefix,
        string $base_uri
    ): string {

        $file_uri = $base_uri;

        $file_uri .= substr(
            str_replace('\\', DIRECTORY_SEPARATOR, $namespace),
            strlen($prefix)
        );

        $file_uri .= '.php';

        return $file_uri;
    }

    /**
     * Shortens a filename by shifting off the document root path.
     *
     * @param string      $filename File path to shorten.
     * @param string|null $doc_root Document root path name (auto-detect when
     *                    null).
     */
    public static function shortenDocrootFilename(
        string $filename,
        ?string $doc_root = null
    ): string {

        if( !$doc_root ) {
            $doc_root = ($_SERVER['DOCUMENT_ROOT'] ?? null);
        }

        if( !$doc_root ) {
            return $filename;
        }

        if( str_starts_with($filename, $doc_root) ) {
            $filename = (
                '...'
                . DIRECTORY_SEPARATOR
                . substr($filename, strlen($doc_root) + 1)
            );
        }

        return $filename;
    }

    /**
     * Tells if the first given namespace string should be ordered above the
     * second given namespave string.
     */
    public static function compareTwoNamespaceNames(
        string $a,
        string $b
    ): int {

        if( $a === $b ) {
            return 0;
        }

        $a_parts = explode('\\', $a);
        $b_parts = explode('\\', $b);

        if( $a !== $b ) {

            if( str_starts_with($a, $b) ) {
                return 1;
            } elseif( str_starts_with($b, $a) ) {
                return -1;
            }
        }

        foreach( $a_parts as $index => $a_part ) {

            if( $a_part !== $b_parts[$index] ) {
                return strtolower($a_part) <=> strtolower($b_parts[$index]);
            }
        }
    }

    /**
     * Parses a given IDE open file URI format and fills it in with given
     * params.
     *
     * @param string   $ide_uri_format IDE's file open uri format, eg.
     *                                 vscode://file/{file}[:{line}][:{column}]
     * @param string   $filename       File path to fill in.
     * @param int|null $line_number    Optional line number to fill in.
     * @param int|null $column_number  Optional column number to fill in.
     * @return string Formatted IDE's file open URI string.
     */
    public static function parseIdeUriFormat(
        string $ide_uri_format,
        string $filename,
        ?int $line_number = null,
        ?int $column_number = null
    ): string {

        $known_names = [
            'file' => $filename,
            'line' => $line_number,
            'column' => $column_number,
        ];
        $result = $mem = '';
        $format_len = strlen($ide_uri_format);
        $curly_stack = [];
        $brackets_stack = [];
        $unresolved_curly = [];
        $resolved_curly = [];

        for( $i = 0; $i < $format_len; $i++ ) {

            $char = $ide_uri_format[$i];

            if( $char === '{' ) {

                $curly_stack[$i] = '';

            } elseif( $curly_stack ) {

                if( $char === '}' ) {

                    $opened_at = array_key_last($curly_stack);
                    $name = array_pop($curly_stack);

                    // Resolved by a known name.
                    if( isset($known_names[$name]) ) {

                        $v = $known_names[$name];
                        $resolved_curly[$opened_at] = $name;

                    // Unresolved.
                    } else {

                        $v = '';

                        // Non-nullified name.
                        if( !array_key_exists($name, $known_names) ) {
                            $v = ('{' . $name . '}');
                        }

                        $unresolved_curly[$opened_at] = $name;
                    }

                    if( $curly_stack ) {
                        $curly_stack[array_key_last($curly_stack)] .= $v;
                    } elseif( $brackets_stack ) {
                        $brackets_stack[array_key_last($brackets_stack)] .= $v;
                    } else {
                        $result .= $v;
                    }

                } else {

                    $curly_stack[array_key_last($curly_stack)] .= $char;
                }

            } elseif( $char === '[' ) {

                $brackets_stack[$i] = '';

            } elseif( $brackets_stack ) {

                $last_key = array_key_last($brackets_stack);

                if( $char === ']' ) {

                    $condition = true;

                    if( $unresolved_curly ) {

                        foreach( $unresolved_curly as $p => $n ) {

                            if( $p > $last_key && $p < $i ) {

                                $condition = false;
                                unset($unresolved_curly[$p]);
                            }
                        }
                    }

                    $resolved = false;

                    if( $resolved_curly ) {

                        foreach( $resolved_curly as $p => $n ) {

                            if( $p > $last_key && $p < $i ) {
                                $resolved = true;
                                break;
                            }
                        }
                    }

                    $str = array_pop($brackets_stack);

                    if( !$resolved ) {
                        $str = ('[' . $str . ']');
                    }

                    $mem = ($str . $mem);

                    if( $condition ) {

                        if( !$brackets_stack ) {
                            $result .= $mem;
                            $mem = '';
                        }

                    } else {

                        $mem = '';
                    }

                } else {

                    $brackets_stack[$last_key] .= $char;
                }

            } else {

                $result .= $char;
            }
        }

        return $result;
    }

    /** HTML formats given class name - either fully qualified or keyword. */
    public function formatClassNameOrNamespace(
        string $string,
        array $ns_name_classes = []
    ): string {

        $separator_pos = strrpos($string, '\\');

        // Namespace
        if( $separator_pos !== false ) {
            return $this->formatNamespaceName($string, $ns_name_classes);
        // Class name
        } else {
            return sprintf(
                '<span class="cls-name">%s</span>',
                $string
            );
        }
    }

    /**
     * HTML formats namespace name
     *
     * @param string $ns_name         Namespace name.
     * @param array  $ns_name_classes Classes to add to namespace name wrapper.
     * @return string HTML formatted namespace name.
     */
    public function formatNamespaceName(
        string $ns_name,
        array $ns_name_classes = []
    ): string {

        $separator_pos = strrpos($ns_name, '\\');

        if( $separator_pos === false ) {
            throw new \Exception(
                "Namespace name must contain backward slash separator"
            );
        }

        $base = substr($ns_name, ($separator_pos + 1));

        if( $base === '{closure}' ) {
            $base = self::getFormattedClosureString();
            $ns_name_classes[] = 'closure';
        }

        $ns_name_classes = ['ns-name', ...$ns_name_classes];

        return
            sprintf(
                '<span class="%s">',
                implode(' ', $ns_name_classes)
            )
            . substr($ns_name, 0, ($separator_pos + 1))
            . sprintf(
                '<span class="base">%s</span>',
                $base
            )
            . '</span>';
    }

    /** Returns HTML formatted debug string for closure. */
    public static function getFormattedClosureString(): string {

        return '<span class="punc punc-brkt">{</span>'
            . 'closure'
            . '<span class="punc punc-brkt">}</span>';
    }

    /** HTML formats a simplified function name string. */
    public function formatSimplifiedFunctionStr( string $func_str ): string {

        if( str_ends_with($func_str, '()') ) {
            $func_str = substr($func_str, -2);
        }

        $func_format = '<span class="func-name">%s</span>';
        $punc_html = '<span class="punc punc-brkt">(</span>'
            . '<span class="punc punc-brkt">)</span>';

        if( $func_str === '{closure}' ) {
            $func_str = self::getFormattedClosureString();
        }

        return (sprintf(
            $func_format,
            $func_str
        ) . $punc_html);
    }
}
