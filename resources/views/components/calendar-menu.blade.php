@props(['label' => 'Add to calendar', 'class' => 'btn btn--ghost btn--sm', 'up' => false])
<details {{ $attributes->class(['cal', 'cal--up' => $up]) }}>
    <summary class="{{ $class }}">{{ $label }}</summary>
    <div class="cal__menu">
        <a href="{{ \App\Support\EventInfo::googleCalendarUrl() }}" target="_blank" rel="noopener">Google Calendar</a>
        <a href="{{ \App\Support\EventInfo::outlookCalendarUrl() }}" target="_blank" rel="noopener">Outlook</a>
        <a href="{{ \App\Support\EventInfo::icsUrl() }}">Apple Calendar (.ics)</a>
    </div>
</details>
