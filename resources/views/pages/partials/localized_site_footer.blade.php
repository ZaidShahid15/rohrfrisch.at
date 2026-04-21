<footer class="localized-footer">
    <div class="container">
        <div class="localized-grid three-col">
            <div>
                <img src="{{ asset('site-clone/rohrfrisch.at/wp-content/uploads/2025/01/RohrFrisch-200-x-70-px.png') }}" alt="RohrFrisch" width="200" height="70">
                <p class="mt-3 mb-0">Schnelle Hilfe bei Rohrproblemen in Wien mit lokal eingebundenen Assets und sauberer Laravel-Struktur.</p>
            </div>
            <div>
                <h4 class="h5 text-white">Direktlinks</h4>
                <p class="mb-2"><a href="{{ url('/dienstleistungen') }}">Dienstleistungen</a></p>
                <p class="mb-2"><a href="{{ url('/preise') }}">Preise</a></p>
                <p class="mb-2"><a href="{{ url('/uber-uns') }}">Über uns</a></p>
                <p class="mb-0"><a href="{{ url('/kontakt') }}">Kontakt</a></p>
            </div>
            <div>
                <h4 class="h5 text-white">Kontakt</h4>
                <p class="mb-2"><a href="tel:+4314420059">+43 1 4420059</a></p>
                <p class="mb-2"><a href="mailto:office@rohrfrisch.at">office@rohrfrisch.at</a></p>
                <p class="mb-0">Wien und Umgebung</p>
            </div>
        </div>
        <div class="pt-4 mt-4 border-top border-secondary-subtle">
            <small>&copy; {{ date('Y') }} RohrFrisch</small>
        </div>
    </div>
</footer>
