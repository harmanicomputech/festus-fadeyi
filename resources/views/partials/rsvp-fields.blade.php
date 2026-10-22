{{--
    Shared RSVP fields. $rsvp (optional) pre-fills when managing an existing reply;
    $invitation (optional) pre-fills name/email from a personal invitation.
--}}
@php
    $rsvp = $rsvp ?? null;
    $invitation = $invitation ?? null;
    $val = fn ($field, $default = null) => old($field, $rsvp?->{$field} ?? $default);
    $pledgeAmount = old('pledge_amount', isset($pledge) && $pledge && $pledge->status === 'open' ? $pledge->amount_kobo / 100 : null);
    $attendance = $val('attendance');
    $err = fn ($f) => $errors->has($f) ? 'aria-invalid=true aria-describedby='.$f.'-error' : '';
@endphp

<div class="form-grid">
    <div class="field col-2">
        <label for="title">Title</label>
        <select class="select" id="title" name="title" required {!! $err('title') !!}>
            <option value="" disabled @selected(! $val('title', $invitation?->title))>Select</option>
            @foreach (config('event.rsvp.titles') as $t)
                <option value="{{ $t }}" @selected($val('title', $invitation?->title) === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <x-field-error name="title" />
    </div>

    <div class="field col-4">
        <label for="full_name">Full name</label>
        <input class="input" id="full_name" name="full_name" type="text" autocomplete="name" required maxlength="120"
               value="{{ $val('full_name', $invitation?->name) }}" {!! $err('full_name') !!}>
        <x-field-error name="full_name" />
    </div>

    @unless ($rsvp)
        <div class="field col-3">
            <label for="email">Email</label>
            <input class="input" id="email" name="email" type="email" autocomplete="email" inputmode="email" required maxlength="190"
                   value="{{ old('email', $invitation?->email) }}" {!! $err('email') !!}>
            <x-field-error name="email" />
        </div>
    @endunless

    <div class="field {{ $rsvp ? 'col-6' : 'col-3' }}">
        <label for="phone">Phone <span class="optional">(WhatsApp)</span></label>
        <input class="input" id="phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" required maxlength="20" placeholder="0803 000 0000"
               value="{{ $val('phone', $invitation?->phone) }}" {!! $err('phone') !!}>
        <x-field-error name="phone" />
    </div>

    <div class="field col-6">
        <label for="organisation">Organisation / designation <span class="optional">optional</span></label>
        <input class="input" id="organisation" name="organisation" type="text" autocomplete="organization" maxlength="160" value="{{ $val('organisation') }}" {!! $err('organisation') !!}>
        <x-field-error name="organisation" />
    </div>

    <fieldset class="field choices col-6" @error('attendance') aria-describedby="attendance-error" @enderror>
        <legend>Will you attend?</legend>
        <div class="choices__row">
            @foreach (['yes' => 'Yes', 'maybe' => 'Maybe', 'no' => 'No'] as $value => $label)
                <label class="choice">
                    <input type="radio" name="attendance" value="{{ $value }}" required @checked($attendance === $value) data-attendance>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <x-field-error name="attendance" />
    </fieldset>

    <div class="field col-6" data-when-attending @if ($attendance === 'no') hidden @endif>
        <label for="party_size">Seats required <span class="optional">including yourself</span></label>
        <select class="select" id="party_size" name="party_size" {!! $err('party_size') !!}>
            @for ($n = 1; $n <= \App\Support\EventInfo::maxPartySize(); $n++)
                <option value="{{ $n }}" @selected((int) $val('party_size', 1) === $n)>{{ $n === 1 ? 'Just me (1 seat)' : 'Me and '.($n - 1).' guest'.($n > 2 ? 's' : '').' ('.$n.' seats)' }}</option>
            @endfor
        </select>
        <x-field-error name="party_size" />
    </div>

    <div class="field col-6" data-when-attending @if ($attendance === 'no') hidden @endif>
        <label for="dietary_needs">Dietary or accessibility needs <span class="optional">optional</span></label>
        <input class="input" id="dietary_needs" name="dietary_needs" type="text" maxlength="500" value="{{ $val('dietary_needs') }}" {!! $err('dietary_needs') !!}>
        <x-field-error name="dietary_needs" />
    </div>

    @if (config('event.donations.pledges_enabled'))
        <div class="field col-6">
            <label for="pledge_amount">Donation pledge <span class="optional">optional</span></label>
            <div class="input-prefix">
                <span aria-hidden="true">₦</span>
                <input class="input" id="pledge_amount" name="pledge_amount" type="text" inputmode="numeric" placeholder="e.g. 100,000" maxlength="14"
                       value="{{ $pledgeAmount ? number_format($pledgeAmount) : '' }}" data-money {!! $err('pledge_amount') !!}>
            </div>
            <x-field-error name="pledge_amount" />
        </div>
    @endif

    <div class="field col-6">
        <label for="message">Message to the celebrant <span class="optional">optional</span></label>
        <textarea class="textarea" id="message" name="message" maxlength="1000" rows="3" {!! $err('message') !!}>{{ $val('message') }}</textarea>
        <x-field-error name="message" />
    </div>

    <div class="honeypot" aria-hidden="true">
        <label for="website">Leave this empty</label>
        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
    </div>

    @unless ($rsvp)
        <div class="privacy-note">
            <label class="checkbox">
                <input type="checkbox" name="privacy" value="1" required @checked(old('privacy')) @error('privacy') aria-invalid="true" aria-describedby="privacy-error" @enderror>
                <span>My details may be used for this event only, as set out in the <a class="link-gold" href="{{ route('privacy') }}" target="_blank">privacy notice</a>.</span>
            </label>
            <x-field-error name="privacy" />
        </div>
    @endunless
</div>
