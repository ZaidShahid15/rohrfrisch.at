<script src="{{ asset('site-clone/rohrfrisch.at/wp-includes/js/jquery/jquery.min--f43b551b749a36845288913120943cc6.js') }}"></script>
<script src="{{ asset('site-clone/rohrfrisch.at/wp-content/themes/plumer/assets/js/bootstrap.min--5b31223e2ef611d00bfe4e71ed403f89.js') }}"></script>
<script>
    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href="#top"]');

        if (!link) {
            return;
        }

        event.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
