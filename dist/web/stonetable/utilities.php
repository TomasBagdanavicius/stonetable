<?php

declare(strict_types=1);

require_once 'config.php';
require_once (LIB_PATH . '/project-directory/Autoload.php');

use PD\ProjectDirectory;
use PD\ProjectFile;
use PD\ProjectRootDirectory;
use PD\SourceFile;
use PD\SpecialComment;
use PD\StaticFile;
use PD\TestFile;

/**
 * Sends HTTP response headers required to allow sharing it with requesting code
 * from any origin.
 */
function send_access_control_http_headers(): void {

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: "GET, OPTIONS"');
}

/**
 * Retrieves value from the global $_GET variable.
 *
 * @param string $key     Index key.
 * @param mixed  $default Default value to use when value is not available.
 */
function get_value_exists( string $key, mixed $default = null ): mixed {

    if( isset($_GET[$key]) && is_string($_GET[$key]) && $_GET[$key] !== '' ) {
        return $_GET[$key];
    } else {
        return $default;
    }
}

/**
 * Sends a final JSON error response.
 *
 * @param string $message Error message text.
 */
function send_error( string $message ): never {

    header('Content-Type: application/json; charset=utf-8');

    $result = json_encode([
        'status' => 0,
        'message' => $message,
    ]);

    die($result);
}

/**
 * Sends a final JSON success message.
 *
 * @param array  $data   Data payload.
 * @param array $params Meta data (aka. header data).
 */
function send_success( array $data, array $params = null ): never {

    header('Content-Type: application/json; charset=utf-8');

    $full_data = ( $params ?? [] );
    $full_data['data'] = $data;

    $result = json_encode([
        'status' => 1,
        ...$full_data,
    ]);

    die($result);
}

send_access_control_http_headers();

/** Tells if the running server is localhost. */
function is_local_server(): bool {
    return (
        isset($_SERVER['REMOTE_ADDR'])
        && in_array($_SERVER['REMOTE_ADDR'], [
            '127.0.0.1',
            '::1'
        ])
    );
}

/** Returns URL to the web directory. */
function get_web_url() {
    return ProjectRootDirectory::getUrlAddressFromPathname(__DIR__);
}

/** Returns URL to the data server directory. */
function get_api_url() {

    return ProjectRootDirectory::getUrlAddressFromPathname(
        __DIR__ . DIRECTORY_SEPARATOR . 'api'
    );
}

/** Returns endpoint URL that can be used to rebuild special comments. */
function get_rebuild_special_comments_endpoint(
    ProjectFile $project_file
): string {

    return sprintf(
        '%s/rebuild-special-comments.php?project_path=%s&path=%s',
        get_api_url(),
        rawurlencode($project_file->root_directory->dirname),
        rawurlencode($project_file->getRelativePathname())
    );
}

/** Gets endpoint URL that is used to create a demo file equivalent. */
function get_demo_file_create_endpoint(
    ProjectFile $project_file
): string {

    return sprintf(
        '%s/create-demo-file.php?project_path=%s&path=%s',
        get_api_url(),
        rawurlencode($project_file->root_directory->dirname),
        rawurlencode($project_file->getRelativePathname())
    );
}

/**
 * Gets endpoint URL that is used to create a playground file equivalent.
 */
function get_playground_file_create_endpoint(
    ProjectFile $project_file
): string {

    return sprintf(
        '%s/create-playground-file.php?project_path=%s&path=%s',
        get_api_url(),
        rawurlencode($project_file->root_directory->dirname),
        rawurlencode($project_file->getRelativePathname())
    );
}

/**
 * Builds web application's URL.
 *
 * @param ProjectRootDirectory  $root_directory Project base directory.
 * @param ProjectFile|null      $main_file      File that should be opened
 *                                              in the apps's main panel.
 * @param ProjectDirectory|null $side_directory Directory that should be
 *                                              opened in the apps's side panel.
 */
function get_app_url(
    ProjectRootDirectory $root_directory,
    ?ProjectDirectory $side_directory = null,
    ?ProjectFile $main_file = null
): string {

    $url = (
        get_web_url()
        . '/app/?project='
        . rawurlencode(basename($root_directory->pathname))
    );

    if( $side_directory ) {
        $url .= (
            '&side='
            . rawurlencode($side_directory->getRelativePathname())
        );
    }

    if( $main_file ) {
        $url .= (
            '&main='
            . rawurlencode($main_file->getRelativePathname())
        );
    }

    return $url;
}

/** Custom application link comment line that will act as a special comment. */
class AppSpecialComment extends SpecialComment {

    /** A prefix to the source file link in the source comment line. */
    public const LINE_PREFIX = "App";

    public function __construct(
        public readonly ProjectFile $project_file
    ) {

        parent::__construct(self::LINE_PREFIX);
    }

    /** Gets the content part of the comment line. */
    public function getContent(): ?string {

        return get_app_url(
            root_directory: $this->project_file->root_directory,
            main_file: $this->project_file
        );
    }
}

/**
 * Appoints custom special comment objects based on provided project file type.
 *
 * @param array       $special_comment_storage A named list of special comments.
 * @param ProjectFile $project_file            Project file object that will
 *                                             recive the custom special
 *                                             comments.
 * @return array|null Null when no custom comments should be appointed.
 */
function on_special_comment_setup(
    array $special_comment_storage,
    ProjectFile $project_file
): ?array {

    if(
        ($project_file instanceof SourceFile)
        || ($project_file instanceof TestFile)
    ) {

        return [
            'app' => new AppSpecialComment($project_file),
        ];

    } else {

        return null;
    }
}

/**
 * Sets up a instance of the project root directory object.
 *
 * @param string $pathname Path name to the project.
 */
