<?php

/**
 * Trait that contains methods suitable for any project file (including dirs).
 *
 * PHP version 8.1
 *
 * @package   Project Directory
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.1
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PD;

require_once (__DIR__ . '/../php-code-test-suite/Autoload.php');

use PCTS\OutputText\OutputTextFormatter;

trait FileTrait {

    /** Gets directory object representing file's parent directory.  */
    public function getParentProjectDirectory(): ?ProjectDirectory {

        $path = $this->getPath();

        if(
            $this->root_directory
            && $this->root_directory->pathname === $path
        ) {
            return null;
        }

        return new ProjectDirectory($path, $this->root_directory);
    }

    /** Builds IDE open file URI for this file. */
    public function getIdeUri(): string {

        return OutputTextFormatter::parseIdeUriFormat(
            $this->root_directory->config['ide_uri_format'],
            $this->pathname
        );
    }
}
