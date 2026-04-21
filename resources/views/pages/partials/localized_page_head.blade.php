<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="robots" content="noindex, follow">
@isset($canonicalPath)
<link rel="canonical" href="{{ $canonicalPath }}">
@endisset
<link rel="icon" href="{{ asset('site-clone/rohrfrisch.at/wp-content/uploads/2025/01/cropped-Untitled_design__52_-removebg-preview-32x32.png') }}" sizes="32x32">
<link rel="icon" href="{{ asset('site-clone/rohrfrisch.at/wp-content/uploads/2025/01/cropped-Untitled_design__52_-removebg-preview-192x192.png') }}" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('site-clone/rohrfrisch.at/wp-content/uploads/2025/01/cropped-Untitled_design__52_-removebg-preview-180x180.png') }}">
<link rel="stylesheet" href="{{ asset('site-clone/fonts.googleapis.com/css2--7290a22e33164c5bf010cfb73663cb06.css') }}" media="all">
<link rel="stylesheet" href="{{ asset('site-clone/rohrfrisch.at/wp-content/themes/plumer/assets/css/bootstrap.min--5b31223e2ef611d00bfe4e71ed403f89.css') }}" media="all">
<link rel="stylesheet" href="{{ asset('site-clone/rohrfrisch.at/wp-content/themes/plumer/assets/css/fontawesome.min--af8e0adc10d41272bdf2588e26ff8149.css') }}" media="all">
<link rel="stylesheet" href="{{ asset('site-clone/rohrfrisch.at/wp-content/themes/plumer/style--8a5471f88ca9c58e18cea05ebc9fe21a.css') }}" media="all">
<link rel="stylesheet" href="{{ asset('site-clone/rohrfrisch.at/wp-content/themes/plumer/assets/css/style--8a5471f88ca9c58e18cea05ebc9fe21a.css') }}" media="all">
<link rel="stylesheet" href="{{ asset('site-clone/rohrfrisch.at/wp-content/themes/plumer/assets/css/color.schemes--d4d009f105bbb43ac34bbadde940dd59.css') }}" media="all">
@isset($pageCssPath)
<link rel="stylesheet" href="{{ asset($pageCssPath) }}" media="all">
@endisset
<style>
    body.localized-page {
        background: #fff;
        color: #1a1a1a;
    }

    .localized-header {
        position: sticky;
        top: 0;
        z-index: 40;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(232, 58, 21, 0.12);
    }

    .localized-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 18px 0;
    }

    .localized-nav-links {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        font-weight: 600;
    }

    .localized-hero {
        position: relative;
        overflow: hidden;
        padding: 110px 0 70px;
        background: linear-gradient(rgba(16, 16, 16, 0.55), rgba(16, 16, 16, 0.72)),
            url('{{ asset('site-clone/rohrfrisch.at/wp-content/uploads/2025/01/plumber-repair-experienced-attentive-middleaged-man-examining-bottom-kitchen-sink.jpg') }}') center/cover no-repeat;
        color: #fff;
    }

    .localized-hero-card,
    .localized-panel,
    .localized-faq details {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 18px 60px rgba(0, 0, 0, 0.08);
    }

    .localized-hero-card {
        color: #1a1a1a;
        padding: 22px;
    }

    .localized-section {
        padding: 72px 0;
    }

    .localized-grid {
        display: grid;
        gap: 28px;
    }

    .localized-grid.two-col {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .localized-grid.three-col {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .localized-panel {
        padding: 28px;
        height: 100%;
    }

    .localized-panel img,
    .localized-hero-card img {
        width: 100%;
        border-radius: 18px;
        object-fit: cover;
    }

    .localized-eyebrow {
        display: inline-block;
        margin-bottom: 14px;
        color: #e83a15;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 13px;
    }

    .localized-cta {
        padding: 48px 36px;
        border-radius: 28px;
        background: linear-gradient(135deg, #e83a15, #2d1813);
        color: #fff;
    }

    .localized-faq details {
        padding: 18px 22px;
        margin-bottom: 14px;
    }

    .localized-faq summary {
        cursor: pointer;
        font-weight: 700;
        list-style: none;
    }

    .localized-footer {
        background: #111;
        color: #f5f5f5;
        padding: 56px 0 28px;
    }

    .localized-footer a,
    .localized-header a {
        color: inherit;
    }

    @media (max-width: 991.98px) {
        .localized-grid.two-col,
        .localized-grid.three-col {
            grid-template-columns: 1fr;
        }

        .localized-nav {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
