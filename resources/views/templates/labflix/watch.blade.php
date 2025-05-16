<?php 

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$itemTypePhp = isset($episodes) && !empty($episodes) ? 'episode' : ($item->version == 0 ? 'free' : 'paid');
$itemIdPhp=$item->id;


$host = 'localhost';
$username = 'protuxav_Admin';
$password = 'ae@F@osaIh81';
$database = 'protuxav_NewFlix';

$conn = new mysqli($host, $username, $password, $database);

$userId = auth()->id() ?? null;

$tmp_stmt = $conn->prepare("SELECT rent_price,category_id FROM items WHERE id = ?");
$tmp_stmt->bind_param("i", $itemIdPhp);
$tmp_stmt->execute();
$tmp_result = $tmp_stmt->get_result();
$tmp_row = $tmp_result->fetch_assoc();
$itemPrice = (int) $tmp_row['rent_price'];
$category_id=$tmp_row['category_id'];
if ($category_id==5){$itemTypePhp='free';}


$showModal = false;

if (is_null($userId)) {
    header('Location: https://newflix.net/user/login');
    exit; 
}

if ($itemTypePhp == 'paid' || $itemTypePhp == 'episode') {
    $stmt = $conn->prepare("SELECT expired_date 
                            FROM subscriptions 
                            WHERE user_id = ? AND expired_date > CURRENT_TIMESTAMP;");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 1) {
        $stmt = $conn->prepare("SELECT created_on 
                                FROM trial 
                                WHERE user_id = ? AND item_id = ? AND created_on >= DATE_SUB(NOW(), INTERVAL 7 DAY);");
        $stmt->bind_param("ii", $userId, $itemIdPhp);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows < 1) {
            $showModal = true;
        }
    }
}


?>


@if($showModal)
    <!-- Modal Structure -->
    <div class="modal alert-modal" id="alertModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <span class="alert-icon"><i class="fas fa-question-circle"></i></span>
                    <p class="modal-description">Subscription Alert!</p>
                    <p class="modal--text">Please subscribe to a plan to view our paid items</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn--dark btn--sm" data-bs-dismiss="modal" type="button">Cancel</button>
                    <?php if ($itemPrice > 0) { echo '<form action="https://newflix.net/payment/Single_Item/fib/payment.php" method="POST"><input type="hidden" name="itemId" value="' . $itemIdPhp . '"><input type="hidden" name="userID" value="' . $userId . '"><button class="btn btn--base btn--sm" type="submit">Buy For ' . $itemPrice . ' IQD</button></form>'; } ?>
                    <a class="btn btn--base btn--sm" href="{{ route('subscription') }}">Subscribe Now</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var alertModal = new bootstrap.Modal(document.getElementById("alertModal"));
            alertModal.show();
        });
    </script>
@endif



@extends($activeTemplate . 'layouts.frontend')
@section('content')

<?php if ($showModal == true){
goto skip;
} ?>

    <div class="pt-80 pb-80">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="main-video">
                        <!--<video class="video-player" playsinline controls data-poster="{{ getImage(getFilePath('item_landscape') . '/' . $item->image->landscape) }}">
                            @foreach ($videos as $video)
                                <source src="{{ $video->content }}" type="video/mp4" size="{{ $video->size }}" />
                            @endforeach
                            @foreach ($subtitles ?? [] as $subtitle)
                                <track kind="captions" label="{{ $subtitle->language }}" src="{{ getImage(getFilePath('subtitle') . '/' . $subtitle->file) }}" srclang="{{ $subtitle->code }}" />
                            @endforeach
                        </video>-->
                        <?php
$text = $video->content;

