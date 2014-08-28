#!/usr/bin/php -q
<?php
/**
 * extract-js-localizations.php
 *
 * Exports all strings from js into app/views/localizations/show.php so
 * they can be translated as well.
 *
 * @author    Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license   GPL2 or any later version
 * @copyright Stud.IP Core Group
 * @since     3.1
 */

require 'studip_cli_env.inc.php';


function should_skip_file($filename, $realfile) {
    $exclude = array(
        'locale/*',
        'public/assets/javascripts/jquery/*',
        'public/assets/javascripts/ckeditor/*',
        'public/assets/javascripts/mathjax/*',
        'public/assets/*',
        'public/pictures/*',
        'public/plugins_packages/*',
        'test/*',
        'vendor/*',
    );
    $include = array(
        'public/assets/javascripts*',
        'public/plugins_packages/core*',
    );
    $mime_types = array(
        'text/*',
        'application/javascript',
    );
    
    $matching_pattern = null;
    $skip             = false;
    foreach ($exclude as $pattern) {
        if (fnmatch($pattern, $filename)) {
            $matching_pattern = $pattern;
            $skip             = true;
            break;
        }
    }
    
    if ($skip) {
        foreach ($include as $pattern) {
            if (fnmatch($pattern, $filename) && strlen($pattern) > strlen($matching_pattern)) {
                $skip = false;
                break;
            }
        }
    }
    
    if (!$skip && is_file($realfile)) {
        $mime_type = mime_content_type($realfile);
        
        $skip = true;
        foreach ($mime_types as $pattern) {
            if (fnmatch($pattern, $mime_type)) {
                $skip = false;
                break;
            }
        }
    }

    return $skip;
}

function extract_strings($file) {
    $contents = file_get_contents($file);
    $regexp   = '/(?:\'([^\']+)\'|"([^"]+)")\\.toLocaleString\\(\\s*\\)/';
    
    if (preg_match_all($regexp, $contents, $matches, PREG_SET_ORDER)) {
        $result = array();
        foreach ($matches as $match) {
            $result[] = $match[1] ?: $match[2];
        }
        return array_unique($result);
    }

    return false;
}

function find_strings_in_dir($directory, $base = null) {
    $result = array();

    $base = rtrim($base ?: $directory, '/') . '/';

    $files = glob(rtrim($directory, '/') . '/*');
    foreach ($files as $file) {
        $filename = str_replace($base, '', $file);
        $is_dir   = is_dir($file);

        if (should_skip_file($filename, $file)) {
            continue;
        }

        if (is_dir($file)) {
            $result += find_strings_in_dir($file, $base);
        } elseif ($strings = extract_strings($file)) {
            $result[$filename] = $strings;
        }
    }

    return $result;
}

$occurences = find_strings_in_dir(realpath(__DIR__ . '/..'));

$hashes = array();
foreach ($occurences as $file => $strings) {
    foreach ($strings as $index => $string) {
        $hash = md5($string);
        if (in_array($hash, $hashes)) {
            unset($strings[$index]);
        } else {
            $hashes[] = $hash;
        }
    }
    if (empty($strings)) {
        unset($occurences[$file]);
    } else {
        $occurences[$file] = $strings;
    }
}

ob_start();
?>
<?= '<?php' . PHP_EOL ?>

$translations = array(
<? foreach ($occurences as $file => $strings): ?>
    // <?= $file . PHP_EOL ?>
<? foreach ($strings as $string): ?>
    '<?= addcslashes($string, "'") ?>' => _('<?= addcslashes($string, "'") ?>'),
<? endforeach; ?>

<? endforeach; ?>
);

// translations have to be UTF8 for #json_encode
$translations = $plugin->utf8EncodeArray($translations);

?>
String.toLocaleString({
  "<?= '<?=' ?> strtr($language, "_", "-") <?= '?>' ?>": <?= '<?=' ?> json_encode($translations) <?= '?>' ?>

});
<?
$view = ob_get_clean();

file_put_contents(__DIR__ . '/../app/views/localizations/show.php', $view);

printf('%u strings written to file' . PHP_EOL, array_sum(array_map('count', $occurences)));
