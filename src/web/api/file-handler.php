<?php

declare(strict_types=1);

require_once (__DIR__ . '/../utilities.php');

if( !defined('PROJECTS_PATH') ) {
    send_error(sprintf(
        "Path to projects directory is not defined in %s",
        realpath(__DIR__ . '/../config.php')
    ));
}

if( !$project_pathname = get_value_exists('project_path') ) {
    send_error("Please provide a project path");
}

$path = get_value_exists('path', DIRECTORY_SEPARATOR);

require_once (LIB_PATH . '/php-code-test-suite/Autoload.php');

use PD\TestFile;
use PCTS\PhpTokens\Tokenizer;
use PCTS\PhpTokens\PhpTokenCategoryEnum;
use PCTS\PhpTokens\Language\NamespaceUseDeclarationTypeEnum as NUDTE;
use PCTS\OutputText\OutputTextFormatter;

$project_root_directory = get_project_root_object($project_pathname);
$project_file_object = $project_root_directory->find($path);

if( !$project_file_object ) {
    send_error("File with path \"$path\" was not found");
}

if( !$project_file_object->isFile() ) {
    send_error(
        "Path name {$project_file_object->pathname} does not represent a file"
    );
}

/**
 * Enumerates view handler names.
 */
enum ViewHandlersEnum: string {
    case SOURCE_CODE_PARTS = 'source-code';
    case OUTPUT_CODE_PARTS = 'demo-output';
}

$handler_name = ( $project_file_object instanceof TestFile )
    ? ViewHandlersEnum::OUTPUT_CODE_PARTS
    : ViewHandlersEnum::SOURCE_CODE_PARTS;

switch( $handler_name ) {
    case ViewHandlersEnum::OUTPUT_CODE_PARTS:

        /* Make the following global variables available in demo-page-init.php
        file. */
        $demo_filename = $project_file_object->pathname;
        $demo_format = 'json';

        require_once (__DIR__ . '/../demo-page-init.php');

        Demo\register_json_result_formatter(function(
            array $result
        ) use(
            $project_file_object,
            $handler_name,
        ) {

            $meta = $project_file_object->getDescriptionData();
            $meta['handlerName'] = $handler_name->value;

            return [
                'status' => 1,
                'data' => [
                    'meta' => $meta,
                    'parts' => $result,
                ],
            ];

        });

        include $demo_filename;

    break;

    case ViewHandlersEnum::SOURCE_CODE_PARTS:
    default:

        [$output_text_formatter, $known_vendors]
            = $project_root_directory->produceOutputTextFormatter();

        $tokenizer = new Tokenizer(
            code: $project_file_object->getContents(),
            flags: (Tokenizer::KEY_AS_LINE_NUMBER
            | Tokenizer::SPLIT_SPLITABLE_TOKENS
            | Tokenizer::CURRENT_AS_ENHANCED_TOKEN)
        );

        $parts = [];

        foreach( $tokenizer as $line_number => $enhanced_token ) {

            $category = $enhanced_token->getReusableCategory();
            $features = '';
            $namespace = $namespace_name = null;
            $php_token = $tokenizer->getElement();
            $is_whitespace_line_break
                = ($php_token->id === T_WHITESPACE_LINE_BREAK);

            if( !$is_whitespace_line_break ) {

                if( $category === PhpTokenCategoryEnum::CLASS_NAME ) {

                    if( !$tokenizer->isNamespaceUseDeclarationContext() ) {
                        $namespace_name = $enhanced_token->token->text;
                    } else {
                        $namespace = $tokenizer->getContext()->getNamespace();
                    }

                } elseif( $category === PhpTokenCategoryEnum::NAMESPACE ) {

                    if( !$tokenizer->isNamespaceDefinitionNamePhase() ) {

                        $last_component_type
                            = $enhanced_token->getNamespaceLastComponentType();

                        if( !$tokenizer->isNamespaceUseDeclarationContext() ) {

                            if( $last_component_type === NUDTE::CLASS_LIKE ) {
                                $namespace_name = $enhanced_token->token->text;
                            }

                        } else {

                            if( $last_component_type === NUDTE::CLASS_LIKE ) {
                                $namespace = $tokenizer->getContext()
                                    ->getNamespace();
                            }
                        }
                    }
                }

                if( $namespace_name || $namespace ) {

                    if( !$namespace ) {
                        $namespace = $tokenizer->resolveNamespaceName(
                            $namespace_name
                        );
                    }

                    $separator_pos = strpos($namespace, '\\');

                    $namespace_vendor = ( $separator_pos !== false )
                        ? substr($namespace, 0, $separator_pos)
                        : $namespace;

                    if( isset($known_vendors[$namespace_vendor]) ) {

                        $base = $known_vendors[$namespace_vendor]['base'];
                        $base_path_len = (strlen($base) + 1);

                        $features = sprintf(
                            ' data-project="%s" data-relative-path="%s"',
                            $known_vendors[$namespace_vendor]['project'],
                            substr(
                                $output_text_formatter->namespaceToFilename(
                                    $namespace
                                ),
                                offset: $base_path_len
                            )
                        );
                    }
                }

                $html = sprintf(
                    '<span class="%s"%s>%s</span>',
                    implode(' ', $enhanced_token->getClassNames()),
                    $features,
                    $enhanced_token->getInnerHtml()
                );

                if( !isset($parts[$line_number]) ) {
                    $parts[$line_number] = $html;
                } else {
                    $parts[$line_number] .= $html;
                }
            }
        }

        /* The last token is either single or a series of line breaks. Those
        line breaks will not be counted into the $line_number. */
        if( isset($is_whitespace_line_break) && $is_whitespace_line_break ) {
            $line_number += substr_count($php_token->text, "\n");
        }

        $meta = $project_file_object->getDescriptionData();
        $meta['handlerName'] = $handler_name->value;
        $meta['lineCount'] = ( $line_number ?? 0 );

        send_success([
            'meta' => $meta,
            'parts' => $parts,
        ]);

    break;
}
