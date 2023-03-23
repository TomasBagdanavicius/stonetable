<?php

/**
 * Tailored project directory file iterator.
 *
 * Requires PHP 8.1 or higher.
 *
 * @package Project Directory
 * @author Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace PD;

class FileIterator extends \IteratorIterator {

    public function __construct(
        \Traversable $iterator,
        public readonly ?ProjectRootDirectory $project_root_directory = null,
        ?string $class = null
    ) {

        parent::__construct($iterator, $class);
    }

    /**
     * Gets the current file.
     *
     * @return mixed When project root directory is available, it will use the
     *               factory to produce a dedicated file object.
     */
    public function current(): mixed {

        return ( $this->project_root_directory )
            ? $this->project_root_directory->factory->fromPathname(
                parent::current()->getPathName()
            )
            : parent::current();
    }
}
