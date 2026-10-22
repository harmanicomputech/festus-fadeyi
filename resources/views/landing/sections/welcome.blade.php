@php($welcome = config('event.welcome'))
<section class="section welcome grain" id="welcome" aria-labelledby="welcome-title">
    <div class="container container--narrow">
        <div class="section-head reveal">
            <span class="eyebrow eyebrow--both">Welcome</span>
            <h2 class="display" id="welcome-title">A word of <em>welcome</em></h2>
        </div>

        @if ($welcome['message'])
            <blockquote class="welcome__quote reveal">
                @foreach (preg_split('/\n\s*\n/', trim($welcome['message'])) as $para)
                    <p>{{ $para }}</p>
                @endforeach
                @if ($welcome['signed_by'])
                    <footer class="welcome__sign">— {{ $welcome['signed_by'] }}</footer>
                @endif
            </blockquote>
        @else
            <x-placeholder class="reveal" label="Welcome message from the host or family">
                The approved welcome note will appear here once supplied.
            </x-placeholder>
        @endif

        <x-ornament class="reveal" style="margin-top:3rem" />
    </div>
</section>
