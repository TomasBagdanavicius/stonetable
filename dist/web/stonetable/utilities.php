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

    return ( isset($_GET[$key]) && $_GET[$key] !== '' )
        ? $_GET[$key]
        : $default;
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

    $on_special_comment_setup = function(
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
    };

    try {

        return new ProjectRootDirectory(
            $pathname,
            on_description_data: $on_description_data,
            on_special_comment_setup: $on_special_comment_setup
        );

    } catch( Throwable $exception ) {

        send_error(
            "Could not initialize project directory: {$exception->getMessage()}"
        );
    }
}
