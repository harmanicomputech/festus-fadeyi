<footer class="footer on-dark" id="contact">
    <div class="container">
        <p class="footer__big" data-reveal>Dr. Festus Fadeyi <b>at 85</b></p>
        <div class="footer__cols">
            <div>
                <h4>The occasion</h4>
                <p>{{ config('event.date_label') }}<br>{{ config('event.time_label') }}<br>{{ config('event.venue.full') }}</p>
            </div>
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
                    <li><a href="{{ route('privacy') }}">Privacy notice</a></li>
                </ul>
            </div>
        </div>
        <div class="footer__bottom">
            <span>&copy; {{ date('Y') }} {{ config('event.host') }}</span>
            <span>Symposium in Honour of Dr. Festus Fadeyi at 85</span>
        </div>
    </div>
</footer>
