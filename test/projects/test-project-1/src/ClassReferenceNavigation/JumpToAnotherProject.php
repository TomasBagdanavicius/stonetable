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

/* The current project is set up using vendor name "VendorName". It also imports
vendor "VendorName2" (mind trailing number "2") as project "test-project-2".
Having done this configuration and used "VendorName2" in the top level of the
namespace name, this should now navigate to "test-project-2". */
use VendorName2\Jump;
