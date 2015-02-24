<?php

require dirname(__DIR__) . '/lib/TemplateRenderer.php';

$renderer = new TemplateRenderer(
    __DIR__ . '/templates', // Look for uncompiled templates here
    '.php', // Templates end with this file extension
    __DIR__ . '/templates_compiled', // Look for compiled templates here
    __DIR__ . '/templates_strings' // Look for localized strings here
);

if (isset($_SERVER['argv'][1])) {
    // In production, call `setCompiledLang` with the current language code.
    // This tells TemplateRenderer to use compiled templates + localized
    // strings instead of the original templates in `templates/`. In dev,
    // you can skip this step so that changes to files in `templates/` are
    // reflected immediately without having to run `bin/compile.php`.
    $renderer->setCompiledLang($_SERVER['argv'][1]);
}

// Render examples/fruits.php with some data
$renderer->render('fruits', [
    'show_fruits' => true,
    'fruits' => ['apple', 'banana', 'cherry'],
]);
