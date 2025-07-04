<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Frontend;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;

class GeneralSettingController extends Controller {
    public function systemSetting() {
        $pageTitle = 'System Settings';
        $settings  = json_decode(file_get_contents(resource_path('views/admin/setting/settings.json')));
        return view('admin.setting.system', compact('pageTitle', 'settings'));
    }
    public function general() {
        $pageTitle       = 'General Setting';
        $timezones       = timezone_identifiers_list();
        $currentTimezone = array_search(config('app.timezone'), $timezones);
        return view('admin.setting.general', compact('pageTitle', 'timezones', 'currentTimezone'));
    }

    public function generalUpdate(Request $request) {
        $request->validate([
            'site_name'       => 'required|string|max:40',
            'cur_text'        => 'required|string|max:40',
            'cur_sym'         => 'required|string|max:40',
            'base_color'      => 'nullable|regex:/^[a-f0-9]{6}$/i',
            'secondary_color' => 'nullable|regex:/^[a-f0-9]{6}$/i',
            'timezone'        => 'required|integer',
            'currency_format' => 'required|in:1,2,3',
            'paginate_number' => 'required|integer',
            'tmdb_api'        => 'nullable|string|max:255',
            'skip_time'       => 'required|integer|max:60',
            'file_server'     => 'required|in:current,custom-ftp,wasabi,digital_ocean',
        ]);

        $timezones = timezone_identifiers_list();
        $timezone  = @$timezones[$request->timezone] ?? 'UTC';

        $general                  = gs();
        $general->site_name       = $request->site_name;
        $general->cur_text        = $request->cur_text;
        $general->cur_sym         = $request->cur_sym;
        $general->paginate_number = $request->paginate_number;
        $general->base_color      = str_replace('#', '', $request->base_color);
        $general->secondary_color = str_replace('#', '', $request->secondary_color);
        $general->currency_format = $request->currency_format;
        $general->server          = $request->file_server;
        $general->tmdb_api        = $request->tmdb_api;
        $general->skip_time       = $request->skip_time;
        $pusherConfiguration      = [
            'app_id'         => $request->app_id,
            'app_key'        => $request->app_key,
            'app_secret_key' => $request->app_secret_key,
            'cluster'        => $request->cluster,
        ];
        $general->pusher_config = $pusherConfiguration;
        $general->save();

        $timezoneFile = config_path('timezone.php');
        $content      = '<?php $timezone = "' . $timezone . '" ?>';
        file_put_contents($timezoneFile, $content);
        $notify[] = ['success', 'General setting updated successfully'];
        return back()->withNotify($notify);
    }

    public function systemConfiguration() {
        $pageTitle = 'System Configuration';
        return view('admin.setting.configuration', compact('pageTitle'));
    }

    public function systemConfigurationSubmit(Request $request) {
        $general                    = gs();
        $general->ev                = $request->ev ? Status::ENABLE : Status::DISABLE;
        $general->en                = $request->en ? Status::ENABLE : Status::DISABLE;
        $general->sv                = $request->sv ? Status::ENABLE : Status::DISABLE;
        $general->sn                = $request->sn ? Status::ENABLE : Status::DISABLE;
        $general->pn                = $request->pn ? Status::ENABLE : Status::DISABLE;
        $general->force_ssl         = $request->force_ssl ? Status::ENABLE : Status::DISABLE;
        $general->secure_password   = $request->secure_password ? Status::ENABLE : Status::DISABLE;
        $general->registration      = $request->registration ? Status::ENABLE : Status::DISABLE;
        $general->agree             = $request->agree ? Status::ENABLE : Status::DISABLE;
        $general->multi_language    = $request->multi_language ? Status::ENABLE : Status::DISABLE;
        $general->ad_show_mobile    = $request->ad_show_mobile ? Status::ENABLE : Status::DISABLE;
        $general->device_limit      = $request->device_limit ? Status::ENABLE : Status::DISABLE;
        $general->watch_party       = $request->watch_party ? Status::ENABLE : Status::DISABLE;
        $general->watch_party_users = $request->watch_party_users ? Status::ENABLE : Status::DISABLE;
        $general->in_app_payment    = $request->in_app_payment ? Status::ENABLE : Status::DISABLE;
        $general->save();
        $notify[] = ['success', 'System configuration updated successfully'];
        return back()->withNotify($notify);
    }

    public function logoIcon() {
        $pageTitle = 'Logo & Favicon';
        return view('admin.setting.logo_icon', compact('pageTitle'));
    }

