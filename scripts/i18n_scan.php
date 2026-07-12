<?php
/**
 * i18n scanner.
 *  - Extracts every __('...') / @lang('...') key used across views + app code.
 *  - Heuristically flags user-facing strings that are NOT wrapped, so coverage can be finalised.
 *
 * Usage:
 *   php scripts/i18n_scan.php keys      # print authoritative key list (unique, sorted)
 *   php scripts/i18n_scan.php missing   # keys used in code but absent from a catalog (default: fr)
 *   php scripts/i18n_scan.php unwrapped # blade lines with probable hardcoded UI text
 */

$root = dirname(__DIR__);
$mode = $argv[1] ?? 'unwrapped';

function files(string $dir, array $exts): array {
    if (!is_dir($dir)) return [];
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        foreach ($exts as $e) {
            if (str_ends_with($f->getFilename(), $e)) { $out[] = $f->getPathname(); break; }
        }
    }
    sort($out);
    return $out;
}

/** Extract all translation keys used via __() / @lang() / trans(). */
function extractKeys(array $paths): array {
    $keys = [];
    // __('...'), __("..."), @lang('...'), trans('...'), Lang::get('...')
    $re = '/(?:@lang|__|trans|Lang::get)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s';
    foreach ($paths as $p) {
        $src = file_get_contents($p);
        if (preg_match_all($re, $src, $m)) {
            foreach ($m[2] as $i => $raw) {
                $quote = $m[1][$i];
                // unescape the matched quote and backslashes
                $val = str_replace(['\\'.$quote, '\\\\'], [$quote, '\\'], $raw);
                // skip dotted "namespaced" keys (file-based translations), keep natural-language keys
                if ($val !== '' && !preg_match('/^[a-z0-9_]+\.[a-z0-9_.]+$/i', $val)) {
                    $keys[$val] = true;
                }
            }
        }
    }
    $keys = array_keys($keys);
    sort($keys, SORT_NATURAL | SORT_FLAG_CASE);
    return $keys;
}

$viewFiles = files($root.'/resources/views', ['.blade.php']);
$codeFiles = array_merge(files($root.'/app', ['.php']), files($root.'/routes', ['.php']));

if ($mode === 'keys') {
    foreach (extractKeys(array_merge($viewFiles, $codeFiles)) as $k) echo $k."\n";
    exit;
}

if ($mode === 'missing') {
    $locale = $argv[2] ?? 'fr';
    $cat = json_decode(@file_get_contents($root."/lang/$locale.json") ?: '{}', true) ?: [];
    $used = extractKeys(array_merge($viewFiles, $codeFiles));
    $missing = array_values(array_filter($used, fn($k) => !array_key_exists($k, $cat)));
    foreach ($missing as $k) echo $k."\n";
    fwrite(STDERR, sprintf("\n%d used, %d in %s, %d missing\n", count($used), count($cat), $locale, count($missing)));
    exit;
}

// mode: unwrapped — flag probable hardcoded UI copy in blades.
$ignoreText = '/^(?:\s|&nbsp;|→|←|·|—|–|\||\/|\d|[%+×=:,.()\[\]#-])+$/u';
$flagged = [];
foreach ($viewFiles as $p) {
    $rel = str_replace($root.DIRECTORY_SEPARATOR, '', $p);
    foreach (file($p) as $n => $line) {
        $ln = $n + 1;
        // 1) text between > and < :  >Some Words<
        if (preg_match_all('/>\s*([^<>{}@]*[A-Za-z]{2,}[^<>{}]*?)\s*</', $line, $mm)) {
            foreach ($mm[1] as $txt) {
                $t = trim($txt);
                if ($t === '' || preg_match($ignoreText, $t)) continue;
                if (preg_match('/\{\{|__\(|@lang|x-icon|->/', $t)) continue;
                if (!preg_match('/[A-Za-z]{2,}\s+[A-Za-z]|^[A-Z][a-z]+$/', $t)) continue; // multiword or Capitalised word
                $flagged[] = "$rel:$ln  TEXT  ".mb_strimwidth($t, 0, 60);
            }
        }
        // 2) placeholder / title / aria-label literal attributes (no {{ }} / __ inside)
        if (preg_match_all('/\b(placeholder|title|aria-label)\s*=\s*"([^"{}]*[A-Za-z]{2,}[^"{}]*)"/', $line, $am)) {
            foreach ($am[2] as $i => $txt) {
                $t = trim($txt);
                if ($t === '' || preg_match($ignoreText, $t)) continue;
                $flagged[] = "$rel:$ln  ".strtoupper($am[1][$i])."  ".mb_strimwidth($t, 0, 60);
            }
        }
    }
}
echo implode("\n", $flagged)."\n";
fwrite(STDERR, "\n".count($flagged)." flagged lines\n");
