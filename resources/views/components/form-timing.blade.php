@props(['formType'])
<input type="hidden" name="_form_timing" value="{{ app(\App\Services\Security\FormTimingService::class)->issue($formType) }}">
