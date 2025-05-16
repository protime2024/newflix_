<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Models\AdminNotification;
use App\Models\Advertise;
use App\Models\Category;
use App\Models\ChannelCategory;
use App\Models\DeviceToken;
use App\Models\Episode;
use App\Models\Frontend;
use App\Models\Game;
use App\Models\History;
use App\Models\Item;
use App\Models\Language;
use App\Models\LiveTelevision;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Reel;
use App\Models\ReelHistory;
use App\Models\Slider;
use App\Models\SubCategory;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\Tournament;
use App\Models\VideoReport;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller {
    public function index() {
        $pageTitle = 'Home';
        $sliders   = Slider::active()->with(['item' => function ($query) {
            $query->with('category', 'video');
        }])->orderBy('id', 'desc')->get();
        $featuredMovies = Item::active()->hasVideo()->where('featured', Status::YES)->orderBy('id', 'desc')->get();
        $advertise      = Advertise::where('device', 1)->where('ads_show', 1)->where('ads_type', 'banner')->inRandomOrder()->first();
        return view('Template::home', compact('pageTitle', 'sliders', 'featuredMovies', 'advertise'));
    }

    public function pages($slug) {
        $page        = Page::where('tempname', activeTemplate())->where('slug', $slug)->firstOrFail();
        $pageTitle   = $page->name;
        $sections    = $page->secs;
        $seoContents = $page->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::pages', compact('pageTitle', 'sections', 'seoContents', 'seoImage'));
    }

    public function contact() {
        $pageTitle = "Contact Us";
        $user      = auth()->user();
        return view('Template::contact', compact('pageTitle', 'user'));
    }

    public function contactSubmit(Request $request) {
        $request->validate([
            'name'    => 'required',
            'email'   => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required',
        ]);

        $request->session()->regenerateToken();

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        $random = getNumber();

        $ticket           = new SupportTicket();
        $ticket->user_id  = auth()->id() ?? 0;
        $ticket->name     = $request->name;
        $ticket->email    = $request->email;
        $ticket->priority = Status::PRIORITY_MEDIUM;

        $ticket->ticket     = $random;
        $ticket->subject    = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status     = Status::TICKET_OPEN;
        $ticket->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = auth()->user() ? auth()->user()->id : 0;
        $adminNotification->title     = 'A new contact message has been submitted';
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        $message                    = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message           = $request->message;
        $message->save();

        $notify[] = ['success', 'Ticket created successfully!'];

        return to_route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function policyPages($slug) {
        $policy      = Frontend::where('tempname', activeTemplateName())->where('slug', $slug)->where('data_keys', 'policy_pages.element')->firstOrFail();
        $pageTitle   = $policy->data_values->title;
        $seoContents = $policy->seo_content;
        $seoImage    = @$seoContents->image ? frontendImage('policy_pages', $seoContents->image, getFileSize('seo'), true) : null;
        return view('Template::policy', compact('policy', 'pageTitle', 'seoContents', 'seoImage'));
    }

    public function links($slug) {
        $policy      = Frontend::where('tempname', activeTemplateName())->where('slug', $slug)->where('data_keys', 'short_links.element')->firstOrFail();
        $pageTitle   = $policy->data_values->title;
        $seoContents = $policy->seo_content;
        $seoImage    = @$seoContents->image ? frontendImage('policy_pages', $seoContents->image, getFileSize('seo'), true) : null;
        return view('Template::policy', compact('policy', 'pageTitle', 'seoContents', 'seoImage'));
    }

    public function changeLanguage($lang = null) {
        $language = Language::where('code', $lang)->first();
        if (!$language) {
            $lang = 'en';
        }
        session()->put('lang', $lang);
        return back();
    }

    public function blogDetails($slug) {
        $blog        = Frontend::where('slug', $slug)->where('data_keys', 'blog.element')->firstOrFail();
        $pageTitle   = $blog->data_values->title;
        $seoContents = $blog->seo_content;
        $seoImage    = @$seoContents->image ? frontendImage('blog', $seoContents->image, getFileSize('seo'), true) : null;
        return view('Template::blog_details', compact('blog', 'pageTitle', 'seoContents', 'seoImage'));
    }

    public function cookieAccept() {
        Cookie::queue('gdpr_cookie', gs('site_name'), 43200);
    }

    public function cookiePolicy() {
        $cookieContent = Frontend::where('data_keys', 'cookie.data')->first();
        abort_if($cookieContent->data_values->status != Status::ENABLE, 404);
        $pageTitle = 'Cookie Policy';
        $cookie    = Frontend::where('data_keys', 'cookie.data')->first();
        return view('Template::cookie', compact('pageTitle', 'cookie'));
    }

    public function placeholderImage($size = null) {
        $imgWidth  = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text      = $imgWidth . '×' . $imgHeight;
        $fontFile  = realpath('assets/font/solaimanLipi_bold.ttf');
        $fontSize  = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image     = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill    = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgFill);
        $textBox    = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth  = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX      = ($imgWidth - $textWidth) / 2;
        $textY      = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function maintenance() {
        $pageTitle = 'Maintenance Mode';
        if (gs('maintenance_mode') == Status::DISABLE) {
            return to_route('home');
        }
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->first();
        return view('Template::maintenance', compact('pageTitle', 'maintenance'));
    }

    public function getSection(Request $request) {
        $data = [];
        if ($request->sectionName == 'end') {
            return response('end');
        }
        $items = Item::hasVideo();

        if ($request->sectionName == 'recent_added') {
            $data['recent_added'] = (clone $items)->where('item_type', Status::SINGLE_ITEM)->orderBy('id', 'desc')->limit(18)->get();
        } else if ($request->sectionName == 'latest_series') {
            $data['latestSerieses'] = (clone $items)->orderBy('id', 'desc')->where('item_type', Status::EPISODE_ITEM)->limit(12)->get();
        } else if ($request->sectionName == 'single') {
            $data['single'] = (clone $items)->orderBy('id', 'desc')->where('single', 1)->with('category')->first();
        } else if ($request->sectionName == 'latest_trailer') {
            $data['latest_trailers'] = (clone $items)->where('item_type', Status::SINGLE_ITEM)->where('is_trailer', 1)->orderBy('id', 'desc')->limit(12)->get();
        } else if ($request->sectionName == 'free_zone') {
            $data['frees'] = (clone $items)->free()->orderBy('id', 'desc')->limit(12)->get();
        } else if ($request->sectionName == 'top') {
            $data['mostViewsTrailer'] = (clone $items)->where('item_type', Status::SINGLE_ITEM)->where('is_trailer', 1)->orderBy('view', 'desc')->first();
            $data['topRateds']        = (clone $items)->orderBy('ratings', 'desc')->limit(4)->get();
            $data['trendings']        = (clone $items)->orderBy('view', 'desc')->where('trending', 1)->limit(4)->get();
        } else if ($request->sectionName == 'single1' || $request->sectionName == 'single2' || $request->sectionName == 'single3') {
            $data['single'] = (clone $items)->orderBy('id', 'desc')->where('single', Status::YES)->with('category')->get();
        }
        return view('Template::sections.' . $request->sectionName, $data);
    }

    public function watchVideo($slug, $episodeId = null) {
        if (is_numeric($slug)) {
            $item = Item::active()->where('id', $slug)->with('video.subtitles')->firstOrFail();
        } else {
            $item = Item::active()->where('slug', $slug)->with('video.subtitles')->firstOrFail();
        }

        $item->increment('view');

        $userHasSubscribed = (auth()->check() && auth()->user()->exp > now()) ? Status::ENABLE : Status::DISABLE;

        if ($item->item_type == Status::EPISODE_ITEM) {
            $episodes     = Episode::hasVideo()->with(['video', 'item'])->where('item_id', $item->id)->get();
            $relatedItems = $this->relatedItems($item->id, Status::EPISODE_ITEM);
            $pageTitle    = 'Episode Details';

            if ($episodes->isEmpty()) {
                $notify[] = ['error', 'Oops! There is no video'];
                return back()->withNotify($notify);
            }

            $subscribedUser = auth()->check() && (auth()->user()->exp > now());
            if ($episodeId) {
                $episode       = Episode::hasVideo()->findOrFail($episodeId);
                $firstVideo    = $episode->video;
                $isPaidItem    = $episode->version ? Status::ENABLE : Status::DISABLE;
                $activeEpisode = $episode;
            } else {
                $firstVideo    = $episodes[0]->video;
                $activeEpisode = $episodes[0];
                $isPaidItem    = $activeEpisode->version ? Status::ENABLE : Status::DISABLE;
                $episodeId     = $activeEpisode->id;
            }

            $this->storeHistory(episodeId: $activeEpisode->id);
            $this->storeVideoReport(episodeId: $activeEpisode->id);

            $video              = $firstVideo;
            $checkWatchEligable = $this->checkWatchEligableEpisode($activeEpisode, $userHasSubscribed);

        } else {
            $this->storeHistory($item->id);
            $this->storeVideoReport($item->id);

            $pageTitle          = 'Movie Details';
            $relatedItems       = $this->relatedItems($item->id, Status::SINGLE_ITEM);
            $episodes           = [];
            $video              = $item->video;
            $checkWatchEligable = $this->checkWatchEligableItem($item, $userHasSubscribed);
        }

        $watchEligable     = $checkWatchEligable[0];
        $hasSubscribedItem = $checkWatchEligable[1];

        if (!$video) {
            $notify[] = ['error', 'There are no videos for this item'];
            return back()->withNotify($notify);
        }

        $adsTime     = $video->getAds() ?? [];
        $subtitles   = $video->subtitles;
        $videos      = $this->videoList($video);
        $seoContents = $this->getItemSeoContent($item);

        return view('Template::watch', compact('pageTitle', 'item', 'relatedItems', 'seoContents', 'adsTime', 'subtitles', 'videos', 'episodes', 'episodeId', 'watchEligable', 'userHasSubscribed', 'hasSubscribedItem'));
    }

    protected function checkWatchEligableEpisode($episode, $userHasSubscribed) {
        if ($episode->version == Status::PAID_VERSION) {
            $watchEligable = $userHasSubscribed ? true : false;
        } else if ($episode->version == Status::RENT_VERSION) {
            $hasSubscribedItem = Subscription::active()->where('user_id', auth()->id())->where('item_id', $episode->item_id)->whereDate('expired_date', '>', now())->exists();
            if (@$episode->item->exclude_plan) {
                $watchEligable = $hasSubscribedItem ? true : false;
            } else {
                $watchEligable = ($userHasSubscribed || $hasSubscribedItem) ? true : false;
            }
        } else {
            $watchEligable = true;
        }
        return [$watchEligable, @$hasSubscribedItem ?? 0];
    }

    protected function checkWatchEligableItem($item, $userHasSubscribed) {
        if ($item->version == Status::PAID_VERSION) {
            $watchEligable = $userHasSubscribed ? true : false;
        } else if ($item->version == Status::RENT_VERSION) {
            $hasSubscribedItem = Subscription::active()->where('user_id', auth()->id())->where('item_id', $item->id)->whereDate('expired_date', '>', now())->exists();
            if ($item->exclude_plan) {
                $watchEligable = $hasSubscribedItem ? true : false;
            } else {
                $watchEligable = ($userHasSubscribed || $hasSubscribedItem) ? true : false;
            }
        } else {
            $watchEligable = true;
        }
        return [$watchEligable, @$hasSubscribedItem ?? 0];
    }

    private function videoList($video) {
        $videoFile = [];
        if ($video->three_sixty_video) {
            $videoFile[] = [
                'content' => getVideoFile($video, 'three_sixty'),
                'size'    => 360,
            ];
        }
        if ($video->four_eighty_video) {
            $videoFile[] = [
                'content' => getVideoFile($video, 'four_eighty'),
                'size'    => 480,
            ];
        }
        if ($video->seven_twenty_video) {
            $videoFile[] = [
                'content' => getVideoFile($video, 'seven_twenty'),
                'size'    => 720,
            ];
        }
        if ($video->thousand_eighty_video) {
            $videoFile[] = [
                'content' => getVideoFile($video, 'thousand_eighty'),
                'size'    => 1080,
            ];
        }

        return json_decode(json_encode($videoFile, true));
    }

    private function storeHistory($itemId = null, $episodeId = null) {
        if (auth()->check()) {
            if ($itemId) {
                $history = History::where('user_id', auth()->id())->orderBy('id', 'desc')->limit(1)->first();
                if (!$history || ($history && $history->item_id != $itemId)) {
                    $history          = new History();
                    $history->user_id = auth()->id();
                    $history->item_id = $itemId;
                    $history->save();
                }
            }
            if ($episodeId) {
                $history = History::where('user_id', auth()->id())->orderBy('id', 'desc')->limit(1)->first();
                if (!$history || ($history && $history->episode_id != $episodeId)) {
                    $history             = new History();
                    $history->user_id    = auth()->id();
                    $history->episode_id = $episodeId;
                    $history->save();
                }
            }
        }
    }

    protected function storeVideoReport($itemId = null, $episodeId = null) {
        $deviceId = md5($_SERVER['HTTP_USER_AGENT']);

        if ($itemId) {
            $report = VideoReport::whereDate('created_at', now())->where('device_id', $deviceId)->where('item_id', $itemId)->exists();
        }

        if ($episodeId) {
            $report = VideoReport::whereDate('created_at', now())->where('device_id', $deviceId)->where('episode_id', $episodeId)->exists();
        }
        if (!$report) {
            $videoReport             = new VideoReport();
            $videoReport->device_id  = $deviceId;
            $videoReport->item_id    = $itemId ?? 0;
            $videoReport->episode_id = $episodeId ?? 0;
            $videoReport->save();
        }
    }

    private function getItemSeoContent($item) {
        $seoContents['keywords']           = $item->meta_keywords ?? [];
        $seoContents['social_title']       = $item->title;
        $seoContents['description']        = strLimit(strip_tags($item->description), 150);
        $seoContents['social_description'] = strLimit(strip_tags($item->description), 150);
        $seoContents['image']              = getImage(getFilePath('item_landscape') . '/' . $item->image->landscape);
        $seoContents['image_size']         = '900x600';
        return $seoContents;
    }

    private function relatedItems($itemId, $itemType) {
        return Item::hasVideo()->orderBy('id', 'desc')->where('item_type', $itemType)->where('id', '!=', $itemId)->limit(8)->get();
    }

    public function category($id) {
        $category  = Category::findOrFail($id);
        $items     = Item::hasVideo()->where('category_id', $id)->where('status', 1)->orderBy('id', 'desc')->limit(12)->get();
        $pageTitle = $category->name;
        return view('Template::items', compact('pageTitle', 'items', 'category'));
    }

    public function subCategory($id) {
        $subcategory = SubCategory::findOrFail($id);
        $items       = Item::hasVideo()->where('sub_category_id', $id)->orderBy('id', 'desc')->limit(12)->get();
        $pageTitle   = $subcategory->name;
        return view('Template::items', compact('pageTitle', 'items', 'subcategory'));
    }

    public function search(Request $request) {
        $search = $request->search;
        if (!$search) {
            return redirect()->route('home');
        }
        $items = Item::search($search)->where('status', 1)->where(function ($query) {
            $query->orWhereHas('video')->orWhereHas('episodes', function ($video) {
                $video->where('status', 1)->whereHas('video');
            });
        })->orderBy('id', 'desc')->limit(12)->get();
        $pageTitle = "Result Showing For " . $search;
        return view('Template::items', compact('pageTitle', 'items', 'search'));
    }
    public function loadMore(Request $request) {
        if (isset($request->category_id)) {
            $data['category'] = Category::find($request->category_id);
            $data['items']    = Item::hasVideo()->where('category_id', $request->category_id)->orderBy('id', 'desc')->where('id', '<', $request->id)->take(6)->get();
        } else if (isset($request->subcategory_id)) {
            $data['sub_category'] = SubCategory::find($request->subcategory_id);
            $data['items']        = Item::hasVideo()->where('sub_category_id', $request->subcategory_id)->orderBy('id', 'desc')->where('id', '<', $request->id)->take(6)->get();
        } else if (isset($request->search)) {
            $data['search'] = $request->search;
            $data['items']  = Item::hasVideo()->search($request->search)->orderBy('id', 'desc')->where('id', '<', $request->id)->take(6)->get();
        } else {
            return response('end');
        }

        if ($data['items']->count() <= 0) {
            return response('end');
        }

        return view('Template::item_ajax', $data);
    }

   

    public function liveTelevision($id = 0) {
        $pageTitle         = 'Live TV list';
        $channelCategories = ChannelCategory::active()->withWhereHas('channels', function ($query) {
            $query->active();
        })->get();
        return view('Template::live_tvs', compact('pageTitle', 'channelCategories'));
    }



/* zz */
    public function watchTelevision($id) {
        /*if (!auth()->id()) {
            $notify[] = ['error', 'You must login to watch live TV'];
            return back()->withNotify($notify);
        }*/

        $tv            = LiveTelevision::with('category')->active()->findOrFail($id);
        $user          = auth()->user();
        
        // $hasSubscribed = Subscription::where('user_id', $user->id)->where('channel_category_id', $tv->channel_category_id)->where('expired_date', '>=', now())->active()->first();

        /*if (!$hasSubscribed) {
            $notify[] = ['error', 'You must subscribe to watch this live TV'];
            return to_route('live.tv')->withNotify($notify);
        }*/
        

        $pageTitle = $tv->title;
        $otherTvs  = LiveTelevision::whereHas('category', function ($query) {
            $query->active();
        })->active()->where('id', '!=', $id)->get();
        return view('Template::watch_tv', compact('pageTitle', 'tv', 'otherTvs'));
    }

    public function addWishList(Request $request) {
        if (!auth()->check()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You must be login to add an item to wishlist',
            ]);
        }

        $wishlist = new Wishlist();

        if ($request->type == 'item') {
            $data = Item::where('id', $request->id)->first();
        } else {
            $data              = Episode::where('id', $request->id)->first();
            $wishlist->item_id = $data->item_id;
        }
        if (!$data) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid request',
            ]);
        }
        $column            = $request->type . '_id';
        $wishlist->$column = $data->id;
        $exits             = Wishlist::where($column, $data->id)->where('user_id', auth()->id())->first();
        if (!$exits) {
            $wishlist->user_id = auth()->id();
            $wishlist->save();
            return response()->json([
                'status'  => 'success',
                'message' => 'Video added to wishlist successfully',
            ]);
        }
        return response()->json([
            'status'  => 'error',
            'message' => 'Already in wishlist',
        ]);
    }

    public function removeWishlist(Request $request) {
        if (!auth()->check()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You must be login to add an item to wishlist',
            ]);
        }

        $column   = $request->type . '_id';
        $wishlist = Wishlist::where($column, $request->id)->where('user_id', auth()->id())->first();
        if (!$wishlist) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid Request',
            ]);
        }
        $wishlist->delete();
        return response()->json([
            'status'  => 'success',
            'message' => 'Video removed from wishlist successfully',
        ]);
    }

    public function addClick(Request $request) {
        $ad = Advertise::find($request->id);
        $ad->increment('click');
        return response()->json("Success");
    }

    public function storeDeviceToken(Request $request) {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()->all()];
        }

        $deviceToken = DeviceToken::where('token', $request->token)->first();

        if ($deviceToken) {
            return ['success' => true, 'message' => 'Already exists'];
        }

        $deviceToken          = new DeviceToken();
        $deviceToken->user_id = auth()->user()->id;
        $deviceToken->token   = $request->token;
        $deviceToken->is_app  = 0;
        $deviceToken->save();

        return ['success' => true, 'message' => 'Token save successfully'];
    }

    public function pusher($socketId, $channelName) {
        $general      = gs();
        $pusherSecret = $general->pusher_config->app_secret_key;
        $str          = $socketId . ":" . $channelName;
        $hash         = hash_hmac('sha256', $str, $pusherSecret);

        return response()->json([
            'success' => true,
            'message' => "Pusher authentication successfully",
            'auth'    => $general->pusher_config->app_key . ":" . $hash,
        ]);
    }

    public function subscription() {
        $pageTitle = 'Subscribe';
        $plans     = Plan::active()->paginate(getPaginate());
        return view('Template::subscription', compact('pageTitle', 'plans'));
    }

    public function liveTournaments() {
        $pageTitle   = 'Live Tournaments';
        $tournaments = Tournament::active()->paginate(getPaginate());
        return view('Template::tournaments.index', compact('pageTitle', 'tournaments'));
    }

    public function tournamentGames($id, $slug) {
        $tournament = Tournament::active()->with(['games' => function ($query) {
            $query->active()->orderBy('start_time', 'asc');
        }])->findOrFail($id);
        $pageTitle = $tournament->name;

        $games = $tournament->games->groupBy(function ($game) {
            return Carbon::parse($game->start_time)->format('Y-m-d');
        });

        return view('Template::tournaments.games', compact('pageTitle', 'tournament', 'games'));

    }

    public function tournamentDetail($id, $slug) {
        $tournament = Tournament::active()->with(['games' => function ($query) {
            $query->active()->orderBy('start_time', 'asc');
        }])->findOrFail($id);
        $pageTitle = $tournament->name;
        $games     = $tournament->games->groupBy(function ($game) {
            return Carbon::parse($game->start_time)->format('Y-m-d');
        });
        $watchEligable = $this->checkWatchEligableTournament($tournament);
        return view('Template::tournaments.detail', compact('pageTitle', 'tournament', 'games', 'watchEligable'));
    }

    protected function checkWatchEligableTournament($tournament) {
        $watchEligable = true;
        if ($tournament->version == Status::PAID_VERSION) {
            $watchEligable = false;
            if (auth()->check()) {
                $watchEligable = Subscription::active()->where('user_id', auth()->id())->where('tournament_id', $tournament->id)->exists();
            }
        }
        return $watchEligable;
    }

    public function gameDetail($id, $slug) {
        $game          = Game::active()->with('tournament', 'teamOne', 'teamTwo')->findOrFail($id);
        $relatedGames  = Game::active()->where('id', '!=', $game->id)->where('tournament_id', $game->tournament_id)->orderBy('start_time', 'asc')->get();
        $pageTitle     = $game->slug;
        $watchEligable = $this->checkWatchEligableGame($game);
        return view('Template::games.detail', compact('pageTitle', 'game', 'relatedGames', 'watchEligable'));
    }

    public function watchGame($id, $slug) {
        $pageTitle     = 'Watch Game';
        $game          = Game::active()->with('tournament', 'teamOne', 'teamTwo')->findOrFail($id);
        $watchEligable = $this->checkWatchEligableGame($game);
        if (!$watchEligable) {
            $notify[] = ['error', 'Please purchase a subscription for this game'];
            return to_route('subscription')->withNotify($notify);
        }
        return view('Template::games.watch', compact('pageTitle', 'game'));
    }

    protected function checkWatchEligableGame($game) {
        $watchEligable = true;
        if ($game->tournament->version == Status::FREE_VERSION) {
            return $watchEligable;
        }
        if ($game->version == Status::PAID_VERSION) {
            $watchEligable = false;
            if (auth()->check()) {
                $tournamentId  = $game->tournament_id;
                $gameId        = $game->id;
                $watchEligable = Subscription::active()->where('user_id', auth()->id())->where(function ($query) use ($gameId, $tournamentId) {
                    $query->where('game_id', $gameId)->orWhere('tournament_id', $tournamentId);
                })->exists();
            }
        }
        return $watchEligable;
    }

    public function shortVideos($id = 0, $route = null) {
        $pageTitle = 'Short Videos';
        $reels     = Reel::query();
        if (request()->lastId) {
            $reels = $reels->where('id', '<', request()->lastId);
        }

        if ($id) {
            $firstReel = clone $reels;
            $firstReel = $firstReel->where('id', $id)->firstOrFail();
            $reels     = clone $reels;
            $reels     = $reels->where('id', '!=', $firstReel->id)->inRandomOrder()->take(9)->get();
            $reels->prepend($firstReel);
        } else {
            if ($route == 'favorite') {
                $reelId = ReelHistory::where('user_id', auth()->id())->where('list', Status::YES)->pluck('reel_id')->toArray();
                $reels  = $reels->whereIn('id', $reelId);
            }
            $reels = $reels->inRandomOrder()->take(10)->get();
        }

        $userLikesId   = [];
        $userUnLikesId = [];
        $userListId    = [];
        if (auth()->check()) {
            $userReact     = ReelHistory::where('user_id', auth()->id())->get();
            $userLikesId   = $userReact->where('likes', Status::YES)->pluck('reel_id')->toArray();
            $userUnLikesId = $userReact->where('unlikes', Status::YES)->pluck('reel_id')->toArray();
            $userListId    = $userReact->where('list', Status::YES)->pluck('reel_id')->toArray();
        }
        $lastId = @$reels->last()->id;
        if (request()->lastId) {
            if ($reels->count()) {
                $data = view('Template::partials.reels', compact('reels', 'userLikesId', 'userUnLikesId', 'userListId'))->render();
                return response()->json([
                    'data'          => $data,
                    'lastId'        => $lastId,
                    'userLikesId'   => $userLikesId,
                    'userUnLikesId' => $userUnLikesId,
                    'userListId'    => $userListId,
                ]);
            }
            return response()->json([
                'error' => 'Item not more yet',
            ]);
        }
        return view('Template::short_videos', compact('pageTitle', 'reels', 'userLikesId', 'userUnLikesId', 'userListId', 'lastId'));
    }
}
