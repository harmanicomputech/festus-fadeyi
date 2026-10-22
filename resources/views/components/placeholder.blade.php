@props(['label'])
{{-- Content awaiting the client: a quiet "coming soon" pill. Replace via config/event.php. --}}
<span {{ $attributes->class('soon') }} data-placeholder>{{ $label }} — coming soon</span>
