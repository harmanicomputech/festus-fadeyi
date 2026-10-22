<footer class="footer on-dark" id="contact">
    <div class="footer__glow" aria-hidden="true"></div>
    <div class="pattern pattern--fade" aria-hidden="true"></div>
    <div class="container">
        <div class="footer__cta">
            <p class="kicker">{{ config('event.date_label') }}</p>
            <p class="footer__big" data-reveal>Dr. Festus Fadeyi <b>at 85</b></p>
            <div class="footer__actions" data-reveal>
                <a class="btn" href="{{ route('home') }}#rsvp" data-magnetic>RSVP Now <x-arrow /></a>
                <a class="btn btn--ghost" href="{{ route('donate.show') }}" data-magnetic>Donate</a>
            </div>
        </div>

        <div class="footer__cols">
            <div>
                <h4>The occasion</h4>
                <p>{{ config('event.subtitle') }}<br><span class="footer__muted">{{ config('event.book_title') }}</span></p>
                <p>{{ config('event.time_label') }}<br>{{ config('event.venue.full') }}</p>
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
                <h4>Explore</h4>
                <ul>
                    <li><a href="{{ route('home') }}#celebrant">The Celebrant</a></li>
                    <li><a href="{{ route('home') }}#book">The Book</a></li>
                    <li><a href="{{ route('home') }}#programme">Programme</a></li>
                    <li><a href="{{ route('home') }}#venue">Venue</a></li>
                </ul>
            </div>
            <div>
                <h4>Take part</h4>
                <ul>
                    <li><a href="{{ route('home') }}#rsvp">RSVP</a></li>
                    <li><a href="{{ route('home') }}#give">Give</a></li>
                    <li><x-calendar-menu label="Add to calendar" class="footer-cal" up /></li>
                    <li><a href="{{ route('privacy') }}">Privacy notice</a></li>
                </ul>
            </div>
        </div>

        <div class="footer__bottom">
            <span>&copy; {{ date('Y') }} Symposium in Honour of Dr. Festus Fadeyi at 85</span>
            <a class="footer__top" href="{{ route('home') }}#top" aria-label="Back to top">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
            </a>
        </div>
    </div>
    <span class="footer__mark" aria-hidden="true">85</span>
</footer>
