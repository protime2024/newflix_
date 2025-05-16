<section class="section pb-80" data-section="top">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-header">
                    <h2 class="section-title">@lang('Latest Items')</h2>
                </div>
            </div>
        </div><!-- row end -->
        <div class="movie-slider-one">
            @foreach ($recent_added as $recent)
                <div class="movie-card" data-text="{{ $recent->versionName }}">
                    <div class="movie-card__thumb">
                        <img class="lazy-loading-img" data-src="{{ getImage(getFilePath('item_portrait') . '/' . $recent->image->portrait) }}" src="{{ asset('assets/global/images/lazy.png') }}" alt="@lang('image')">
                        <a class="icon" href="{{ route('watch', $recent->slug) }}"><i class="fas fa-play"></i></a>
                    </div>
                    <div class="movie-card__content">
                        <h6><a href="{{ route('watch', $recent->slug) }}">{{ __(isset($recent->title) ? short_string($recent->title, 17) : '') }}</a></h6>
                        <ul class="movie-card__meta">
                            <li><i class="far fa-eye color--primary"></i> <span>{{ isset($recent->view) ? numFormat($recent->view) : '0' }}</span></li>
                            <li><i class="fas fa-star color--glod"></i> <span>({{ isset($recent->ratings) ? $recent->ratings : '0' }})</span></li>
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<script>
    $(document).ready(function() {
        // Initialize the movie slider for the Latest Items section
        if ($('.section[data-section="top"] .movie-slider-one').length && !$('.section[data-section="top"] .movie-slider-one').hasClass('slick-initialized')) {
            $('.section[data-section="top"] .movie-slider-one').slick({
                infinite: true,
                slidesToShow: 6,
                slidesToScroll: 1,
                arrows: true,
                dots: false,
                autoplay: false,
                prevArrow: '<div class="prev slick-arrow"><i class="las la-long-arrow-alt-left"></i></div>',
                nextArrow: '<div class="next slick-arrow"><i class="las la-long-arrow-alt-right"></i></div>',
                responsive: [
                    {
                        breakpoint: 1200,
                        settings: {
                            slidesToShow: 5
                        }
                    },
                    {
                        breakpoint: 992,
                        settings: {
                            slidesToShow: 4
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 3
                        }
                    },
                    {
                        breakpoint: 576,
                        settings: {
                            slidesToShow: 2
                        }
                    }
                ]
            });
        }
    });
</script>