<?php

/**
 * Dedicated object for the project's root directory.
 *
 * PHP version 8.1
 *
 * @package   Project Directory
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.2
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PD;

require_once (__DIR__ . '/../php-code-test-suite/Autoload.php');

use PCTS\OutputText\OutputTextFormatter;

/** Defines methods specific to the project's root directory */
class ProjectRootDirectory extends ProjectDirectory {

    /**
     * Name of the directory that should be located at the root level of a
     * target project and contain the config file for that project.
     */
    public const SYS_CONFIG_DIR_NAME = '.config';

    /** Project config file's name. */
    public const SYS_CONFIG_FILE_NAME = 'project-directory-config.php';

    /** Reference to the file factory. */
    public readonly ProjectFileObjectFactory $factory;

    /** Stores data from project's config file (the $config variable). */
    public readonly array $config;

    /** Source directory path. */
    public readonly string $source_dirname;

    /** Tests directory path */
    public readonly string $tests_dirname;

    /** Directory path to the demo's static folder. */
    public readonly string $demo_static_dirname;

    /** Directory path to the units' static folder. */
    public readonly string $units_static_dirname;

    /** Directory containing all projects */
    public readonly string $projects_dirname;

    /**
     * Sets up main parameters.
     *
     * @param string        $dirname                  Project root directory
     *                                                path name.
     * @param \Closure|null $on_description_data      Func. that will be called
     *                                                each time description data
     *                                                is fetched.
     * @param \Closure|null $on_special_comment_setup Func. that will be called
     *                                                each time special comments
     *                                                are set up.
     */
    public function __construct(
        string $dirname,
        ?\Closure $on_description_data = null,
        public readonly ?\Closure $on_special_comment_setup = null
    ) {

        if( !file_exists($dirname) ) {
            throw new \UnexpectedValueException(
                "File $dirname was not found"
            );
        }

        if( !is_dir($dirname) ) {
            throw new \Error(
                "File $dirname is not a directory"
            );
        }

        $dirname = rtrim($dirname, '/\\');
        $config_file_pathname = self::joinPath(
            $dirname,
            self::SYS_CONFIG_DIR_NAME,
            self::SYS_CONFIG_FILE_NAME
        );

        if( file_exists($config_file_pathname) ) {

            include $config_file_pathname;

            if( empty($config) ) {
                throw new \Error(
                    "Config data structure was not found"
                );
            }

            $this->config = $config;
        }

        $this->source_dirname = ( $this->config['source_dirname']
            ?? self::joinPath($dirname, MainDirectoriesEnum::SOURCE->value) );
        $this->tests_dirname = ( $this->config['tests_dirname']
            ?? self::joinPath($dirname, MainDirectoriesEnum::TESTS->value) );
        $this->demo_static_dirname = self::joinPath(
            $this->tests_dirname,
            'demo',
            BranchedTestDirectory::STATIC_DIR_NAME
        );
        $this->units_static_dirname = self::joinPath(
            $this->tests_dirname,
            'units',
            BranchedTestDirectory::STATIC_DIR_NAME
        );
        $this->projects_dirname = dirname($dirname);
        $this->factory = new ProjectFileObjectFactory($this);
        $patterns = $this->getPatterns();

        foreach( $patterns as $data ) {
            $this->factory->add(...$data);
        }

        parent::__construct($dirname, $this, $on_description_data);
    }

    /**
     * Gives all file patterns and other relevant data to be used when a pattern
     * is matched.
     */
    public function getPatterns(): array {

        return [
            [
                'pattern' => self::joinPath($this->source_dirname, '*'),
                'class_name' => 'PD\SourceFile',
                'type' => 'file',
            ], [
                'pattern' => self::joinPath($this->tests_dirname, 'demo'),
                'class_name' => 'PD\BranchedTestDirectory',
                'type' => 'dir',
            ], [
                'pattern' => self::joinPath(
                    $this->tests_dirname,
                    'demo',
                    'static',
                    '*'
                ),
                'class_name' => 'PD\StaticFile',
                'type' => 'file',
                'dependencies' => [
                    self::joinPath($this->tests_dirname, 'demo'),
                ],
            ], [
                'pattern' => self::joinPath(
                    $this->tests_dirname,
                    'demo',
                    'playground',
                    '*'
                ),
                'type' => 'file',
                'class_name' => 'PD\PlaygroundFile',
                'dependencies' => [
                    self::joinPath($this->tests_dirname, 'demo'),
                ],
            ], [
                'pattern' => self::joinPath($this->tests_dirname, 'units'),
                'type' => 'dir',
                'class_name' => 'PD\BranchedTestDirectory',
            ], [
                'pattern' => self::joinPath(
                    $this->tests_dirname,
                    'units',
                    'static',
                    '*'
                ),
                'type' => 'file',
                'class_name' => 'PD\StaticFile',
                'dependencies' => [
                    self::joinPath($this->tests_dirname, 'units'),
                ],
            ], [
                'pattern' => self::joinPath(
                    $this->tests_dirname,
                    'units',
                    'playground',
                    '*'
                ),
                'type' => 'file',
                'class_name' => 'PD\PlaygroundFile',
                'dependencies' => [
                    self::joinPath($this->tests_dirname, 'units'),
                ],
            ], [
                'pattern' => self::joinPath($this->tests_dirname, '*'),
                'type' => 'file',
                'class_name' => 'PD\TestFile',
            ],
        ];
    }

