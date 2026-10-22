<section class="section rsvp on-dark" id="rsvp" aria-labelledby="rsvp-title">
    <div class="pattern" aria-hidden="true"></div>
    <div class="container">
        <div class="reply-card reveal">
            <x-corner class="reply-card__corner reply-card__corner--tl" /><x-corner class="reply-card__corner reply-card__corner--tr" />
            <x-corner class="reply-card__corner reply-card__corner--bl" /><x-corner class="reply-card__corner reply-card__corner--br" />

            <div class="reply-card__head">
                <span class="eyebrow eyebrow--both">Répondez s’il vous plaît</span>
                <h2 class="reply-card__title" id="rsvp-title">Kindly <em>reply</em></h2>
                <p class="reply-card__sub">
                    @if ($rsvpOpen)
                        We would be honoured by your presence. Please reply by <strong>{{ config('event.rsvp.deadline_label') }}</strong>.
                    @else
                        RSVPs closed on <strong>{{ config('event.rsvp.deadline_label') }}</strong>.
                    @endif
                </p>
                <x-ornament />
            </div>

            @if (session('rsvp_notice'))
                <div class="notice" role="status"><p>{{ session('rsvp_notice') }}</p></div>
            @endif

            @if (! $rsvpOpen)
                <div class="notice">
                    <p>Thank you for your interest. New replies are no longer being accepted. If you have already replied, use the secure link in your confirmation email to view or change your RSVP, or contact {{ config('event.contact.name') }} at <a class="link-gold" href="mailto:{{ config('event.contact.email') }}">{{ config('event.contact.email') }}</a> / {{ config('event.contact.phone') }}.</p>
                </div>
            @else
                @if ($invitation)
                    <p class="greeting">An invitation for {{ $invitation->salutation() }}</p>
                @endif

                @if ($seatsFull)
                    <div class="notice"><p>All seats are currently reserved. You may still reply — acceptances will join the waitlist, and we will email you as soon as a seat becomes available.</p></div>
                @endif

                @if ($errors->any())
                    <div class="notice notice--error" role="alert" tabindex="-1" data-error-summary>
                        <p>Please check the highlighted {{ Str::plural('field', $errors->count()) }} below.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('rsvp.store') }}" novalidate data-rsvp-form data-loading-form>
                    @csrf
                    @if ($inviteOnly && ! $invitation)
                        <div class="form-grid" style="margin-bottom:1.35rem">
                            <div class="field col-6">
                                <label for="invite_code">Invitation code</label>
                                <input class="input" id="invite_code" name="invite_code" type="text" autocomplete="off" autocapitalize="characters" required maxlength="20"
                                       value="{{ old('invite_code') }}" @error('invite_code') aria-invalid="true" aria-describedby="invite_code-error" @enderror>
                                <p class="hint">You will find this code in your invitation email.</p>
                                <x-field-error name="invite_code" />
                            </div>
                        </div>
                    @endif

                    @include('partials.rsvp-fields', ['invitation' => $invitation])

                    <div class="form-actions" style="margin-top:1.8rem">
                        <button class="btn btn--navy" type="submit">Send my reply <x-arrow /></button>
                        <p class="hint">You will receive a confirmation email with a link to update or cancel your reply.</p>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
