@if (!blank($reels))
    <section class="shorts-section ptb-80">
        <div class="container-fluid">
            <div class="seciton-title">
                <div class="seciton-title__left">
                    <h3 class="seciton-title__heading mb-0">@lang('Shorts')</h3>
                    <a href="{{ route('short.videos') }}" class="seciton-title__link">
                        @lang('View All') <span class="fs-16"> <i class="las la-arrow-right"></i> </span>
                    </a>
                </div>
                <div class="seciton-title__right">
                    <div class="Short-slider-arrow">
                        <div class="slider-prev">
                            <i class="fas fa-angle-left"></i>
                        </div>
                        <div class="slider-next">
                            <i class="fas fa-angle-right"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="shorts-slider overflow-hidden">
                <div class="swiper-wrapper">
                    @foreach ($reels as $reel)
                        <div class="swiper-slide">
                            <a href="{{ route('short.videos', $reel->id) }}" class="shorts-items">
                                <video class="video-player plyr-video" playsinline controls>
                                    <source src="{{ $reel->reelVideo }}" type="video/mp4" />
                                </video>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif

@push('style')
    <style>
        .plyr__control--overlaid,
        .plyr--video .plyr__control:focus-visible,
        .plyr--video .plyr__control:hover,
        .plyr--video .plyr__control[aria-expanded="true"] {
            background: transparent;
            color: hsl(var(--base));
        }
    </style>
@endpush
