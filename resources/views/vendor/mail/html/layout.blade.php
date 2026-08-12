<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}
}

/* Dark mode: honoured by Apple Mail, Outlook (desktop/mobile) and a few
   others; clients without support just keep the light palette. Gmail apps
   mostly re-invert the light theme themselves instead of reading this, which
   is out of our control either way. Written here (not themes/default.css)
   because the CSS inliner that applies that file strips every @media block
   outright before it can reach the sent email. !important is required to
   beat the inline style="" attributes the inliner already wrote. */
@media (prefers-color-scheme: dark) {
body, .wrapper, .body { background-color: #111014 !important; color: #d4d4d8 !important; }
.inner-body { background-color: #1c1a1f !important; border-color: #2e2b32 !important; }
.header-accent { background-color: #d75a70 !important; }
.header { background-color: #21131a !important; border-bottom-color: #3a1e26 !important; }
.header a { color: #f4f4f5 !important; }
h1, h3 { color: #f4f4f5 !important; }
h2 { background-color: #2a0a12 !important; color: #e78f9c !important; }
p, .table td { color: #d4d4d8 !important; }
a { color: #e78f9c !important; }
.subcopy { border-top-color: #2e2b32 !important; }
.subcopy p { color: #a1a1aa !important; }
.table th { border-bottom-color: #2e2b32 !important; }
.panel-content, .panel-content p { background-color: #2a0a12 !important; color: #e78f9c !important; }
.panel { border-left-color: #d75a70 !important; }
.footer { border-top-color: #2e2b32 !important; }
.footer p, .footer a { color: #71717a !important; }
}
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
