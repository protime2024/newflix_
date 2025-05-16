@extends($activeTemplate . 'layouts.frontend')

@section('content')
    <section class="pt-80 pb-80">
        <div class="container">
            <div class="row gy-4 justify-content-center">
                <div class="col-lg-8 pe-lg-5">
                    <div class="tv-detials">
                        <!-- Video.js CSS and JS -->
                        <link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet">
                        <script src="https://vjs.zencdn.net/7.20.3/video.min.js"></script>
                        <script src="https://cdn.jsdelivr.net/npm/videojs-contrib-quality-levels@2.0.9/dist/videojs-contrib-quality-levels.min.js"></script>
                        <script src="https://cdn.jsdelivr.net/npm/videojs-hls-quality-selector@1.1.5/dist/videojs-hls-quality-selector.min.js"></script>

                        <!-- Video container with Video.js -->
                        <div class="main-video" style="max-width: 100%; background: #000;">
                            <video 
                                id="liveTvPlayer" 
                                class="video-js vjs-default-skin vjs-big-play-centered" 
                                controls 
                                autoplay
                                preload="auto"
                                data-setup='{"fluid": true}'
                                poster="{{ getImage(getFilePath('television') . '/' . $tv->image, getFileSize('television')) }}"
                            >
                                <source src="{{ $tv->url }}" type="application/x-mpegURL">
                            </video>
                        </div>

                        <!-- Initialize Video.js with HLS support and quality selector -->
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const player = videojs('liveTvPlayer', {
                                    controls: true,
                                    autoplay: true,
                                    preload: 'auto',
                                    playbackRates: [0.5, 1, 1.5, 2],
                                    responsive: true,
                                    fluid: true
                                });

                                // Add quality selector plugin
                                player.qualityLevels();
                                player.hlsQualitySelector();
                                
                                // Handle player errors
                                player.on('error', function() {
                                    console.error('Player error:', player.error());
                                });

                                // Optionally set a loading indicator
                                player.on('waiting', function() {
                                    console.log('Buffering...');
                                });
                            });
                        </script>

                        <div class="tv-details-wrapper">
                            <div class="tv-details__content">
                                <div class="tv-details-channel">
                                    <div class="tv-details-channel__thumb">
                                        <img src="{{ getImage(getFilePath('television') . '/' . $tv->image, getFileSize('television')) }}" alt="">
                                    </div>
                                    <div class="tv-details-channel__content">
                                        <h5 class="tv-details-channel__title">{{ __($tv->title) }}</h5>
                                    </div>
                                </div>
                                <div class="tv-details__social-share">
                                    <ul class="post-share d-flex align-items-center justify-content-sm-end justify-content-start flex-wrap">
                                        <li class="caption">@lang('Share') : </li>
                                        <li><a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"><i class="lab la-facebook-f"></i></a></li>
                                        <li><a href="http://www.linkedin.com/shareArticle?mini=true&amp;url={{ urlencode(url()->current()) }}&amp;title={{ __($tv->title) }}"><i class="fab fa-linkedin-in"></i></a></li>
                                        <li><a href="https://twitter.com/intent/tweet?text={{ __($tv->title) }}%0A{{ url()->current() }}"><i class="lab la-twitter"></i></a></li>
                                        <li><a href="http://pinterest.com/pin/create/button/?url={{ urlencode(url()->current()) }}&description={{ __($tv->title) }}&media={{ getImage(getFilePath('television') . '/' . $tv->image, getFileSize('television')) }}"><i class="lab la-pinterest"></i></a></li>
                                    </ul>
                                </div>
                            </div>
                            <p class="tv-details__desc mt-4">{{ __($tv->description) }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="tv-details__sidebar">
                        <h3 class="tv-details__sidebar-title m-3">@lang('Other Tv Channels')</h3>
                        <ul class="tv-sidebar-list">
                            @foreach ($otherTvs as $otherTv)
                                <li class="tv-sidebar-list__item">
                                    <a class="tv-sidebar-list__link" href="{{ route('watch.tv', $otherTv->id) }}">
                                        <div class="tv-details-channel">
                                            <div class="tv-details-channel__thumb">
                                                <img src="{{ getImage(getFilePath('television') . '/' . $otherTv->image, getFileSize('television')) }}" alt="">
                                            </div>
                                            <div class="tv-details-channel__content">
                                                <h5 class="tv-details-channel__title">{{ __($otherTv->title) }}</h5>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
