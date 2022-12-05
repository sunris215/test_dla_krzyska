<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setLineEnding("\n")
    ->setRules([
        '@PSR2' => true,
        '@PhpCsFixer' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['align_double_arrow' => true],
        'no_short_echo_tag' => false,
        'not_operator_with_successor_space' => false,
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'void_return' => true,
        'native_function_invocation' => ['include' => ['@all']],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
    ])
    ->setFinder($finder);