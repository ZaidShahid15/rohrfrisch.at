;(function($) {
    'use strict';
    $(window).on( 'elementor/frontend/init', function() {
        

    var GlobalJSLoad = function() {

        /*----------- Global Slider ----------*/
        $(".themeholy-carousel").each(function () {
            var themeholySlide = $(this);

            // Collect Data
            function d(data) {
                return themeholySlide.data(data);
            }

            // Custom Arrow Button
            var prevButton = '<button type="button" class="slick-prev"><i class="' + d("prev-arrow") + '"></i></button>',
                nextButton = '<button type="button" class="slick-next"><i class="' + d("next-arrow") + '"></i></button>';

            // Check for arrow wrapper
            if (d("arrows") == true) {
                if (!themeholySlide.closest(".arrow-wrap").length) {
                    themeholySlide.closest(".container").parent().addClass("arrow-wrap");
                }
            }
            themeholySlide.not('.slick-initialized').slick({
                dots: d("dots") ? true : false,
                fade: d("fade") ? true : false,
                arrows: d("arrows") ? true : false,
                speed: d("speed") ? d("speed") : 1000,
                asNavFor: d("asnavfor") ? d("asnavfor") : false,
                autoplay: d("autoplay") == false ? false : true,
                infinite: d("infinite") == false ? false : true,
                slidesToShow: d("slide-show") ? d("slide-show") : 1,
                adaptiveHeight: d("adaptive-height") ? true : false,
                centerMode: d("center-mode") ? true : false,
                autoplaySpeed: d("autoplay-speed") ? d("autoplay-speed") : 8000,
                centerPadding: d("center-padding") ? d("center-padding") : "0",
                focusOnSelect: d("focuson-select") == false ? false : true,
                pauseOnFocus: d("pauseon-focus") ? true : false,
                pauseOnHover: d("pauseon-hover") ? true : false,
                variableWidth: d("variable-width") ? true : false,
                vertical: d("vertical") ? true : false,
                verticalSwiping: d("vertical") ? true : false,
                swipeToSlide: (d('swipetoslide') ? true : false),
                prevArrow: d("prev-arrow")
                    ? prevButton
                    : '<button type="button" class="slick-prev"><i class="far fa-arrow-left"></i></button>',
                nextArrow: d("next-arrow")
                    ? nextButton
                    : '<button type="button" class="slick-next"><i class="far fa-arrow-right"></i></button>',
                rtl: $("html").attr("dir") == "rtl" ? true : false,
                responsive: [
                    {
                        breakpoint: 1600,
                        settings: {
                            arrows: d("xl-arrows") ? true : false,
                            dots: d("xl-dots") ? true : false,
                            slidesToShow: d("xl-slide-show")
                                ? d("xl-slide-show")
                                : d("slide-show"),
                            centerMode: d("xl-center-mode") ? true : false,
                            centerPadding: "0",
                        },
                    },
                    {
                        breakpoint: 1400,
                        settings: {
                            arrows: d("ml-arrows") ? true : false,
                            dots: d("ml-dots") ? true : false,
                            slidesToShow: d("ml-slide-show")
                                ? d("ml-slide-show")
                                : d("slide-show"),
                            centerMode: d("ml-center-mode") ? true : false,
                            centerPadding: 0,
                        },
                    },
                    {
                        breakpoint: 1200,
                        settings: {
                            arrows: d("lg-arrows") ? true : false,
                            dots: d("lg-dots") ? true : false,
                            slidesToShow: d("lg-slide-show")
                                ? d("lg-slide-show")
                                : d("slide-show"),
                            centerMode: d("lg-center-mode")
                                ? d("lg-center-mode")
                                : false,
                            centerPadding: 0,
                        },
                    },
                    {
                        breakpoint: 992,
                        settings: {
                            arrows: d("md-arrows") ? true : false,
                            dots: d("md-dots") ? true : false,
                            slidesToShow: d("md-slide-show")
                                ? d("md-slide-show")
                                : 1,
                            centerMode: d("md-center-mode")
                                ? d("md-center-mode")
                                : false,
                            centerPadding: 0,
                        },
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            arrows: d("sm-arrows") ? true : false,
                            dots: d("sm-dots") ? true : false,
                            slidesToShow: d("sm-slide-show")
                                ? d("sm-slide-show")
                                : 1,
                            centerMode: d("sm-center-mode")
                                ? d("sm-center-mode")
                                : false,
                            centerPadding: 0,
                            variableWidth: d("sm-variable-width") ? true : false,
                        },
                    },
                    {
                        breakpoint: 576,
                        settings: {
                            arrows: d("xs-arrows") ? true : false,
                            dots: d("xs-dots") ? true : false,
                            slidesToShow: d("xs-slide-show")
                                ? d("xs-slide-show")
                                : 1,
                            centerMode: d("xs-center-mode")
                                ? d("xs-center-mode")
                                : false,
                            centerPadding: 0,
                            variableWidth: d("xs-variable-width") ? true : false,
                        },
                    },
                    // You can unslick at a given breakpoint now by adding:
                    // settings: "unslick"
                    // instead of a settings object
                ],
            });
        });

        /*----------- Custom Animaiton For Slider ----------*/
       $('[data-ani-duration]').each(function () {
            var durationTime = $(this).data('ani-duration');
            $(this).css('animation-duration', durationTime);
        });
        
        $('[data-ani-delay]').each(function () {
            var delayTime = $(this).data('ani-delay');
            $(this).css('animation-delay', delayTime);
        });
        
        $('[data-ani]').each(function () {
            var animaionName = $(this).data('ani');
            $(this).addClass(animaionName);
            $('.slick-current [data-ani]').addClass('themeholy-animated');
        });
        
        $('.themeholy-carousel').on('afterChange', function (event, slick, currentSlide, nextSlide) {
            $(slick.$slides).find('[data-ani]').removeClass('themeholy-animated');
            $(slick.$slides[currentSlide]).find('[data-ani]').addClass('themeholy-animated');
        })

        if ($("[data-bg-src]").length > 0) {
            $("[data-bg-src]").each(function () {
                var src = $(this).attr("data-bg-src");
                $(this).css("background-image", "url(" + src + ")");
                $(this).removeAttr("data-bg-src").addClass("background-image");
            });
        }

        $("[data-slick-next]").each(function () {
            $(this).on("click", function (e) {
                e.preventDefault();
                $($(this).data("slick-next")).slick("slickNext");
            });
        });

        $("[data-slick-prev]").each(function () {
            $(this).on("click", function (e) {
                e.preventDefault();
                $($(this).data("slick-prev")).slick("slickPrev");
            });
        });

    
        $.fn.shapeMockup = function () {
            var $shape = $(this);
            $shape.each(function() {
              var $currentShape = $(this),
              shapeTop = $currentShape.data('top'),
              shapeRight = $currentShape.data('right'),
              shapeBottom = $currentShape.data('bottom'),
              shapeLeft = $currentShape.data('left');
              $currentShape.css({
                top: shapeTop,
                right: shapeRight,
                bottom: shapeBottom,
                left: shapeLeft,
              }).removeAttr('data-top')
              .removeAttr('data-right')
              .removeAttr('data-bottom')
              .removeAttr('data-left')
              .closest('.elementor-widget , .elementor-widget-wrap').css('position', 'static')
              .closest('section').addClass('shape-mockup-wrap');
            });
        };

        if ($('.shape-mockup')) {
            $('.shape-mockup').shapeMockup();
        }


        $('.progress-bar').waypoint(function() {
            $('.progress-bar').css({
            animation: "animate-positive 1.8s",
            opacity: "1"
            });
        }, { offset: '75%' });


        /*----------- 19. Process Step ----------*/
        $(".pstep-1").hover(function() {
            $(".step-1").addClass("active");
            $(".step-2, .step-3").removeClass("active");
        }, function() {
            $(".step-1, .step-3").removeClass("active");
            $(".step-2").addClass("active");
        });
        $(".pstep-3").hover(function() {
            $(".step-3").addClass("active");
            $(".step-2, .step-1").removeClass("active");
        }, function() {
            $(".step-2, .step-3").removeClass("active");
            $(".step-2").addClass("active");
        });


        $(".filter-active").imagesLoaded(function () {
            var $filter = ".filter-active",
                $filterItem = ".filter-item",
                $filterMenu = ".filter-menu-active";

            if ($($filter).length > 0) {
                var $grid = $($filter).isotope({
                    itemSelector: $filterItem,
                    filter: "*",
                    masonry: {
                        // use outer width of grid-sizer for columnWidth
                        // columnWidth: 1,
                    },
                });

                // filter items on button click
                $($filterMenu).on("click", "button", function () {
                    var filterValue = $(this).attr("data-filter");
                    $grid.isotope({
                        filter: filterValue,
                    });
                });

                // Menu Active Class
                $($filterMenu).on("click", "button", function (event) {
                    event.preventDefault();
                    $(this).addClass("active");
                    $(this).siblings(".active").removeClass("active");
                });
            }
        });
            

            
          
    };

    elementorFrontend.hooks.addAction('frontend/element_ready/global', GlobalJSLoad);



    /*---------------------------------- Section Image ------------------------------*/

    var secImage = function() {
        if ($('.img-half').length > 0) {
            $('.img-half').closest('.elementor-widget , .elementor-widget-wrap').css('position', 'static');
        }
    };

    //shape mockup//
    elementorFrontend.hooks.addAction('frontend/element_ready/plumerthumbwithvideo.default', secImage);



    });
}(jQuery));
