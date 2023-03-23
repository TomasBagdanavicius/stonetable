<?php

/**
 * Enhances output text readability.
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

namespace PCTS\OutputText;

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

                return ('.../'
                    . substr($pathname, strlen($shorten_path) + 1));
            }
        }

        return $pathname;
    }

    /**
     * Formats a given pathname by attempting to shorten it and wrap into an IDE
     * HTML link.
     *
     * @param string   $pathname      Path name to format.
     * @param string   $text_suffix   An optional suffix to to be added to the
     *                                text part of the resulting string.
     * @param int|null $line_number   Optional line number to add to IDE link.
     * @param int|null $column_number Optional column number to add to IDE link.
     * @return string If global option is set to format in HTML and pathname
     *                represents an existing file, results in HTML format.
     */
    public function formatPathname(
        string $pathname,
        string $text_suffix = '',
        ?int $line_number = null,
        ?int $column_number = null
    ): string {

        if( !$this->isQualifiedPathname($pathname) ) {
            return ($pathname . $text_suffix);
        }

        $text = ($this->shortenPathname($pathname)
            . $text_suffix);

        $is_link = (
            $this->format_html
            && $this->convert_links
            && file_exists($pathname)
        );

        return ( $is_link )
            ? self::buildIdeHtmlLink(
                $pathname,
                $text,
                $line_number,
                $column_number
            )
            : $text;
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

        if( $this->convert_links ) {
            $result = $this->convertNamespacesToHtmlLinks($result);
        }

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

    /** Converts all namespaces to HTML hyperlinks in the given text string. */
    public function convertNamespacesToHtmlLinks(
        string $text
    ): string {

        if( !$text || !$this->vendor_data ) {
            return $text;
        }

        $vendor_names = array_keys($this->vendor_data);

        // Mind the "preceeded by" character group.
        $regex_str = sprintf(
            '#(\s|^|\?|\(|"|\')((%s)[A-Za-z0-9\\\\]+)#m',
            implode('|', $vendor_names)
        );

        preg_match_all($regex_str, $text, $matches, PREG_OFFSET_CAPTURE);

        if( $matches ) {

            $offset = 0;

            foreach( $matches[2] as $index => $match ) {

                [$namespace, $line_number] = $match;
                $vendor_name = $matches[3][$index][0];

                $html_link = $this->namespaceToIdeHtmlLink($namespace);

                $text = substr_replace(
                    $text,
                    $html_link,
                    ((int)$match[1] + $offset),
                    strlen($match[0])
                );

                $offset += (strlen($html_link) - strlen($match[0]));
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
            '#(?<!file\/)(%s([a-zA-Z\/\_-]+(\.(%s))?))(\son\sline\s(\d+))?#m',
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
                $text_relative_path = ('.../' . $relative_path);

                $is_link = (
                    $this->format_html
                    && $this->convert_links
                    && file_exists($filename)
                );

                $replace_text = ( $is_link )
                    ? self::buildIdeHtmlLink(
                        $filename,
                        $text_relative_path,
                        ( (int)$matches[6][$index][0] ?: null )
                    )
                    : $text_relative_path;

                $text = substr_replace(
                    $text,
                    $replace_text,
                    ((int)$match[1] + $offset),
                    strlen($filename)
                );

                $offset += (strlen($replace_text) - strlen($filename));
            }
        }

        return $text;
    }

    /** Converts a namespace to IDE open file HTML hyperlink. */
    public function namespaceToIdeHtmlLink(
        string $namespace
    ): ?string {

        if( !$filename = $this->namespaceToFilename($namespace) ) {
            return null;
        }

        return self::buildIdeHtmlLink($filename, $namespace);
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
        ?int $column_number = null
    ): string {

        $html_link = sprintf(
            '<a href="%s">',
            $this->buildIdeUri($filename, $line_number, $column_number)
        );

        $html_link .= ( $text )
            ? $text
            : $filename;

        $html_link .= '</a>';

        return $html_link;
    }

    /** Converts file URI to IDE open file URI. */
    public function fileUriToIdeUri(
        string $file_uri
    ): string {

        $prefix = 'file://';

        if( !str_starts_with($file_uri, $prefix) ) {
            throw new \ValueError(
                "File URI must start with \"$prefix\" prefix"
            );
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
            str_replace('\\', '/', $namespace),
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
            $filename = ('.../' . substr($filename, strlen($doc_root) + 1));
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
}
