@props(['url'])
<tr>
<td class="header-accent"></td>
</tr>
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{{-- Explicit width/height (not just CSS): most clients size a blocked
     image's placeholder box off these HTML attributes, so this is what
     keeps that fallback state looking deliberate instead of a stray
     oversized or undersized box. --}}
<img src="{{ site_logo() }}" width="168" height="38" class="logo" alt="{{ config('app.name') }}">
</a>
</td>
</tr>
