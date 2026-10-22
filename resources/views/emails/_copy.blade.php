{{-- Client-approved copy from config/event.php (email.copy.*) replaces the draft paragraphs when set. --}}
@foreach (preg_split('/\n\s*\n/', trim($copy)) as $para)
    <p style="margin:0 0 16px;">{{ $para }}</p>
@endforeach
