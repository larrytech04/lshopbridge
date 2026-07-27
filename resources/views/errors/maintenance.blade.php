<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Under maintenance') }} · {{ config('platform.name') }}</title>
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#0b1220; color:#e5e7eb; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; text-align:center; padding:24px; }
        .card { max-width:420px; }
        h1 { font-size:1.5rem; font-weight:800; margin:16px 0 8px; }
        p { color:#94a3b8; line-height:1.5; }
        .icon { font-size:2.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🛠️</div>
        <h1>{{ __('We\'ll be right back') }}</h1>
        <p>{{ __(':name is undergoing scheduled maintenance. Please check back shortly.', ['name' => config('platform.name')]) }}</p>
    </div>
</body>
</html>
