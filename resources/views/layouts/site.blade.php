<!doctype html>
<html lang="@yield('html_lang', 'en-US')">
@php
    $pageTitle = trim($__env->yieldContent('title', 'RohrFrisch'));
    $pageHead = trim($__env->yieldContent('head'));
    $pageScripts = trim($__env->yieldContent('scripts'));
    $defaultDescription = trim($__env->yieldContent(
        'meta_description',
        'RohrFrisch bietet Abflussreinigung, Rohrreinigung und Kanalservice in Wien, Niederoesterreich und Burgenland.'
    ));
    $canonicalUrl = url()->current();
    $assetIdsToStrip = [
        'jquery-migrate-js',
        'perfect-scrollbar-css',
        'perfect-scrollbar-wpc-css',
        'woosq-feather-css',
        'woosq-frontend-css',
        'perfect-scrollbar-js',
        'woosq-frontend-js',
    ];
    $duplicateLinkHrefs = [];
    $duplicateScriptSrcs = [];

    $removeTaggedAssets = static function (string $markup, array $assetIds): string {
        foreach ($assetIds as $assetId) {
            $markup = preg_replace(
                '#\s*<link\b(?=[^>]*\bid="' . preg_quote($assetId, '#') . '")[^>]*>\s*#i',
                PHP_EOL,
                $markup
            ) ?? $markup;

            $markup = preg_replace(
                '#\s*<script\b(?=[^>]*\bid="' . preg_quote($assetId, '#') . '")(?![^>]*\bsrc=)[^>]*>.*?</script>\s*#is',
                PHP_EOL,
                $markup
            ) ?? $markup;

            $markup = preg_replace(
                '#\s*<script\b(?=[^>]*\bid="' . preg_quote($assetId, '#') . '")[^>]*\bsrc="[^"]*"[^>]*>\s*</script>\s*#i',
                PHP_EOL,
                $markup
            ) ?? $markup;
        }

        return $markup;
    };

    $dedupeLinkHrefs = static function (string $markup) use (&$duplicateLinkHrefs): string {
        return preg_replace_callback(
            '#<link\b(?=[^>]*\bhref="([^"]+)")[^>]*>#i',
            static function (array $matches) use (&$duplicateLinkHrefs): string {
                $href = $matches[1];

                if (isset($duplicateLinkHrefs[$href])) {
                    return '';
                }

                $duplicateLinkHrefs[$href] = true;

                return $matches[0];
            },
            $markup
        ) ?? $markup;
    };

    $dedupeScriptSrcs = static function (string $markup) use (&$duplicateScriptSrcs): string {
        return preg_replace_callback(
            '#<script\b(?=[^>]*\bsrc="([^"]+)")[^>]*>\s*</script>#i',
            static function (array $matches) use (&$duplicateScriptSrcs): string {
                $src = $matches[1];

                if (isset($duplicateScriptSrcs[$src])) {
                    return '';
                }

                $duplicateScriptSrcs[$src] = true;

                return $matches[0];
            },
            $markup
        ) ?? $markup;
    };

    if ($pageHead !== '') {
        $pageHead = $removeTaggedAssets($pageHead, $assetIdsToStrip);
        $pageHead = $dedupeLinkHrefs($pageHead);

        $pageHead = preg_replace_callback(
            '#<link\s+rel="canonical"\s+href="([^"]*)"\s*/?>#i',
            static function (array $matches) use ($canonicalUrl): string {
                $href = trim($matches[1]);

                if ($href === '' || $href === '/') {
                    return '<link rel="canonical" href="' . e($canonicalUrl) . '">';
                }

                if (preg_match('#^https?://#i', $href)) {
                    return $matches[0];
                }

                return '<link rel="canonical" href="' . e(url($href)) . '">';
            },
            $pageHead
        ) ?? $pageHead;

        if (! preg_match('#<meta\s+name="description"#i', $pageHead)) {
            $pageHead .= PHP_EOL . '<meta name="description" content="' . e($defaultDescription) . '">';
        }

        if (! preg_match('#<link\s+rel="canonical"#i', $pageHead)) {
            $pageHead .= PHP_EOL . '<link rel="canonical" href="' . e($canonicalUrl) . '">';
        }
    }

    if ($pageScripts !== '') {
        $pageScripts = $removeTaggedAssets($pageScripts, $assetIdsToStrip);
        $pageScripts = preg_replace(
            '#\s*<script\b(?=[^>]*\bid="woosq-frontend-js-extra")(?![^>]*\bsrc=)[^>]*>.*?</script>\s*#is',
            PHP_EOL,
            $pageScripts
        ) ?? $pageScripts;
        $pageScripts = $dedupeScriptSrcs($pageScripts);
    }
