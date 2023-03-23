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

// Grouped namespace use declarations with a subname.
use VendorName\{
    ClassReferenceNavigation\ClassA,
    ClassReferenceNavigation\ClassB as CB
};

class ClassC
{
    public function __construct() {
        $classB = new CB($this);
        $classA = new ClassA($classB, $this);
    }
}
