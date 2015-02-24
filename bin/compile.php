#!/usr/bin/env php
<?php

/**
 * This script is a command-line invoker of TemplateCompiler.
 *
 * See `lib/TemplateCompiler.php` in this repo for details on the compilation
 * process.
 */
if (!class_exists('TemplateCompiler')) {
    require dirname(__DIR__) . '/lib/TemplateCompiler.php';
}

$opt = getopt('t:c:a:s:x:o:D');
function usage($code) {
    echo "Usage: {$_SERVER['PHP_SELF']} <options>\n\n" .
         "Options:\n" .
         "  -t <path>    Template dir\n" .
         "  -c <path>    Compiled template dir\n" .
         "  -s <path>    Strings dir\n" .
         "  -a <csv>     Langs to generate strings for\n" .
         "  -x <suffix>  Template suffix (default: .php)\n" .
         "  -o <path>    If specified, compile just one template\n" .
         "  -D           If specified, remove un-used string entries\n";
    exit($code);
}
if (empty($opt['t']) || empty($opt['c']) || empty($opt['s'])) {
    usage(0);
}

$template_dir = $opt['t'];
$compiled_dir = $opt['c'];
$strings_dir = $opt['s'];
$one_template = isset($opt['o']) ? $opt['o'] : null;
$langs = isset($opt['a']) ? array_map('trim', explode(',', $opt['a'])) : [];
$suffix = isset($opt['x']) ? $opt['x'] : '.php';
$should_prune = isset($opt['D']);

$compiler = new TemplateCompiler(
    $template_dir, $suffix, $compiled_dir, $strings_dir, $langs
);
$compiler->setPrune($should_prune);

if ($one_template) {
    $compiler->compileOne($one_template);
} else {
    $compiler->compileAll();
}
