@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card  ">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('S.N.')</th>
                                    <th>@lang('Title')</th>
                                    <th>@lang('Video')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reels as $reel)
                                    <tr>
                                        <td>{{ $reels->firstItem() + $loop->index }}</td>
                                        <td>{{ __($reel->title) }}</td>
                                        <td>
                                            <a href="{{ $reel->reelVideo }}" target="_blank">
                                                {{ $reel->reelVideo }}
                                            </a>
                                        </td>
                                        <td data-action="@lang('Action')">
                                            <div class="button--group">
                                                <button class="btn btn--sm btn-outline--primary editBtn" data-reel="{{ $reel }}"><i class="la la-pencil"></i>@lang('Edit')</button>

                                                <button class="btn btn--sm btn-outline--danger confirmationBtn" data-id="{{ $reel->id }}" data-question="@lang('Are you sure to remove this reel?')" data-action="{{ route('admin.item.reel.remove', $reel->id) }}"><i class="la la-trash"></i>@lang('Delete')</button>
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
                @if ($reels->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($reels) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="reelsModal" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Title')</label>
                            <input class="form-control" name="title" type="text" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('File')<span class="text--danger">*</span></label>
                            <input class="form-control" name="video" type="file">
                        </div>
                        <div class="form-group">
                            <label>@lang('Short Description')</label>
                            <textarea name="description" class="form-control" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <button class="btn btn--sm btn-outline--primary addBtn"><i class="la la-plus"></i> @lang('Add New')</button>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            var modal = $('#reelsModal');
            $('.addBtn').on('click', function() {
                modal.find('.modal-title').text(`@lang('Add New Reel')`);
                modal.find('form').attr('action', `{{ route('admin.item.reel.store') }}`);
                modal.modal('show');
            });

            $('.editBtn').on('click', function() {
                var data = $(this).data('reel');
                modal.find('.modal-title').text(`@lang('Update Reel Video')`);
                modal.find('form').attr('action', `{{ route('admin.item.reel.store', '') }}/${data.id}`);
                modal.find('[name="title"]').val(data.title);
                modal.find('[name="description"]').val(data.description);
                modal.modal('show');
            });

            modal.on('hidden.bs.modal', function() {
                $('#reelsModal form')[0].reset();
            });

        })(jQuery);
    </script>
@endpush
