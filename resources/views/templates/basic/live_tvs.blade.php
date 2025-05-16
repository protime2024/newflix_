@extends($activeTemplate . 'layouts.frontend')

@section('content')
    <section class="section--bg ptb-80">
        <div class="container">
            @foreach ($channelCategories as $category)
                @php
                    $eligable = false;
                    if (auth()->check()) {
                        $subscribedChannels = auth()->user()->subscribedChannelId();
                        $eligable = in_array($category->id, $subscribedChannels) ? true : false;
                    }
                @endphp
                <div class="tv-live py-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                        <div class="d-flex flex-wrap gap-2 gap-md-3 align-items-center">
                            <h3 class="fw-bold">{{ __($category->name) }} @lang('Channels')</h3>
                            @if (!$eligable)
                                <button class="btn btn--base btn--sm channelSubscribeBtn" data-id="{{ $category->id }}" data-price="{{ showAmount($category->price) }}">
                                    <span class="icon"><i class="fas fa-rocket fa-lg"></i></span>
                                    @lang('Subscribe')
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="tv-card-wrapper">
                        @foreach ($category->channels as $channel)
                            <div class="tv-card">
                                <div class="tv-card__thumb">
                                    <a href="{{ route('watch.tv', $channel->id) }}"><img src="{{ getImage(getFilePath('television') . '/' . $channel->image, getFileSize('television')) }}" class="w-100" alt=""></a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    <div class="modal alert-modal" id="channelModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <span class="alert-icon"><i class="fas fa-question-circle"></i></span>
                        <p class="modal-description">@lang('Confirmation Alert!')</p>
                        <p class="modal--text">@lang('Are you sure to subscribe to this channel group?')</p>
                        <p class="modal--text">@lang('Monthly subscription price is ') <span class="subscription-price"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--dark btn--sm" data-bs-dismiss="modal" type="button">@lang('No')</button>
                        <button class="btn btn--base btn--sm" type="submit">@lang('Yes')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .tv-card__thumb {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
        }

        @media (max-width: 1199px) {
            .tv-card__thumb {
                width: 106px;
                height: 106px;
            }
        }

        @media (max-width: 767px) {
            .tv-card__thumb {
                width: 93px;
                height: 93px;
            }
        }

        @media (max-width: 575px) {
            .tv-card__thumb {
                width: 85px;
                height: 85px;
            }
        }

        .tv-card-wrapper {
            display: flex;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 12px;
        }

        .tv-card {
            display: flex;
            justify-content: center;
        }
    </style>
@endpush
@push('script')
    <script>
        (function($) {
            "use strict";
            $('.channelSubscribeBtn').click(function(e) {
                e.preventDefault();
                let modal = $("#channelModal");
                modal.find('.subscription-price').text($(this).data('price'));
                modal.find('form').attr('action', `{{ route('user.subscribe.channel', '') }}/${$(this).data('id')}`)
                modal.modal('show');
            });
        })(jQuery)
    </script>
@endpush
