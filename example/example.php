<?php

require dirname(__DIR__) . '/lib/TemplateRenderer.php';

$renderer = new TemplateRenderer(
    __DIR__ . '/templates',
    '.php',
    __DIR__ . '/templates_compiled',
    __DIR__ . '/templates_strings'
);

if (isset($_SERVER['argv'][1])) {
    $renderer->setCompiledLang($_SERVER['argv'][1]);
}

$renderer->render('fruits', [
    'show_fruits' => true,
    'fruits' => ['apple', 'banana', 'cherry'],
]);
