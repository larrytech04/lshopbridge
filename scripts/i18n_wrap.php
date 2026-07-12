<?php
/**
 * Conservative Blade auto-wrapper for i18n.
 * Wraps safe text nodes  >Text<  ->  >{{ __('Text') }}<
 * and literal placeholder/title/aria-label attributes.
 *
 * Skips: <script>/<style>/<pre>/<code> blocks, anything already containing
 * {{ }} / __() / @ / $ / blade directives, and non-natural-language text.
 *
 * Usage:
 *   php scripts/i18n_wrap.php --dry  path [path...]   # show changes, write nothing
 *   php scripts/i18n_wrap.php        path [path...]   # apply in place
 */

$args = array_slice($argv, 1);
$dry = false;
$paths = [];
foreach ($args as $a) {
    if ($a === '--dry') { $dry = true; continue; }
    $paths[] = $a;
}

$NATURAL = '/^[\p{L}0-9 \x27’.,!?:;&%()\/…—–·+\x22-]+$/u';
$keys = [];
$changedFiles = 0;
$totalReplacements = 0;

function wrapKey(string $t, array &$keys): string {
    $keys[$t] = true;
    return "{{ __('".str_replace(['\\', "'"], ['\\\\', "\\'"], $t)."') }}";
}

foreach ($paths as $file) {
    if (!is_file($file)) { fwrite(STDERR, "skip (not a file): $file\n"); continue; }
    $orig = file_get_contents($file);

    // Protect blocks we must never touch.
    $protected = [];
    $protect = function ($pattern) use (&$protected) {
        return function ($m) use (&$protected, $pattern) {
            $k = "\x00P".count($protected)."\x00";
            $protected[$k] = $m[0];
            return $k;
        };
    };
    $work = $orig;
    $work = preg_replace_callback('#<(script|style|pre|code|textarea)\b[^>]*>.*?</\1>#is', $protect('block'), $work);
    // Protect blade comments {{-- --}} and echo/directive braces are handled by the natural check.
    $work = preg_replace_callback('/\{\{--.*?--\}\}/s', $protect('comment'), $work);

    $count = 0;

    // 1) Text nodes between > and <
    $work = preg_replace_callback('/>([^<>]*)</s', function ($m) use (&$keys, &$count, $NATURAL) {
        $inner = $m[1];
        $t = trim($inner);
        if ($t === '') return $m[0];
        if (preg_match('/\{\{|\}\}|__\(|@|\$|\x00/', $inner)) return $m[0];
        if (!preg_match('/[A-Za-z]{2,}/', $t)) return $m[0];           // need a real word
        if (!preg_match($NATURAL, $t)) return $m[0];                   // only natural-language chars
        if (preg_match('/^(px|rem|em|vh|vw|fr|true|false|null)$/i', $t)) return $m[0];
        $lead  = substr($inner, 0, strlen($inner) - strlen(ltrim($inner)));
        $trail = substr($inner, strlen(rtrim($inner)));
        $count++;
        return '>'.$lead.wrapKey($t, $keys).$trail.'<';
    }, $work);

    // 2) Literal placeholder / title / aria-label attributes
    $work = preg_replace_callback('/\b(placeholder|title|aria-label)="([^"{}<>\x00]*[A-Za-z]{2,}[^"{}<>\x00]*)"/', function ($m) use (&$keys, &$count, $NATURAL) {
        $t = trim($m[2]);
        if (!preg_match($NATURAL, $t)) return $m[0];
        $count++;
        return $m[1].'="'.wrapKey($t, $keys).'"';
    }, $work);

    // Restore protected blocks.
    $work = strtr($work, $protected);

    if ($count > 0 && $work !== $orig) {
        $totalReplacements += $count;
        $changedFiles++;
        if ($dry) {
            echo "── $file  (+$count)\n";
        } else {
            file_put_contents($file, $work);
            echo "wrote $file  (+$count)\n";
        }
    }
}

fwrite(STDERR, sprintf("\n%s: %d replacements across %d files; %d unique keys\n",
    $dry ? 'DRY' : 'APPLIED', $totalReplacements, $changedFiles, count($keys)));

// Emit the new keys (for catalog building) to a side file.
if (!$dry && $keys) {
    $out = array_keys($keys);
    sort($out, SORT_NATURAL | SORT_FLAG_CASE);
    file_put_contents(dirname(__DIR__).'/storage/i18n_new_keys.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}
