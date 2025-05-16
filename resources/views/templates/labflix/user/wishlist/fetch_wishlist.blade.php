@forelse($wishlists as $wishlist)
    <li class="wishlist-card-list__item">
        <div class="wishlist-card-wrapper">
            <a class="wishlist-card-list__link" href="{{ @$wishlist->episode_id ? route('watch', @$wishlist->episode->item->slug) : route('watch', @$wishlist->item->slug) }}" style="text-decoration: none; color: inherit; display: block;">
                <div class="wishlist-card" style="display: flex; align-items: center;">
                    <div class="wishlist-card__thumb" style="flex: 0 0 auto;">
                        @if ($wishlist->episode_id)
                            <img src="{{ getImage(getFilePath('episode') . '/' . @$wishlist->episode->image) }}" alt="@lang('image')" style="width: 80px; height: auto;">
                        @else
                            <img src="{{ getImage(getFilePath('item_portrait') . '/' . @$wishlist->item->image->portrait) }}" alt="@lang('image')" style="width: 80px; height: auto;">
                        @endif
                    </div>
                    <div class="wishlist-card__content" style="flex-grow: 1; margin-left: 10px;">
                        <h5 class="wishlist-card__title" style="margin: 0; display: inline;">
                            {{ __(@$wishlist->episode_id ? @$wishlist->episode->item->title . ' - ' . @$wishlist->episode->title : @$wishlist->item->title) }}
                        </h5>
                        <p class="wishlist-card__desc text-white" style="margin: 0;">{{ strLimit(@$wishlist->item->description, 60) }}</p>
                    </div>
                </div>
            </a>
            <div class="wishlist-card-wrapper__icon">
                <button class="text--danger basicConfirmationBtn" data-action="{{ route('user.wishlist.remove', $wishlist->id) }}" data-question="@lang('Are you sure to remove this item?')" type="button"><i class="las la-times"></i></button>
            </div>
        </div>
    </li>
@empty
    <li class="text-center">
        <i class="las text-muted la-4x la-clipboard-list"></i><br>
        <h4 class="mt-2 text-muted">@lang('No items found yet!')</h4>
    </li>
@endforelse