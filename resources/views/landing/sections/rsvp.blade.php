<section class="section rsvp on-dark" id="rsvp" aria-labelledby="rsvp-title">
    <div class="pattern pattern--fade" aria-hidden="true"></div>
    <div class="container">
        <div class="reply-card" data-reveal>
            <div class="reply-card__head">
                <h2 class="reply-card__title" id="rsvp-title">Kindly <em>reply</em></h2>
                <p class="reply-card__sub">
                    @if ($rsvpOpen) By <strong>{{ config('event.rsvp.deadline_label') }}</strong> @else RSVPs closed on <strong>{{ config('event.rsvp.deadline_label') }}</strong> @endif
                </p>
            </div>

            @if (session('rsvp_notice'))
                <div class="notice" role="status"><p>{{ session('rsvp_notice') }}</p></div>
            @endif

            @if (! $rsvpOpen)
                <div class="notice">
                    <p>New replies are no longer being accepted. To change an existing reply, use the link in your confirmation email, or contact {{ config('event.contact.name') }} on {{ config('event.contact.phone') }}.</p>
                </div>
            @else
                @if ($invitation)
                    <p class="greeting">For {{ $invitation->salutation() }}</p>
                @endif

                @if ($seatsFull)
                    <div class="notice"><p>All seats are reserved. Acceptances join the waitlist and we will email you if a seat opens.</p></div>
                @endif

                @if ($errors->any())
                    <div class="notice notice--error" role="alert" tabindex="-1" data-error-summary>
                        <p>Please check the highlighted {{ Str::plural('field', $errors->count()) }}.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('rsvp.store') }}" novalidate data-rsvp-form data-loading-form>
                    @csrf
                    @if ($inviteOnly && ! $invitation)
                        <div class="form-grid" style="margin-bottom:1.3rem">
                            <div class="field col-6">
                                <label for="invite_code">Invitation code</label>
                                <input class="input" id="invite_code" name="invite_code" type="text" autocomplete="off" autocapitalize="characters" required maxlength="20"
                                       value="{{ old('invite_code') }}" @error('invite_code') aria-invalid="true" aria-describedby="invite_code-error" @enderror>
                                <x-field-error name="invite_code" />
                            </div>
                        </div>
                    @endif

                    @include('partials.rsvp-fields', ['invitation' => $invitation])

                    <div class="form-actions" style="margin-top:1.8rem">
                        <button class="btn btn--navy" type="submit" data-magnetic>Send my reply <x-arrow /></button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