@endphp
<head>
    <title>{{ $pageTitle }}</title>
    {!! $pageHead !!}
    <style>
        @media (max-width: 767px) {
            body {
                padding-bottom: 104px;
            }

            .elementor-element-1260dbe.elementor-hidden-desktop {
                display: none !important;
            }

            .elementor-1990 .elementor-element.elementor-element-c77c2af {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .elementor-1990 .elementor-element.elementor-element-1f32ff8,
            .elementor-1990 .elementor-element.elementor-element-ea25feb,
            .elementor-1990 .elementor-element.elementor-element-e84c596 {
                width: 100% !important;
                max-width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .elementor-1990 .elementor-element.elementor-element-a5dfaef > .elementor-widget-container {
                margin: 0 0 12px 0 !important;
            }

            .elementor-element.elementor-element-78137cc .elementor-form-fields-wrapper {
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
                margin-top: 14px !important;
            }

            .elementor-element.elementor-element-78137cc .elementor-field-group {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                margin: 0 !important;
            }

            .elementor-element.elementor-element-78137cc .elementor-button {
                width: 100% !important;
            }

            .elementor-element.elementor-element-e84c596 {
                position: static !important;
                width: 100% !important;
                margin-top: 18px !important;
                min-height: auto !important;
            }

            .elementor-element.elementor-element-e84c596 .elementor-widget-container,
            .elementor-element.elementor-element-e84c596 p,
            .elementor-element.elementor-element-e84c596 p span {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                white-space: normal !important;
                overflow-wrap: break-word !important;
                line-height: 1.6 !important;
                text-align: left !important;
            }

            .scroll-top {
                display: none !important;
            }
        }
    </style>
</head>
<body @yield('body_attributes')>
    @yield('content')
    @if (session('form_success'))
        <script>
            window.addEventListener('load', function () {
                alert(@json(session('form_success')));
            });
        </script>
    @endif
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            if (document.querySelector('main, [role="main"]')) {
                applyAccessibleLabels();
                return;
            }

            var mainTarget = document.querySelector('[data-elementor-post-type="page"], [data-elementor-type="wp-page"], .elementor.elementor-page');

            if (mainTarget) {
                mainTarget.setAttribute('role', 'main');
            }

            applyAccessibleLabels();

            function applyAccessibleLabels() {
                var controls = document.querySelectorAll('form input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="image"]):not([type="reset"]), form select, form textarea');

                controls.forEach(function (control) {
                    if (control.labels && control.labels.length > 0) {
                        return;
                    }

                    if (control.hasAttribute('aria-label') || control.hasAttribute('aria-labelledby') || control.hasAttribute('title')) {
                        return;
                    }

                    var label = control.getAttribute('placeholder')
                        || control.getAttribute('name')
                        || control.getAttribute('id')
                        || '';

                    label = label
                        .replace(/^form_fields\[/i, '')
                        .replace(/\]$/i, '')
                        .replace(/^menu-\d+$/i, 'Service')
                        .replace(/[_-]+/g, ' ')
                        .trim();

                    if (!label) {
                        if (control.tagName === 'SELECT') {
                            label = 'Service';
                        } else if (control.tagName === 'TEXTAREA') {
                            label = 'Nachricht';
                        } else if ((control.getAttribute('type') || '').toLowerCase() === 'email') {
                            label = 'Email';
                        } else {
                            label = 'Formularfeld';
                        }
                    }

                    control.setAttribute('aria-label', label);
                });
            }
        });
    </script>
    {!! $pageScripts !!}
    <script>
        window.addEventListener('load', function () {
            if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.slick !== 'function') {
                return;
            }

            var $ = window.jQuery;

            $('.themeholy-carousel').each(function () {
                var $carousel = $(this);

                if ($carousel.hasClass('slick-initialized')) {
                    return;
                }

                var slideShow = parseInt($carousel.attr('data-slide-show') || '1', 10);
                var scrollCount = parseInt($carousel.attr('data-slide-scroll') || '1', 10);
                var lgShow = parseInt($carousel.attr('data-lg-slide-show') || slideShow, 10);
                var mdShow = parseInt($carousel.attr('data-md-slide-show') || lgShow, 10);
                var smShow = parseInt($carousel.attr('data-sm-slide-show') || mdShow, 10);
                var xsShow = parseInt($carousel.attr('data-xs-slide-show') || '1', 10);
                var arrows = $carousel.attr('data-arrows') === 'true';
                var dots = $carousel.attr('data-dots') === 'true';
                var autoplay = $carousel.attr('data-autoplay') === 'true';
                var fade = $carousel.attr('data-fade') === 'true';
                var centerMode = $carousel.attr('data-center-mode') === 'true';
                var adaptiveHeight = $carousel.attr('data-adaptive-height') === 'true';
                var focusOnSelect = $carousel.attr('data-focuson-select') === 'true';

                $carousel.slick({
                    slidesToShow: slideShow,
                    slidesToScroll: scrollCount,
                    infinite: true,
                    arrows: arrows,
                    dots: dots,
                    autoplay: autoplay,
                    autoplaySpeed: 6000,
                    speed: 1000,
                    fade: fade,
                    centerMode: centerMode,
                    adaptiveHeight: adaptiveHeight,
                    focusOnSelect: focusOnSelect,
                    prevArrow: '<button type="button" class="slick-prev slick-arrow" aria-label="Previous slide"><i class="fal fa-arrow-left"></i></button>',
                    nextArrow: '<button type="button" class="slick-next slick-arrow" aria-label="Next slide"><i class="fal fa-arrow-right"></i></button>',
                    responsive: [
                        {
                            breakpoint: 1400,
                            settings: {
                                slidesToShow: lgShow,
                                slidesToScroll: Math.min(scrollCount, lgShow)
                            }
                        },
                        {
                            breakpoint: 1200,
                            settings: {
                                slidesToShow: mdShow,
                                slidesToScroll: Math.min(scrollCount, mdShow)
                            }
                        },
                        {
                            breakpoint: 992,
                            settings: {
                                slidesToShow: smShow,
                                slidesToScroll: Math.min(scrollCount, smShow)
                            }
                        },
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: xsShow,
                                slidesToScroll: 1
                            }
                        }
                    ]
                });
            });
        });
    </script>
</body>
</html>
