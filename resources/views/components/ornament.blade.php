@props(['align' => 'center'])
<div {{ $attributes->class(['ornament', 'ornament--left' => $align === 'left']) }} aria-hidden="true">
    <svg viewBox="0 0 46 14" fill="none"><circle cx="2.5" cy="7" r="1" fill="currentColor"/><path d="M9 7l3-3 3 3-3 3z" fill="currentColor" opacity=".75"/><path d="M23 1l6 6-6 6-6-6z" stroke="currentColor"/><path d="M23 4.4l2.6 2.6L23 9.6 20.4 7z" fill="currentColor"/><path d="M31 7l3-3 3 3-3 3z" fill="currentColor" opacity=".75"/><circle cx="43.5" cy="7" r="1" fill="currentColor"/></svg>
</div>
