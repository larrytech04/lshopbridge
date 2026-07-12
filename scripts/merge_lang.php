<?php
/**
 * Merge a translation additions file into a locale catalog.
 *   php scripts/merge_lang.php <code> <additions.json>
 *
 * Result lang/<code>.json contains EXACTLY the canonical en.json key set,
 * in en order, using: existing translation > additions > English fallback.
 * Stale keys (not in en.json) are dropped.
 */
$root = dirname(__DIR__);
$code = $argv[1] ?? null;
$addFile = $argv[2] ?? null;
if (!$code) { fwrite(STDERR, "usage: merge_lang.php <code> [additions.json]\n"); exit(1); }

$en  = json_decode(file_get_contents("$root/lang/en.json"), true);
$cur = is_file("$root/lang/$code.json") ? json_decode(file_get_contents("$root/lang/$code.json"), true) : [];
$add = ($addFile && is_file($addFile)) ? json_decode(file_get_contents($addFile), true) : [];
if ($en === null) { fwrite(STDERR, "en.json invalid\n"); exit(1); }
if ($cur === null) { fwrite(STDERR, "$code.json invalid JSON\n"); exit(1); }
if ($addFile && $add === null) { fwrite(STDERR, "additions invalid JSON\n"); exit(1); }

$overlay = array_merge($cur, $add);
$final = [];
$en_equal = 0;
foreach ($en as $k => $v) {
    $final[$k] = array_key_exists($k, $overlay) && $overlay[$k] !== '' ? $overlay[$k] : $v;
    if ($final[$k] === $v) $en_equal++;
}
file_put_contents("$root/lang/$code.json",
    json_encode($final, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "$code: ".count($final)." keys written; $en_equal identical to English (proper nouns + untranslated)\n";