    public function logoIconUpdate(Request $request) {
        $request->validate([
            'logo'    => ['image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'favicon' => ['image', new FileTypeValidate(['png'])],
        ]);
        $path = getFilePath('logoIcon');
        if ($request->hasFile('logo')) {
            try {
                fileUploader($request->logo, $path, filename: 'logo.png');
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload the logo'];
                return back()->withNotify($notify);
            }
        }

        if ($request->hasFile('favicon')) {
            try {
                fileUploader($request->favicon, $path, filename: 'favicon.png');
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload the favicon'];
                return back()->withNotify($notify);
            }
        }
        $notify[] = ['success', 'Logo & favicon updated successfully'];
        return back()->withNotify($notify);
    }

    public function customCss() {
        $pageTitle   = 'Custom CSS';
        $file        = activeTemplate(true) . 'css/custom.css';
        $fileContent = @file_get_contents($file);
        return view('admin.setting.custom_css', compact('pageTitle', 'fileContent'));
    }

    public function sitemap() {
        $pageTitle   = 'Sitemap XML';
        $file        = 'sitemap.xml';
        $fileContent = @file_get_contents($file);
        return view('admin.setting.sitemap', compact('pageTitle', 'fileContent'));
    }

    public function sitemapSubmit(Request $request) {
        $file = 'sitemap.xml';
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        file_put_contents($file, $request->sitemap);
        $notify[] = ['success', 'Sitemap updated successfully'];
        return back()->withNotify($notify);
    }

    public function robot() {
        $pageTitle   = 'Robots TXT';
        $file        = 'robots.xml';
        $fileContent = @file_get_contents($file);
        return view('admin.setting.robots', compact('pageTitle', 'fileContent'));
    }

    public function robotSubmit(Request $request) {
        $file = 'robots.xml';
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        file_put_contents($file, $request->robots);
        $notify[] = ['success', 'Robots txt updated successfully'];
        return back()->withNotify($notify);
    }

    public function customCssSubmit(Request $request) {
        $file = activeTemplate(true) . 'css/custom.css';
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        file_put_contents($file, $request->css);
        $notify[] = ['success', 'CSS updated successfully'];
        return back()->withNotify($notify);
    }

    public function maintenanceMode() {
        $pageTitle   = 'Maintenance Mode';
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->firstOrFail();
        return view('admin.setting.maintenance', compact('pageTitle', 'maintenance'));
    }

    public function maintenanceModeSubmit(Request $request) {
        $request->validate([
            'description' => 'required',
            'image'       => ['nullable', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);
        $general                   = gs();
        $general->maintenance_mode = $request->status ? Status::ENABLE : Status::DISABLE;
        $general->save();

        $maintenance = Frontend::where('data_keys', 'maintenance.data')->firstOrFail();
        $image       = @$maintenance->data_values->image;
        if ($request->hasFile('image')) {
            try {
                $old   = $image;
                $image = fileUploader($request->image, getFilePath('maintenance'), getFileSize('maintenance'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $maintenance->data_values = [
            'description' => $request->description,
            'image'       => $image,
        ];
        $maintenance->save();

        $notify[] = ['success', 'Maintenance mode updated successfully'];
        return back()->withNotify($notify);
    }

    public function cookie() {
        $pageTitle = 'GDPR Cookie';
        $cookie    = Frontend::where('data_keys', 'cookie.data')->firstOrFail();
        return view('admin.setting.cookie', compact('pageTitle', 'cookie'));
    }

    public function cookieSubmit(Request $request) {
        $request->validate([
            'short_desc'  => 'required|string|max:255',
            'description' => 'required',
        ]);
        $cookie              = Frontend::where('data_keys', 'cookie.data')->firstOrFail();
        $cookie->data_values = [
            'short_desc'  => $request->short_desc,
            'description' => $request->description,
            'status'      => $request->status ? Status::ENABLE : Status::DISABLE,
        ];
        $cookie->save();
        $notify[] = ['success', 'Cookie policy updated successfully'];
        return back()->withNotify($notify);
    }

    public function socialiteCredentials() {
        $pageTitle = 'Social Login Credentials';
        return view('admin.setting.social_credential', compact('pageTitle'));
    }

    public function updateSocialiteCredentialStatus($key) {
        $general     = gs();
        $credentials = $general->socialite_credentials;
        try {
            $credentials->$key->status = $credentials->$key->status == Status::ENABLE ? Status::DISABLE : Status::ENABLE;
        } catch (\Throwable $th) {
            abort(404);
        }

        $general->socialite_credentials = $credentials;
        $general->save();

        $notify[] = ['success', 'Status changed successfully'];
        return back()->withNotify($notify);
    }

    public function updateSocialiteCredential(Request $request, $key) {
        $general     = gs();
        $credentials = $general->socialite_credentials;
        try {
            @$credentials->$key->client_id     = $request->client_id;
            @$credentials->$key->client_secret = $request->client_secret;
        } catch (\Throwable $th) {
            abort(404);
        }
        $general->socialite_credentials = $credentials;
        $general->save();

        $notify[] = ['success', ucfirst($key) . ' credential updated successfully'];
        return back()->withNotify($notify);
    }

    public function inAppPurchase() {
        $pageTitle  = 'In App Purchase Configuration - Google Play Store';
        $data       = null;
        $fileExists = file_exists(getFilePath('appPurchase') . '/google_pay.json');
        return view('admin.setting.in_app_purchase.google', compact('pageTitle', 'data', 'fileExists'));
    }

    public function inAppPurchaseConfigure(Request $request) {
        $request->validate([
            'file' => ['required', new FileTypeValidate(['json'])],
        ]);

        try {
            fileUploader($request->file, getFilePath('appPurchase'), filename: 'google_pay.json');
        } catch (\Exception $exp) {
            $notify[] = ['error', 'Couldn\'t upload your file'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Configuration file uploaded successfully'];
        return back()->withNotify($notify);
    }

    public function inAppPurchaseFileDownload() {
        $filePath = getFilePath('appPurchase') . '/google_pay.json';
        if (!file_exists(getFilePath('appPurchase') . '/google_pay.json')) {
            $notify[] = ['success', "File not found"];
            return back()->withNotify($notify);
        }
        return response()->download($filePath);
    }
}