if (strpos($text, 'vimeo') !== false) {
    preg_match('/https?:\/\/(?:www\.)?vimeo\.com\/(\d+)/', $text, $matches);
    $videoId = isset($matches[1]) ? $matches[1] : null;
    ?>
    <div style="position: relative; padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
        height: 0; overflow: hidden;">
        <iframe src="https://player.vimeo.com/video/<?php echo $videoId; ?>" 
            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
            frameborder="0" allowfullscreen>
        </iframe>
    </div>
    <?php
} elseif (strpos($text, 'youtube') !== false) {
    preg_match('/https?:\/\/(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $text, $matches);
    $videoId = isset($matches[1]) ? $matches[1] : null;
    if ($videoId) {
        ?>
        <div style="position: relative; padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0; overflow: hidden;">
            <iframe src="https://www.youtube.com/embed/<?php echo $videoId; ?>" 
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                frameborder="0" allowfullscreen>
            </iframe>
        </div>
        <?php
    }}
    else {echo ($video->content);
    ?>
    
    
    
    <?php
}
?>

<?php
skip:
?>
                        @if ($item->version == Status::RENT_VERSION && !$watchEligable)
                            <div class="main-video-lock">
                                <div class="main-video-lock-content">
                                    <span class="icon"><i class="las la-lock"></i></span>
                                    <p class="title">@lang('Purchase Now')</p>
                                    <p class="price">
                                        <span class="price-amount">{{ showAmount($item->rent_price) }}</span>
                                        <span class="small-text ms-3">@lang('For') {{ $item->rental_period }} @lang('Days')</span>
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="ad-video position-relative d-none">
                        <video class="ad-player" style="display: none" id="ad-video"></video>
                        <div class="ad-links d-none">
                            @foreach ($adsTime ?? [] as $ads)
                                <source src="{{ $ads }}" type="video/mp4" />
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap  skip-video">
                            <span class="advertise-text d-none">@lang('Advertisement') - <span class="remains-ads-time">00:52</span></span>
                            <button class="skipButton d-none" id="skip-button" data-skip-time="0">@lang('Skip Ad')</button>
                        </div>
                    </div>

                    <div class="movie-content">
                        <div class="movie-content-inner d-sm-flex justify-content-between align-items-center flex-wrap">
                            <div class="movie-content-left">
                                <h3 class="title">{{ __($item->title) }}</h3>
                                <span class="sub-title">@lang('Category') : <span class="cat">{{ @$item->category->name }}</span>
                                    @if ($item->sub_category)
                                        @lang('Sub Category'): {{ @$item->sub_category->name }}
                                    @endif
                                </span>
                            </div>
                            <div class="movie-content-right mt-sm-0 mt-3">
                                <div class="movie-widget-area align-items-center">
                                    @auth
                                        @if ($watchEligable && gs('watch_party'))
                                            <button type="button" class="watch-party-btn watchPartyBtn">
                                                <i class="las la-desktop base--color"></i>
                                                <span>@lang('Watch party')</span>
                                            </button>
                                        @endif
                                    @endauth

                                    <span class="movie-widget">
                                        <i class="lar la-star base--color"></i>
                                        <span>{{ getAmount($item->ratings) }}</span>
                                    </span>

                                    <span class="movie-widget">
                                        <i class="lar la-eye color--danger"></i>
                                        <span>{{ getAmount($item->view) }} @lang('views')</span>
                                    </span>

                                    @php
                                        $wishlist = $item->wishlists->where('user_id', auth()->id())->count();
                                    @endphp

                                    <span class="movie-widget addWishlist {{ $wishlist ? 'd-none' : '' }}" data-id="{{ $item->id }}" data-type="item"><i class="las la-plus-circle"></i></span>
                                    <span class="movie-widget removeWishlist {{ $wishlist ? '' : 'd-none' }}" data-id="{{ $item->id }}" data-type="item"><i class="las la-minus-circle"></i></span>
                                </div>

                                <ul class="post-share d-flex align-items-center justify-content-sm-end justify-content-start flex-wrap">
                                    <li class="caption">@lang('Share') : </li>

                                    <li data-bs-toggle="tooltip" data-bs-placement="top" title="@lang('Facebook')">
                                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"><i class="lab la-facebook-f"></i></a>
                                    </li>
                                    <li data-bs-toggle="tooltip" data-bs-placement="top" title="@lang('Linkedin')">
                                        <a href="http://www.linkedin.com/shareArticle?mini=true&amp;url={{ urlencode(url()->current()) }}&amp;title={{ __(@$item->title) }}&amp;summary=@php echo strLimit(strip_tags($item->description), 130); @endphp"><i class="lab la-linkedin-in"></i></a>
                                    </li>
                                    <li data-bs-toggle="tooltip" data-bs-placement="top" title="@lang('Twitter')">
                                        <a href="https://twitter.com/intent/tweet?text={{ __(@$item->title) }}%0A{{ url()->current() }}"><i class="lab la-twitter"></i></a>
                                    </li>
                                    <li data-bs-toggle="tooltip" data-bs-placement="top" title="@lang('Pinterest')">
                                        <a href="http://pinterest.com/pin/create/button/?url={{ urlencode(url()->current()) }}&description={{ __(@$item->title) }}&media={{ getImage(getFilePath('item_landscape') . '/' . @$item->image->landscape) }}"><i class="lab la-pinterest"></i></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p class="mt-3">{{ __($item->preview_text) }}</p>
                    </div>

                    <div class="movie-details-content">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                                <div class="card mb-sm-3 col-12 order-sm-1 order-2 mt-3 p-0">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <h4 class="mb-3">@lang('Details')</h4>
                                                <p>{{ __($item->description) }}</p>
                                            </div>
                                            <div class="col-lg-6 mt-lg-0 mt-4">
                                                <h4 class="mb-3">@lang('Team')</h4>
                                                <ul class="movie-details-list">
                                                    <li>
                                                        <span class="caption">@lang('Director:')</span>
                                                        <span class="value">{{ __($item->team->director) }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="caption">@lang('Producer:')</span>
                                                        <span class="value">{{ __($item->team->producer) }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="caption">@lang('Cast:')</span>
                                                        <span class="value">{{ __($item->team->casts) }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="caption">@lang('Genres:')</span>
                                                        <span class="value">{{ __(@$item->team->genres) }}</span>
                                                    </li>
                                                    <li>
                                                        <span class="caption">@lang('Language:')</span>
                                                        <span class="value">{{ __(@$item->team->language) }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (!blank($episodes))
                                    <div class="card col-12 order-sm-2 order-1 p-0">
                                        <div class="card-body p-0">
                                            <ul class="movie-small-list movie-list-scroll">
                                                @foreach ($episodes as $episode)
                                                    @php
                                                        $status = true;
                                                    @endphp
                                                    <li class="movie-small d-flex align-items-center justify-content-between movie-item__overlay video-item flex-wrap"
                                                        data-img="{{ getImage(getFilePath('episode') . '/' . $episode->image) }}" data-text="{{ $episode->versionName }}">

                                                        <div class="caojtyektj d-flex align-items-center flex-wrap">
                                                            <div class="movie-small__thumb">
                                                                <img src="{{ getImage(getFilePath('episode') . '/' . $episode->image) }}" alt="@lang('image')">
                                                            </div>

                                                            <div class="movie-small__content">
                                                                <h5>{{ __($episode->title) }}</h5>
                                                                @if ($status)
                                                                    <a class="base--color" href="{{ route('watch', [$item->slug, $episode->id]) }}">@lang('Play Now')</a>
                                                                @else
                                                                    <a class="base--color" href="{{ route('subscription') }}">@lang('Subscribe to watch')</a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="movie-small__lock">
                                                            <span class="movie-small__lock-icon">
                                                                @if ($status)
                                                                    <i class="fas fa-unlock"></i>
                                                                @else
                                                                    <i class="fas fa-lock"></i>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="movie-section pb-80">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="section-header">
                        <h2 class="section-title">@lang('Related Items')</h2>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center mb-30-none">
                @foreach ($relatedItems as $related)
                    <div class="col-xxl-3 col-md-3 col-4 col-xs-6 mb-30">
                        <div class="movie-card" data-text="{{ $related->versionName }}">
                            <div class="movie-card__thumb thumb__2">
                                <img src="{{ getImage(getFilePath('item_portrait') . '/' . $related->image->portrait) }}" alt="@lang('image')">
                                <a class="icon" href="{{ route('watch', $related->slug) }}"><i class="fas fa-play"></i></a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <div class="watch-party-modal modal fade" id="watchPartyModal" data-bs-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class="las la-times"></i></button>
                <h3 class="title">@lang('Watch Party')</h3>
                <h6 class="tagline">@lang('Watch movies together with your friends and families.')</h6>
                <button class="btn btn--base startPartyBtn">@lang('Now Start Your Party') <i class="las la-long-arrow-alt-right"></i></button>
            </div>
        </div>
    </div>


    <div class="modal alert-modal" id="rentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('user.subscribe.video', $item->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <span class="alert-icon"><i class="fas fa-question-circle"></i></span>
                        <p class="modal-description">@lang('Confirmation Alert!')</p>
                        <p class="modal--text">@lang('Please purchase to this rent item for') {{ $item->rental_period }} @lang('days')</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--dark btn--sm" data-bs-dismiss="modal" type="button">@lang('Cancel')</button>
                        <button class="btn btn--base btn--sm" type="submit">@lang('Purchase Now')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('style')
<style>
#video-wrapper {
    position: relative;
    width: 100%;
    background: #000;
}

#video {
    width: 100%;
    height: auto;
}

.custom-controls {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.7);
    padding: 10px;
    opacity: 0;
    transition: opacity 0.3s;
}

#video-wrapper:hover .custom-controls {
    opacity: 1;
}

.progress-bar {
    width: 100%;
    height: 5px;
    background: rgba(255, 255, 255, 0.2);
    cursor: pointer;
    margin-bottom: 10px;
}

.progress {
    width: 0;
    height: 100%;
    background: #ff0000;
    transition: width 0.1s linear;
}

.controls-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.control-btn {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
}

.time-display {
    color: white;
    margin-left: 10px;
}

.volume-container {
    display: flex;
    align-items: center;
}

.volume-slider {
    width: 80px;
    margin-left: 10px;
}

.left-controls {
    display: flex;
    align-items: center;
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

<?php if ($showModal == true){
goto skip2;
} ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('video');
    const videoWrapper = document.getElementById('video-wrapper');
    const playPauseBtn = document.getElementById('play-pause');
    const muteBtn = document.getElementById('mute');
    const fullscreenBtn = document.getElementById('fullscreen');
    const progressBar = document.getElementById('progress-bar');
    const progress = document.getElementById('progress');
    const volumeSlider = document.getElementById('volume');
    const currentTimeSpan = document.getElementById('current-time');
    const durationSpan = document.getElementById('duration');

    // Initialize with the video source from PHP
    const videoSrc = "{{ $video->content }}";

    // Initialize HLS
    if (Hls.isSupported()) {
        const hls = new Hls({
            maxLoadingDelay: 4,
            maxRetryDelay: 4,
            retryDelayMs: 1000
        });
        hls.loadSource(videoSrc);
        hls.attachMedia(video);

        hls.on(Hls.Events.ERROR, function(event, data) {
            if (data.fatal) {
                switch(data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        hls.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        hls.recoverMediaError();
                        break;
                    default:
                        hls.destroy();
                        break;
                }
            }
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = videoSrc;
    }

    // Play/Pause
    playPauseBtn.addEventListener('click', () => {
        if (video.paused) {
            let watchEligable = "{{ @$watchEligable }}";
            if (!Number(watchEligable)) {
                var modal = $('#alertModal');
                modal.modal('show');
                return false;
            }
            video.play();
            playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
        } else {
            video.pause();
            playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
        }
    });

    // Mute
    muteBtn.addEventListener('click', () => {
        video.muted = !video.muted;
        muteBtn.innerHTML = video.muted ? 
            '<i class="fas fa-volume-mute"></i>' : 
            '<i class="fas fa-volume-up"></i>';
        volumeSlider.value = video.muted ? 0 : video.volume;
    });

    // Volume
    volumeSlider.addEventListener('input', (e) => {
        video.volume = e.target.value;
        video.muted = e.target.value === 0;
        muteBtn.innerHTML = video.muted ? 
            '<i class="fas fa-volume-mute"></i>' : 
            '<i class="fas fa-volume-up"></i>';
    });

    // Progress bar
    video.addEventListener('timeupdate', () => {
        const percentage = (video.currentTime / video.duration) * 100;
        progress.style.width = percentage + '%';
        currentTimeSpan.textContent = formatTime(video.currentTime);
    });

    video.addEventListener('loadedmetadata', () => {
        durationSpan.textContent = formatTime(video.duration);
    });

    progressBar.addEventListener('click', (e) => {
        const pos = (e.pageX - progressBar.offsetLeft) / progressBar.offsetWidth;
        video.currentTime = pos * video.duration;
    });

    // Fullscreen
    fullscreenBtn.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            videoWrapper.requestFullscreen();
            fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
        } else {
            document.exitFullscreen();
            fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
        }
    });

    // Time formatter
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        seconds = Math.floor(seconds % 60);
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    // Keyboard controls
    document.addEventListener('keydown', (e) => {
        switch(e.key.toLowerCase()) {
            case ' ':
            case 'k':
                e.preventDefault();
                playPauseBtn.click();
                break;
            case 'm':
                muteBtn.click();
                break;
            case 'f':
                fullscreenBtn.click();
                break;
            case 'arrowleft':
                video.currentTime -= 5;
                break;
            case 'arrowright':
                video.currentTime += 5;
                break;
            case 'arrowup':
                video.volume = Math.min(1, video.volume + 0.1);
                volumeSlider.value = video.volume;
                break;
            case 'arrowdown':
                video.volume = Math.max(0, video.volume - 0.1);
                volumeSlider.value = video.volume;
                break;
        }
    });
});
</script>
    <?php skip2: ?>
@endpush
@push('context')
    oncontextmenu="return false"
@endpush
