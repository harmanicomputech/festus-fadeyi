@props(['label', 'tag' => 'Awaiting client', 'inline' => false])
{{-- Clearly marked slot for content the client has not yet supplied. Replace via config/event.php. --}}
<div {{ $attributes->class(['placeholder', 'placeholder--inline' => $inline]) }} data-placeholder>
    <span class="placeholder__tag">{{ $tag }}</span>
    @if ($inline)
        <span>{{ $label }}</span>
    @else
        <strong>{{ $label }}</strong>
        @if (trim($slot) !== '')<span>{{ $slot }}</span>@endif
    @endif
</div>
