<?php

/**
 * Interface for builders that can provide PHP namespace use declaration type
 * hints.
 *
 * PHP version 8.1
 *
 * @package   PHP Code Test Suite
 * @author    Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license   MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version   1.0.2
 * @since     1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpTokens\Language;

use PCTS\PhpTokens\Language\NamespaceUseDeclarationTypeEnum;

interface CategoryHintInterface {

    /** Sets given namespace use declaration type. */
    public function setCategoryHint(
        NamespaceUseDeclarationTypeEnum $type
    ): void;
}
