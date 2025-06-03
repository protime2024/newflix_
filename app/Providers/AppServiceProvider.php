<?php

namespace App\Providers;

use App\Constants\Status;
use App\Lib\Searchable;
use App\Models\AdminNotification;
use App\Models\Category;
use App\Models\Deposit;
use App\Models\Frontend;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\App;
class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        Builder::mixin(new Searchable);
    }

    /**
     * Bootstrap any application services.
     */


public function boot(): void {
    $viewShare['emptyMessage'] = 'Data not found';

    if (App::runningInConsole() || !Schema::hasTable('categories')) {
        // Avoid running DB queries during artisan commands like package:discover
        view()->share($viewShare);
        return;
    }

    $viewShare['categories'] = Category::active()->with([
        'subcategories' => function ($subcategory) {
            $subcategory->active();
        },
    ])->get(['name', 'id']);

    view()->share($viewShare);

    view()->composer('admin.partials.sidenav', function ($view) {
        $view->with([
            'bannedUsersCount'           => User::banned()->count(),
            'emailUnverifiedUsersCount'  => User::emailUnverified()->count(),
            'mobileUnverifiedUsersCount' => User::mobileUnverified()->count(),
            'pendingTicketCount'         => SupportTicket::whereIN([Status::TICKET_OPEN, Status::TICKET_REPLY])->count(),
            'pendingDepositsCount'       => Deposit::pending()->count(),
            'updateAvailable'            => version_compare(gs('available_version'), systemDetails()['version'], '>') ? 'v' . gs('available_version') : false,
        ]);
    });

    view()->composer('admin.partials.topnav', function ($view) {
        $view->with([
            'adminNotifications'     => AdminNotification::where('is_read', Status::NO)->with('user')->orderBy('id', 'desc')->take(10)->get(),
            'adminNotificationCount' => AdminNotification::where('is_read', Status::NO)->count(),
        ]);
    });

    view()->composer('partials.seo', function ($view) {
        $seo = Frontend::where('data_keys', 'seo.data')->first();
        $view->with([
            'seo' => $seo ? $seo->data_values : $seo,
        ]);
    });

    if (gs('force_ssl')) {
        \URL::forceScheme('https');
    }

    Paginator::useBootstrapFive();
}

}
