<?php
return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP56Migration' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'heredoc_to_nowdoc' => true,
        'no_short_echo_tag' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'semicolon_after_instruction' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
    )
;