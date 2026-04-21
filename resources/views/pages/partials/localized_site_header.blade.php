<header class="localized-header">
    <div class="container">
        <div class="localized-nav">
            <a href="{{ url('/') }}">
                <img src="{{ asset('site-clone/rohrfrisch.at/wp-content/uploads/2025/01/RohrFrisch-200-x-70-px-3.png') }}" alt="RohrFrisch" width="200" height="70">
            </a>
            <nav class="localized-nav-links" aria-label="Hauptnavigation">
                <a href="{{ url('/') }}">Startseite</a>
                <a href="{{ url('/dienstleistungen') }}">Dienstleistungen</a>
                <a href="{{ url('/preise') }}">Preise</a>
                <a href="{{ url('/uber-uns') }}">Über uns</a>
                <a href="{{ url('/kontakt') }}">Kontakt</a>
            </nav>
            <div class="d-flex gap-2 flex-wrap">
                <a class="themeholy-btn" href="tel:+4314420059">+43 1 4420059</a>
                <a class="themeholy-btn style3" href="{{ url('/kontakt') }}">Kontakt</a>
            </div>
        </div>
    </div>
</header>
