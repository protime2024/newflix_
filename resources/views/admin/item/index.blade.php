@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card  ">
                <div class="card-body p-0">
                    <div class="table-responsive--md table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('Title')</th>
                                    <th>@lang('Category')</th>
                                    <th>@lang('Subcategory')</th>
                                    <th>@lang('Item Type')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>{{ $item->title }}</td>
                                        <td>{{ $item->category->name }}</td>
                                        <td>{{ @$item->sub_category->name ?? 'N/A' }}</td>
                                        <td>
                                            @if ($item->versionName == 'Episode')
                                                <span class="badge badge--primary">@lang('Episode Item')</span>
                                            @elseif(in_array($item->versionName, ['Free', 'Paid', 'Rent']))
                                                <span class="badge badge--success">@lang('Single Item')</span>
                                            @else
                                                <span class="badge badge--warning">@lang('Trailer')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                echo $item->statusBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <a class="btn btn--sm btn-outline--primary" href="{{ route('admin.item.edit', $item->id) }}">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </a>

                                                <button class="btn btn--sm btn-outline--info" data-bs-toggle="dropdown" type="button" aria-expanded="false"><i class="las la-ellipsis-v"></i>@lang('More')</button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item threshold" href="{{ route('watch', $item->slug) }}" target="_blank"> <i class="las la-eye"></i> @lang('Preview') </a>
                                                    @if ($item->item_type == Status::EPISODE_ITEM)
                                                        <a class="dropdown-item threshold" href="{{ route('admin.item.episodes', $item->id) }}">
                                                            <i class="las la-list"></i> @lang('Episodes')
                                                        </a>
                                                    @else
                                                        @if ($item->video)
                                                            <a class="dropdown-item threshold" href="{{ route('admin.item.updateVideo', $item->id) }}">
                                                                <i class="las la-cloud-upload-alt"></i> @lang('Update Video')
                                                            </a>
                                                            <a class="dropdown-item threshold" href="{{ route('admin.item.ads.duration', $item->id) }}">
                                                                <i class="lab la-buysellads"></i> @lang('Update Ads')
                                                            </a>
                                                            <a class="dropdown-item threshold" href="{{ route('admin.item.subtitle.list', [$item->id, '']) }}">
                                                                <i class="las la-file-audio"></i> @lang('Subtitles')
                                                            </a>
                                                            <a class="dropdown-item threshold" href="{{ route('admin.item.report', [$item->id, '']) }}">
                                                                <i class="las la-chart-area"></i> @lang('Report')
                                                            </a>
                                                        @else
                                                            <a class="dropdown-item threshold" href="{{ route('admin.item.uploadVideo', $item->id) }}">
                                                                <i class="las la-cloud-upload-alt"></i> @lang('Upload Video')
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a class="dropdown-item threshold confirmationBtn" data-action="{{ route('admin.item.send.notification', $item->id) }}" data-question="@lang('Are you sure to send notifications to all users?')" href="javascript:void(0)"> <i class="las la-bell"></i> @lang('Send Notification') </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($items->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($items) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />

    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">@lang('Import Your Items')</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.item.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <div id="dropZone">
                                <p id="dropZoneText">@lang('Drag and drop a file here or click to select one')</p>
                                <input type="file" name="file" id="fileInput" accept=".csv">
                            </div>
                        </div>
                        <code>
                            @lang('Please download the CSV template file below. This template contains the required columns and sample data to guide you in preparing your CSV file.')
                            <a href="{{ getImage('assets/images/items.csv') }}" download>@lang('Click Here')</a>
                        </code>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn--primary">@lang('Upload')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder="Search by Name" />
    <a class="btn btn-outline--primary" href="{{ route('admin.item.create') }}"><i class="la la-plus"></i>@lang('Add New')</a>
    <button class="btn btn-outline--dark" type="button" data-bs-toggle="modal" data-bs-target="#uploadModal"><i class="las la-lg la-file-csv"></i>@lang('Import')</button>
@endpush

@push('style')
    <style>
        .table-responsive {
            overflow-x: unset !important;
        }

        #dropZone {
            border: 2px dashed #4634ff;
            padding: 40px;
            text-align: center;
            cursor: pointer;
        }

        #fileInput {
            display: none;
        }
    </style>
@endpush


@push('script')
    <script>
        (function($) {
            var dropZone = $('#dropZone');
            var fileInput = $('#fileInput');
            var dropZoneText = $('#dropZoneText');

            fileInput.on('click', function(e) {
                e.stopPropagation();
            });

            dropZone.on('click', function() {
                fileInput.click();
            });

            dropZone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.css('border-color', '#007bff');
            });

            dropZone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.css('border-color', '#cccccc');
            });

            dropZone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.css('border-color', '#cccccc');
                var files = e.originalEvent.dataTransfer.files;
                fileInput.prop('files', files);
                dropZoneText.text(files[0].name);
            });

            fileInput.on('change', function() {
                if (fileInput.prop('files').length > 0) {
                    dropZoneText.text(fileInput.prop('files')[0].name);
                }
            });
        })(jQuery)
    </script>
@endpush
