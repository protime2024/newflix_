@extends('admin.layouts.app')
@section('panel')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Image')</th>
                                    <th>@lang('Tournament')</th>
                                    <th>@lang('Team')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Start Time')</th>
                                    <th>@lang('Version')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($games as $game)
                                    <tr>
                                        <td>
                                            <div class="user">
                                                <div class="thumb"><img src="{{ getImage(getFilePath('game') . '/' . $game->image, getFileSize('game')) }}" alt="{{ __($game->name) }}" class="plugin_bg"></div>
                                            </div>
                                        </td>
                                        <td>
                                            {{ __(@$game->tournament->name) }}
                                        </td>
                                        <td>
                                            <span class="name">{{ @$game->teamOne->name }}</span> - <span>{{ @$game->teamTwo->name }}</span>
                                        </td>
                                        <td>
                                            <span>{{ showAmount($game->price) }}</span>
                                        </td>
                                        <td>
                                            <span>{{ showDateTime($game->start_time) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                echo $game->versionBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            @php
                                                echo $game->statusBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.game.add', $game->id) }}" class="btn btn--sm btn-outline--primary">
                                                    <i class="la la-pen"></i> @lang('Edit')
                                                </a>
                                                @if ($game->status == Status::ENABLE)
                                                    <button class="btn btn--sm btn-outline--danger confirmationBtn" data-question="@lang('Are you sure to disable this game?')" data-action="{{ route('admin.game.status', $game->id) }}"><i class="la la-eye-slash"></i>@lang('Disable')</button>
                                                @else
                                                    <button class="btn btn--sm btn-outline--success confirmationBtn" data-question="@lang('Are you sure to enable this game?')" data-action="{{ route('admin.game.status', $game->id) }}"><i class="la la-eye"></i>@lang('Enable')</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($games->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($games) @endphp
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form />
    <a href="{{ route('admin.game.add') }}" class="btn btn-outline--primary"><i class="las la-plus"></i>@lang('Add New')</a>
@endpush
