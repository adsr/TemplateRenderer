<?php

/**
 * TemplateCompiler is a simple template compiler class designed for use with
 * TemplateRenderer. The template compilation process is such:
 *
 *     1. For each template, extract all string literals passed into the `e`
 *        and `er` methods of TemplateRenderer, e.g.,
 *        `$t->e('Hello %s!', $name)`.
 *     2. For each target language, output string database file. This is just
 *        a regular PHP file that contains an associative array of md5 keyed
 *        translated strings.
 *     3. Output a compiled template which replaces the string literal in the
 *        original template with an md5 hash key into the string database,
 *        i.e., `$t->e('Hello %s!', $name)` in the original template becomes
 *        `$t->e('1bd515531ee182107d26d686c4f0882b', $name)` in the compiled
 *        template.
 *
 * A human translator then manually translates the strings in each string
 * database file.
 *
 * See `bin/compile.php` in this repo for a CLI wrapper of this class.
 */
class TemplateCompiler {

    var $template_dir;
    var $template_suffix;
    var $compiled_dir;
    var $strings_dir;
    var $langs;
    var $do_prune = false;
    var $strings_var = 'strings';

    function __construct($template_dir, $template_suffix, $compiled_dir, $strings_dir, $langs) {
        $this->template_dir = rtrim($template_dir, '/');
        $this->template_suffix = $template_suffix;
        $this->compiled_dir = rtrim($compiled_dir, '/');
        $this->strings_dir = rtrim($strings_dir, '/');
        $this->langs = $langs;
    }

    function setStringsVarName($varname) {
        $this->strings_var = $varname;
    }

    function setPrune($prune) {
        $this->do_prune = (bool)$prune;
    }

    function compileOne($template) {
        $source = $this->readTemplateSource($template);
        list($extracted_strings, $compiled_source) = $this->extractAndReplaceStrings($source);
        $this->writeCompiledTemplate($template, $compiled_source);
        foreach ($this->langs as $lang) {
            $persisted_strings = $this->readStrings($template, $lang);
            $this->writeStrings($template, $lang, $persisted_strings, $extracted_strings);
        }
    }

    function compileAll() {
        foreach ($this->findTemplates() as $template) {
            $rel_template = preg_replace(
                '@' . preg_quote($this->template_dir, '@') . '/*@',
                '', $template[0]);
            $this->compileOne($rel_template);
        }
    }

    function findTemplates() {
        $dir_iter = new RecursiveDirectoryIterator($this->template_dir);
        $iter_iter = new RecursiveIteratorIterator($dir_iter);
        return new RegexIterator($iter_iter,
            '@^.*' . preg_quote($this->template_suffix, '@') . '$@',
            RecursiveRegexIterator::GET_MATCH);
    }

      function readTemplateSource($template) {
        $template_path = "{$this->template_dir}/{$template}";
        if (is_readable($template_path)) {
            $src = file_get_contents($template_path);
        } else {
            $src = false;
        }
        if ($src === false) {
            throw new RuntimeException(sprintf(
                'readTemplateSource(%s) failed', $template));
        }
        return $src;
    }

    function extractAndReplaceStrings($src) {
        $matches_s = [];
        $matches_d = [];
        $strings = [];
        $orig_src = $src;
        $replace_fn = function($matches) use (&$strings) {
            $match = $matches[0];
            $str = strpbrk($match, "\"'");
            $evald_str = $this->evalPhpStr($str);
            $hash = md5($evald_str);
            $strings[$hash] = $evald_str;
            $quote = substr($str, 0, 1);
            return substr($match, 0, strlen($match) - strlen($str)) . "{$quote}{$hash}{$quote}";
        };
        $src = preg_replace_callback('@>er?\s*\(\s*"(?:[^"\\\\]|\\\\.)*"@', $replace_fn, $src);
        $src = preg_replace_callback("@>er?\\s*\\(\\s*'(?:[^'\\\\]|\\\\.)*'@", $replace_fn, $src);
        return [$strings, $src];
    }

    function evalPhpStr($str) {
        $code = "echo $str;";
        $output = null;
        $exit_code = 0;
        exec('php -r ' . escapeshellarg($code), $output, $exit_code);
        if ($exit_code != 0) {
            throw new RuntimeException(sprintf('evalPhpStr(%s) failed', $str));
        }
        return implode("\n", $output);
    }

    function writeCompiledTemplate($template, $compiled_src) {
        $compiled_path = "{$this->compiled_dir}/{$template}";
        $compiled_dir = dirname($compiled_path);
        if (!is_dir($compiled_dir) && !mkdir($compiled_dir, 0755, true)) {
            throw new RuntimeException(sprintf(
                'writeCompiledTemplate mkdir(%s) failed', $compiled_dir));
        }
        if (file_put_contents($compiled_path, $compiled_src) === false) {
            throw new RuntimeException(sprintf(
                'writeCompiledTemplate file_put_contents(%s, ...) failed',
                $compiled_path));
        }
        $this->lintPhpFile($compiled_path);
    }

    function readStrings($template, $lang) {
        $strings_path = "{$this->strings_dir}/{$lang}/{$template}";
        if (is_readable($strings_path)) {
            require $strings_path;
            if (isset(${$this->strings_var})) {
                return ${$this->strings_var};
            } else {
                throw new RuntimeException(sprintf(
                    'readStrings(%s) missing `$%s` var', $strings_path, $this->strings_var));
            }
        }
        return [];
    }

    function writeStrings($template, $lang, $persisted_strings, $extracted_strings) {
        if ($this->do_prune) {
            $persisted_strings = array_intersect_key($persisted_strings, $extracted_strings);
        }
        $all_strings = array_merge($extracted_strings, $persisted_strings);
        $strings_path = "{$this->strings_dir}/{$lang}/{$template}";
        $strings_dir = dirname($strings_path);
        if (!is_dir($strings_dir) && !mkdir($strings_dir, 0755, true)) {
            throw new RuntimeException(sprintf(
                'writeStrings mkdir(%s) failed', $strings_dir));
        }
        if (file_put_contents($strings_path, '<' . '?php' . "\n" .
            '$' . $this->strings_var . ' = ' . var_export($all_strings, true) . ';') === false
        ) {
            throw new RuntimeException(sprintf(
                'writeStrings(%s) file_put_contents failed', $strings_path));
        }
        $this->lintPhpFile($strings_path);
    }

    function lintPhpFile($path) {
        $ig = null;
        $exit_code = 0;
        exec('php -l ' . escapeshellarg($path), $ig, $exit_code);
        if ($exit_code != 0) {
            throw new RuntimeException(sprintf('lintPhpFile(%s) failed', $path));
        }
    }

}
