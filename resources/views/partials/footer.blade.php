<footer class="footer on-dark" id="contact">
    <div class="pattern pattern--edge" aria-hidden="true"></div>
    <div class="container" style="position:relative">
        <div class="footer__top">
            <x-ornament />
            <p class="footer__title">Symposium in Honour of Dr. Festus Fadeyi <em>at 85</em></p>
            <p style="margin:0">{{ config('event.subtitle') }} · <em>{{ config('event.book_title') }}</em></p>
            <p style="margin:0">{{ config('event.date_label') }} · {{ config('event.time_label') }} · {{ config('event.venue.full') }}</p>
        </div>
        <div class="footer__cols">
            <div>
                <h4>Host</h4>
                <p style="color:var(--ivory)">{{ config('event.host') }}</p>
            </div>
            <div>
                <h4>Contact</h4>
                <ul>
                    <li>{{ config('event.contact.name') }}</li>
                    <li><a href="mailto:{{ config('event.contact.email') }}">{{ config('event.contact.email') }}</a></li>
                    <li><a href="tel:{{ config('event.contact.phone_intl') }}">{{ config('event.contact.phone') }}</a></li>
                </ul>
            </div>
            <div>
                <h4>Take part</h4>
                <ul>
                    <li><a href="{{ route('home') }}#rsvp">RSVP</a></li>
                    <li><a href="{{ route('donate.show') }}">Donate</a></li>
                    <li><x-calendar-menu label="Add to calendar" class="footer-cal" up /></li>
                </ul>
            </div>
            <div>
                <h4>Privacy</h4>
                <p>Guest and donor information is used only for this event, in line with the Nigeria Data Protection Act 2023.</p>
                <p><a href="{{ route('privacy') }}">Read the privacy notice</a></p>
            </div>
        </div>
        <div class="footer__bottom">
            <span>&copy; {{ date('Y') }} {{ config('event.host') }}</span>
            <span>{{ config('event.short_title') }}</span>
        </div>
    </div>
</footer>
