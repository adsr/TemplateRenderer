<?php

/**
 * TemplateRenderer is a simple template rendering class that supports template
 * inheritance, nested templates, and easy localization. It is intended to work
 * with compiled templates outputted by the TemplateCompiler class. The
 * template language is PHP itself.
 *
 * Example usage:
 *
 *     $renderer = new TemplateRenderer(
 *         'templates/',   // Look for templates in this dir
 *         '.php',         // File extension of templates
 *         'templates_c/', // Look for compiled templates in this dir
 *         'templates_s/'  // Look for compiled strings in this dir
 *     );
 *     // Use compiled templates in production
 *     if ($in_production) {
 *         // Set language to de_DE
 *         $renderer->setCompiledLang('de_DE');
 *     }
 *     // Render templates/index.php with data
 *     $renderer->render('index', [ 'stuff' => 42 ]);
 */
class TemplateRenderer {

    var $template_dir;
    var $template_suffix;
    var $compiled_dir;
    var $strings_dir;
    var $lang;
    var $strings_map;
    var $strings_key;
    var $blocks = [];
    var $strings_var = 'strings';

    function __construct($template_dir, $template_suffix, $compiled_dir, $strings_dir) {
        $this->template_dir = rtrim($template_dir, '/');
        $this->template_suffix = ltrim($template_suffix, '.');
        $this->compiled_dir = rtrim($compiled_dir, '/');
        $this->strings_dir = rtrim($strings_dir, '/');
    }

    function setStringsVarName($varname) {
        $this->strings_var = $varname;
    }

    function setCompiledLang($lang) {
        $this->lang = $lang;
    }

    function readStrings($strings_path) {
        require $strings_path;
        return ${$this->strings_var};
    }

    function render($template, $data = []) {
        $t = $this;
        extract($data);
        ob_start();
        $rel_path = "{$template}.{$this->template_suffix}";
        if ($this->lang) {
            $tpl_path = "{$this->compiled_dir}/{$rel_path}";
            $strings_path = "{$this->strings_dir}/{$this->lang}/{$rel_path}";
            if (!isset($this->strings_map[$strings_path])) {
                $this->strings_map[$strings_path] = $this->readStrings($strings_path);
            }
            $this->strings_key = $strings_path;
        } else {
            $tpl_path = "{$this->template_dir}/{$rel_path}";
        }
        require $tpl_path;
        echo trim(ob_get_clean());
    }

    function e($str) {
        if ($this->lang && isset($this->strings_map[$this->strings_key][$str])) {
            $str = $this->strings_map[$this->strings_key][$str];
        }
        echo htmlspecialchars(vsprintf($str, array_slice(func_get_args(), 1)));
    }

    function er($str) {
        ob_start();
        call_user_func_array([$this, 'e'], func_get_args());
        return trim(ob_get_clean());
    }

    function start_block($block_name) {
        $this->blocks[$block_name] = '';
        ob_start();
    }

    function end_block($block_name) {
        $this->blocks[$block_name] = trim(ob_get_clean());
    }

    function render_block($block_name, $default_content = '') {
        if (array_key_exists($block_name, $this->blocks)) {
            echo $this->blocks[$block_name];
        } else {
            echo $default_content;
        }
    }

}
