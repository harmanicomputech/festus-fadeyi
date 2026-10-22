@extends('layouts.admin', ['title' => 'Final report'])
@section('content')
    <div class="page-head">
        <div><p class="eyebrow">{{ config('event.title') }}</p><h1>Final guest &amp; donation report</h1></div>
        <div class="actions no-print">
            <a class="btn btn--gold" href="{{ route('admin.reports', ['download' => 'xlsx']) }}">Download full report (Excel)</a>
            <button class="btn btn--ghost" type="button" data-print>Print</button>
        </div>
    </div>
    <div class="grid grid--2">
        <section class="panel">
            <h2>Summary</h2>
            <dl class="kv">@foreach ($summary as $k => $v)<dt>{{ $k }}</dt><dd><strong>{{ $v }}</strong></dd>@endforeach</dl>
        </section>
        <section class="panel">
            <h2>Verified donations by method</h2>
            <table class="table">
                @forelse ($byMethod as $m)
                    <tr><td>{{ \App\Models\Donation::METHOD_LABELS[$m->method] ?? $m->method }}</td><td class="num">{{ $m->n }}</td><td class="num">{{ \App\Support\EventInfo::naira((int) $m->total) }}</td></tr>
                @empty
                    <tr><td class="muted">No verified donations yet.</td></tr>
                @endforelse
            </table>
            <p class="small muted">The Excel report contains Summary, Guests, Donations and Pledges sheets. Detailed exports are also available on the Guests and Donations pages.</p>
        </section>
    </div>
@endsection
