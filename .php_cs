<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude(['vendor','lib','classes'])
    ->in(__DIR__);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_blank_lines_after_class_opening' => true,
    ])
    ->setFinder($finder);
