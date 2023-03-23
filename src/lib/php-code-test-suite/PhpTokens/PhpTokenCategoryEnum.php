<?php

/**
 * Enumerates meaningful token categories.
 *
 * Requires PHP 8.1 or higher.
 *
 * @package PHP Code Test Suite
 * @author Tomas Bagdanavičius <tomas.bagdanavicius@lwis.net>
 * @license MIT License
 * @copyright Copyright (c) 2023 LWIS Technologies <info@lwis.net>
 *            (https://www.lwis.net/)
 * @version 1.0.0
 * @since 1.0.0
 */

declare(strict_types=1);

namespace PCTS\PhpTokens;

enum PhpTokenCategoryEnum: string {

    case OPEN_TAG = 'open-tag';
    case CLOSE_TAG = 'close-tag';
    case VARIABLE = 'variable';
    case VARNAME_OPEN = 'varname-open';
    case VARNAME = 'varname';
    case VARNAME_CLOSE = 'varname-close';
    case WHITESPACE = 'whitespace';
    case COMMENT = 'comment';
    case CAST = 'cast';
    case STRING = 'string';
    case NUMBER = 'number';
    case KEYWORD = 'keyword';
    case EXPRESSION_KEYWORD = 'expression-keyword';
    case FUNCTION_LIKE_KEYWORD = 'function-like-keyword';
    case CONSTANT = 'constant';
    case COMPILE_TIME_CONSTANT = 'compile-time-constant';
    case OPERATOR = 'operator';
    case PUNCTUATION = 'punctuation';
    case CLASS_NAME = 'class-name';
    case FUNCTION = 'function';
    case NAMESPACE = 'namespace';
    case HEREDOC_OPEN = 'heredoc-open';
    case HEREDOC_CLOSE = 'heredoc-close';
    case INLINE_HTML = 'inline-html';
    case ATTRIBUTE_OPEN = 'attribute-open';
    case GENERIC_STRING = 'generic-string';
    case UNKNOWN = 'unknown';

}
