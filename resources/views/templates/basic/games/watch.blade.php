@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <section class="ptb-80">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="movie-item">
                        <video playsinline class="video-player" poster="{{ getImage(getFilePath('game') . '/' . $game->image, getFileSize('game')) }}">
                            <source src="{{ $game->link }}">
                        </video>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="recent-match-section section--bg ptb-80">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <ul class="event--tab nav nav-pills" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-details-tab" data-bs-toggle="pill" data-bs-target="#pills-details" type="button" role="tab" aria-controls="pills-details" aria-selected="true">@lang('Details')</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-details" role="tabpanel" aria-labelledby="pills-details-tab" tabindex="0">
                            <div class="row">
                                <div class="col-xl-7 col-lg-9">
                                    <div class="match-details-content">
                                        <h2 class="match-details-content__title">{{ __(@$game->teamOne->name) }} @lang('VS') {{ __(@$game->teamTwo->name) }}</h2>
                                        <p class="match-details-content__desc">
                                            {{ __($game->details) }}
                                        </p>
                                        <ul class="match-details-content__list">
                                            <li>
                                                <span class="title">@lang('Release Date')</span>
                                                <span class="desc">{{ showDateTime($game->start_time) }}</span>
                                            </li>
                                            <li>
                                                <span class="title">@lang('Tournament')</span>
                                                <span class="desc">{{ __(@$game->tournament->name) }}</span>
                                            </li>
                                            <li>
                                                <span class="title">@lang('Season')</span>
                                                <span class="desc">{{ $game->tournament->season }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('style')
    <style>
        .plyr__poster {
            background-size: cover;
        }
    </style>
@endpush

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/plyr.min.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/plyr.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/hls.min.js') }}"></script>
@endpush

@push('script')
    <script>
        $(document).ready(function() {
            $(document).find('.plyr__controls').addClass('d-none');
            $(document).find('.ad-video').find('.plyr__controls').addClass('d-none');
        });
        (function($) {
            "use strict";
            document.addEventListener('DOMContentLoaded', () => {
                const video = document.querySelector('video');
                const source = video.currentSrc;

                const controls = [
                    'play',
                    'play-large',
                ];

                const player = new Plyr(video, {
                    controls,
                    ratio: '16:9'
                });

                player.on('play', () => {
                    $(document).find('.plyr__controls').removeClass('d-none');
                });

                if (!Hls.isSupported()) {
                    video.src = source;
                } else {
                    const hls = new Hls();
                    hls.loadSource(source);
                    hls.attachMedia(video);
                    window.hls = hls;
                }
                window.player = player;
            });
        })(jQuery)
    </script>
@endpush
