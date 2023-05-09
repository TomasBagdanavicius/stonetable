<?php

/**
 * Class reference navigation test.
 *
 * Class reference navigation allows class like namespace names to be clickable
 * in the source code preview. The system is capable to resolve any class like
 * namespace name and translate it into a file path name that matches the
 * hierarchy path of the corresponding namespace name.
 */

declare(strict_types=1);

namespace VendorName\ClassReferenceNavigation;

// Typical namespace use declaration.
use VendorName\ClassReferenceNavigation\ClassB;
// Alias.
use VendorName\ClassReferenceNavigation\ClassC as CC;
// Not backed up by a file: unclickable.
use VendorName\ClassReferenceNavigation\ClassD;

class ClassA
{
    public function __construct(
        public readonly ?ClassB $classB = null,
        public readonly ?CC $classC = null
    ) {
        $this->classC ??= new CC();
        // Predefined: unclickable.
        $stdClass = new \stdClass;
    }
}
