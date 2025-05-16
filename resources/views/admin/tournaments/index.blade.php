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
                                    <th>@lang('Image | Name')</th>
                                    <th>@lang('Short Name')</th>
                                    <th>@lang('Season')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Version')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tournaments as $tournament)
                                    <tr>
                                        <td>
                                            <div class="user">
                                                <div class="thumb"><img src="{{ getImage(getFilePath('tournament') . '/' . $tournament->image, getFileSize('tournament')) }}" alt="{{ __($tournament->name) }}" class="plugin_bg"></div>
                                                <span class="name">{{ __($tournament->name) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            {{ __($tournament->short_name) }}
                                        </td>
                                        <td>
                                            <span>{{ $tournament->season }}</span>
                                        </td>
                                        <td>
                                            <span>{{ showAmount($tournament->price) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                echo $tournament->statusBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            @php
                                                echo $tournament->versionBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.tournament.add', $tournament->id) }}" class="btn btn--sm btn-outline--primary">
                                                    <i class="la la-pen"></i> @lang('Edit')
                                                </a>
                                                @if ($tournament->status == Status::ENABLE)
                                                    <button class="btn btn--sm btn-outline--danger confirmationBtn" data-question="@lang('Are you sure to disable this tournament?')" data-action="{{ route('admin.tournament.status', $tournament->id) }}"><i class="la la-eye-slash"></i>@lang('Disable')</button>
                                                @else
                                                    <button class="btn btn--sm btn-outline--success confirmationBtn" data-question="@lang('Are you sure to enable this tournament?')" data-action="{{ route('admin.tournament.status', $tournament->id) }}"><i class="la la-eye"></i>@lang('Enable')</button>
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
                @if ($tournaments->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($tournaments) @endphp
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder='Search by Name' />
    <a href="{{ route('admin.tournament.add') }}" class="btn btn-outline--primary"><i class="las la-plus"></i>@lang('Add New')</a>
@endpush
