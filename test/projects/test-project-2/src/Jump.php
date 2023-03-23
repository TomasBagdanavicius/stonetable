<?php

/**
 * Class reference navigation to another project test.
 *
 * This file demonstrates how class reference navigation feature can take to not
 * just the same project files, but also another project (ie. another vendor)
 * files. To accomplish this you need to import other vendors into a project by
 * utilising the "import_vendors" configuration parameter in project config
 * file.
 */

/* The current project is set up using vendor name "VendorName2" (mind trailing
number "2"). It also imports vendor "VendorName" as project "test-project-1".
Having done this configuration and used "VendorName" in the top level of the
namespace name, this should now navigate to "test-project-1". */
use VendorName\ClassReferenceNavigation\JumpToAnotherProject;