    /** Gets the project configuration. */
    public function getConfig(): array {

        return $this->config;
    }

    /** Gets source directory object. */
    public function getSourceDirectory(): ProjectDirectory {

        return $this->factory->fromPathname($this->source_dirname);
    }

    /** Gets test directory object. */
    public function getTestsDirectory(): ProjectDirectory {

        return $this->factory->fromPathname($this->tests_dirname);
    }

    /** Gets iterator that will loop through all files in source directory. */
    public function getSourceFileRecursiveIterator(): \Traversable {

        return $this->getSourceDirectory()->getRecursiveIterator();
    }

    /** Gets iterator that will loop through all files in test directory. */
    public function getTestFileRecursiveIterator(): \Traversable {

        return $this->getTestsDirectory()->getRecursiveIterator();
    }

    /**
     * Gets iterator that will loop through all files in demo static directory.
     */
    public function getDemoFileRecursiveIterator(): \Traversable {

        return $this->factory->fromPathname($this->demo_static_dirname)
            ->getRecursiveIterator();
    }

    /**
     * Gets iterator that will loop through all files in units static directory.
     */
    public function getUnitsFileRecursiveIterator(): \Traversable {

        return $this->factory->fromPathname($this->units_static_dirname)
            ->getSortedRecursiveIterator(
                \RecursiveIteratorIterator::CHILD_FIRST
            );
    }

    /** Gets URL relative to the location of directory of this file. */
    public static function getFileUrl(): string {

        return self::getUrlAddressFromPathname(__DIR__);
    }

    /** Gets the vendor name. */
    public function getVendorName(): ?string {

        return ( $this->config['vendor_name'] ?? null );
    }

    /**
     * Instantiates the output text formatter with class parameters.
     *
     * @return array Containing output text formatter object and known vendors
     *               array.
     */
    public function produceOutputTextFormatter(
        bool $format_html = true,
        bool $convert_links = true
    ): array {

        $config = $this->config;
        $vendor_name = $config['vendor_name'];

        $vendor_data = [
            $vendor_name => $this->source_dirname,
        ];

        $known_vendors = [
            $vendor_name => [
                'project' => $this->getBasename(),
                'source' => $this->source_dirname,
                'base' => $this->pathname,
            ],
        ];

        if( !empty($config['import_vendors']) ) {

            $import_vendors = $config['import_vendors'];

            foreach( $import_vendors as $project_name ) {

                $project_dirname = self::joinPath(
                    $this->projects_dirname,
                    $project_name
                );
                $project_instance = new self($project_dirname);

                if( $import_vendor_name = $project_instance->getVendorName() ) {

                    $vendor_data[$import_vendor_name]
                        = $project_instance->source_dirname;

                    $known_vendors[$import_vendor_name] = [
                        'project' => $project_instance->getBasename(),
                        'source' => $project_instance->source_dirname,
                        'base' => $project_instance->pathname,
                    ];
                }
            }
        }

        $output_text_formatter = new OutputTextFormatter(
            shorten_paths: [
                $_SERVER['DOCUMENT_ROOT'],
                $this->projects_dirname,
            ],
            vendor_data: $vendor_data,
            ide_uri_format: $config['ide_uri_format'],
            format_html: $format_html,
            convert_links: $convert_links
        );

        return [
            $output_text_formatter,
            $known_vendors
        ];
    }

    /** A static method that builds current URL. */
    public static function getUrlAddress(): string {

        $result = 'http';
        $is_secure = false;

        if(
            ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' )
            || $_SERVER['SERVER_PORT'] == '443'
        ) {
            $is_secure = true;
            $result .= 's';
        }

        $result .= ('://' . $_SERVER['SERVER_NAME']);
        $port = $_SERVER['SERVER_PORT'];

        if(
            (!$is_secure && $port != '80')
            || ($is_secure && $port != '443')
        ) {
            $result .= (':' . $port);
        }

        return $result;
    }

    /**
     * Statically builds URL relative to the given pathname.
     *
     * @param string $pathname Path name.
     */
    public static function getUrlAddressFromPathname(
        string $pathname
    ): false|string {

        $doc_root = ( $_SERVER['DOCUMENT_ROOT'] ?: $_SERVER['DOC_ROOT'] );

        if( !str_starts_with($pathname, $doc_root) ) {
            return false;
        }

        $uri_path = substr($pathname, strlen($doc_root));
        $uri_path = preg_replace('#\\\+#', '/', $uri_path);

        return (self::getUrlAddress() . $uri_path);
    }

    /**
     * Joins given function arguments with a directory separator string
     *
     * @return string If no arguments are given, it returns an empty string,
     *                otherwise a joined string.
     */
    public static function joinPath(): string {

        if( !func_num_args() ) {
            return '';
        }

        return implode(DIRECTORY_SEPARATOR, func_get_args());
    }
}
