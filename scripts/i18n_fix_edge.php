<?php
// One-shot: wrap the handful of mixed text nodes the conservative auto-wrapper skipped.
$root = dirname(__DIR__);
$files = [
    'resources/views/dashboard/deposit/show.blade.php',
    'resources/views/dashboard/disputes/show.blade.php',
    'resources/views/dashboard/funding/show.blade.php',
    'resources/views/dashboard/learning/show.blade.php',
    'resources/views/dashboard/marketplace/show.blade.php',
    'resources/views/dashboard/verification.blade.php',
    'resources/views/public/agents/_card.blade.php',
    'resources/views/public/agents/show.blade.php',
    'resources/views/public/guides/show.blade.php',
    'resources/views/shop/orders/show.blade.php',
    'resources/views/agent/verification.blade.php',
];
$keys = [];
$wrap = function (string $t) use (&$keys): string {
    $t = trim($t);
    $keys[$t] = true;
    return "{{ __('".str_replace(['\\', "'"], ['\\\\', "\\'"], $t)."') }}";
};

foreach ($files as $rel) {
    $p = "$root/$rel";
    if (!is_file($p)) continue;
    $o = file_get_contents($p);
    $s = $o;

    // >← Back to X<
    $s = preg_replace_callback('/>(\s*←\s*)([A-Za-z][^<>{}]*?)(\s*)</u', fn ($m) => '>'.$m[1].$wrap($m[2]).$m[3].'<', $s);
    // >X →<
    $s = preg_replace_callback('/>(\s*)([A-Za-z][^<>{}]*?)(\s*→\s*)</u', fn ($m) => '>'.$m[1].$wrap($m[2]).$m[3].'<', $s);
    // emoji-suffixed status lines (✅ 🎉)
    $s = preg_replace_callback('/>(\s*)([A-Za-z][^<>{}]*?[\x{2705}\x{1F389}])(\s*)</u', fn ($m) => '>'.$m[1].$wrap($m[2]).$m[3].'<', $s);

    // meaningful placeholders
    foreach (['Type your reply…', 'What do you need shipped?', 'Share your experience…', 'What do you ship and from where?'] as $ph) {
        $cnt = 0;
        $s = str_replace('placeholder="'.$ph.'"', 'placeholder="'.$wrap($ph).'"', $s, $cnt);
    }

    if ($s !== $o) {
        file_put_contents($p, $s);
        echo "fixed $rel\n";
    }
}
echo "\nnew keys:\n - ".implode("\n - ", array_keys($keys))."\n";
