@php $fieldName = app(\App\Services\Security\HoneypotValidationService::class)->fieldName(); @endphp
<div aria-hidden="true" style="position:absolute; left:-9999px; top:-9999px; height:0; width:0; overflow:hidden; opacity:0; pointer-events:none;">
    <label for="{{ $fieldName }}">Leave this field blank</label>
    <input type="text" id="{{ $fieldName }}" name="{{ $fieldName }}" tabindex="-1" autocomplete="off" value="">
</div>