function get_project_root_object( string $pathname ): ProjectRootDirectory {

    $on_description_data = function(
        array $data,
        ProjectFile $project_file
    ): array {

        $is_supported_file_type = $project_file->isSupportedFileType();

        if(
            $is_supported_file_type
            && ($project_file instanceof ProjectFile)
        ) {
            $data['rebuildSpecialCommentsUrl']
                = get_rebuild_special_comments_endpoint($project_file);
        }

        if(
            $is_supported_file_type
            && ($project_file instanceof SourceFile)
            && !$project_file->hasDemoFile()
        ) {
            $data['demoFileCreateUrl'] = get_demo_file_create_endpoint(
                $project_file
            );
        }

        if(
            ($project_file instanceof StaticFile)
            && !$project_file->hasPlaygroundFile()
        ) {
            $data['playgroundFileCreateUrl']
                = get_playground_file_create_endpoint($project_file);
        }

        return $data;
    };

    try {

        return new ProjectRootDirectory(
            $pathname,
            on_description_data: $on_description_data,
            on_special_comment_setup: on_special_comment_setup(...)
        );

    } catch( Throwable $exception ) {

        send_error(
            "Could not initialize project directory: {$exception->getMessage()}"
        );
    }
}

/**
 * Wraps hyperlinks around URLs found in the given text.
 *
 * @param string $text Text string where it will look for URLs.
 * @return string String where all URLs are wrapped inside hyperlinks.
 */
function wrap_links_around_urls( string $text ): string {

    $regex = '/'
        // Match http(s)://, ftp://, or www.
        . '(?:https?:\/\/|ftp:\/\/|(www\.))'
        . '('
        // Known hosts.
        . '(?:localhost|127\.0\.0\.1'
        // Valid domain pattern.
        . '|[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b)'
        // Match any valid path or query parameter, including &amp;
        . '(?:[-a-zA-Z0-9()@:%_\+.~#?&\/\/=;]*[^\)\s])?'
        . ')'
        . '/';

    // Replace URLs with links.
    return preg_replace_callback($regex, function( array $matches ): string {

        [$url, $is_www, $scheme_relative] = $matches;
        $link_text = $url;

        if( $is_www === 'www.' ) {
            $url = ('http://' . $url);
        }

        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            $url,
            $link_text
        );

    }, $text);
}

/**
 * Builds a navigation element.
 *
 * @param string $project        The associated project.
 * @param string $path           The relative path to a file in the project.
 * @param string $text           The text to display within the element.
 * @param string|null $path_type The type of path - "file" or "directory".
 * @param string $tagname        The HTML tag name to use for the element.
 *
 * @return string The fully built navigation element as an HTML string.
 */
function build_navigation_elem(
    string $project,
    string $path,
    string $text,
    ?string $path_type = null,
    string $tagname = 'span'
): string {

    $result = sprintf(
        '<%s data-project="%s" data-relative-path="%s"',
        $tagname,
        $project,
        $path
    );

    if( $path_type ) {
        $result .= sprintf(
            ' data-path-type="%s"',
            $path_type
        );
    }

    $result .= sprintf(
        '>%s</%s>',
        $text,
        $tagname
    );

    return $result;
}

/**
 * Wraps special HTML code around file:// URIs or strings resembling a valid
 * pathname
 *
 * @param string $text          Text string where replacements will be made
 * @param array  $known_vendors An array of vendor data by base project path
 * @return string Modified text string
 */
function wrap_meta_around_pathnames(
    string $text,
    array $known_vendors
): string {

    $path_prefixes = array_keys($known_vendors);
    $regex = '#'
        // Allowed path prefixes.
        . '(' . implode('|', array_map('preg_quote', $path_prefixes)) . ')'
        // Accepted filepath characters.
        . '([\p{L}0-9' . preg_quote("_-:!?.*|/\\") . ']+)'
        // Flags.
        . '#u';

    preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE);

    if( $matches[0] ) {

        $ds = DIRECTORY_SEPARATOR;
        $path_before = 'file://';
        $path_before_len = strlen($path_before);
        $offset = 0;

        foreach( $matches[0] as $i => $data ) {

            [$pathname, $pos] = $data;
            $pathname_len = strlen($pathname);

            foreach( $path_prefixes as $path_prefix ) {

                if( str_starts_with($pathname, $path_prefix) ) {
                    $meta = $known_vendors[$path_prefix];
                    break;
                }
            }

            if(
                $pathname === $meta['source']
                || str_starts_with($pathname, $meta['source'] . $ds)
                || $pathname === $meta['demo_static']
                || str_starts_with($pathname, $meta['demo_static'] . $ds)
                || $pathname === $meta['units_static']
                || str_starts_with($pathname, $meta['units_static'] . $ds)
            ) {

                $relative_path = substr($pathname, (strlen($meta['base']) + 1));
                $inner_text = $pathname;
                $pos += $offset;
                $off = ($pos - $path_before_len);

                if(
                    $pos >= $path_before_len
                    && substr($text, $off, $path_before_len) === $path_before
                ) {
                    $pos -= $path_before_len;
                    $inner_text = ($path_before . $inner_text);
                }

                $replace = build_navigation_elem(
                    $meta['project'],
                    $relative_path,
                    $inner_text,
                    ( ( is_dir($pathname) )
                        ? 'directory'
                        : 'file' )
                );
                $replace_len = strlen($inner_text);
                $text = substr_replace(
                    $text,
                    $replace,
                    $pos,
                    $replace_len
                );
                // Modify offset by the number of new characters added.
                $offset += (strlen($replace) - $replace_len);
            }
        }
    }

    return $text;
}
