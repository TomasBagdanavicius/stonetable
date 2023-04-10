<?php

/**
 * Tailored project directory recursive file iterator.
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

class RecursiveFileIterator extends \RecursiveIteratorIterator {

    public function __construct(
        \Traversable $iterator,
        public readonly ?ProjectRootDirectory $project_root_directory = null,
        int $mode = \RecursiveIteratorIterator::SELF_FIRST,
        int $flags = 0
    ) {

        parent::__construct($iterator, $mode, $flags);
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
