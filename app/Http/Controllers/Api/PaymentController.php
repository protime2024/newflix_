<?php

namespace App\Http\Controllers\Api;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController as GatewayPaymentController;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller {
    public function methods() {
        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('method_code')->get();
        $notify[] = 'Payment Methods';
        return response()->json([
            'remark'  => 'deposit_methods',
            'message' => ['success' => $notify],
            'data'    => [
                'methods'    => $gatewayCurrency,
                'image_path' => getFilePath('gateway'),
            ],
        ]);
    }

    public function depositInsert(Request $request) {
        $validator = Validator::make($request->all(), [
            'method_code'     => 'required',
            'currency'        => 'required',
            'subscription_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = auth()->user();

        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->where('method_code', $request->method_code)->where('currency', $request->currency)->first();

        if (!$gate) {
            $notify[] = 'Invalid gateway';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $subscription = Subscription::inactive()->where('id', $request->subscription_id)->first();
        if (!$subscription) {
            $notify[] = 'Oops! Subscription not found';
            return response()->json([
                'remark'  => 'subscription_not_found',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $amount = @$subscription->subscriptionPrice;

        if ($gate->min_amount > $amount || $gate->max_amount < $amount) {
            $notify[] = 'Please follow deposit limit';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        $charge      = $gate->fixed_charge + ($amount * $gate->percent_charge / 100);
        $payable     = $amount + $charge;
        $finalAmount = $payable * $gate->rate;

        $data                  = new Deposit();
        $data->from_api        = 1;
        $data->user_id         = $user->id;
        $data->subscription_id = $request->subscription_id;
        $data->method_code     = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount          = $amount;
        $data->charge          = $charge;
        $data->rate            = $gate->rate;
        $data->final_amount    = $finalAmount;
        $data->btc_amount      = 0;
        $data->btc_wallet      = "";
        $data->success_url     = urlPath('user.deposit.history');
        $data->failed_url      = urlPath('user.deposit.history');
        $data->trx             = getTrx();
        $data->save();

        $notify[] = 'Payment inserted';
        return response()->json([
            'remark'  => 'deposit_inserted',
            'status'  => 'success',
            'message' => ['success' => $notify],
            'data'    => [
                'deposit'      => $data,
                'redirect_url' => route('deposit.app.confirm', encrypt($data->id)),
            ],
        ]);
    }

    public function appPaymentConfirm(Request $request) {
        if (!gs('in_app_payment')) {
            $notify[] = 'In app purchase feature currently disable';
            return response()->json([
                'remark'  => 'feature_disable',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        $validator = Validator::make($request->all(), [
            'method_code'    => 'required|in:5001',
            'amount'         => 'required|numeric|gt:0',
            'currency'       => 'required|string',
            'purchase_token' => 'required',
            'package_name'   => 'required',
            'plan_id'        => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $user = auth()->user();

        $deposit = Deposit::where('status', Status::PAYMENT_SUCCESS)->where('btc_wallet', $request->purchase_token)->exists();
        if ($deposit) {
            $notify[] = 'Payment already captured';
            return response()->json([
                'remark'  => 'payment_captured',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if (!file_exists(getFilePath('appPurchase') . '/google_pay.json')) {
            $notify[] = 'Configuration file missing';
            return response()->json([
                'remark'  => 'configuration_missing',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
        $configuration = getFilePath('appPurchase') . '/google_pay.json';
        $client        = new \Google_Client();
        $client->setAuthConfig($configuration);
        $client->setScopes([\Google_Service_AndroidPublisher::ANDROIDPUBLISHER]);
        $service = new \Google_Service_AndroidPublisher($client);

        $packageName   = $request->package_name;
        $productId     = $request->plan_id;
        $purchaseToken = $request->purchase_token;
        try {
            $response = $service->purchases_products->get($packageName, $productId, $purchaseToken);
        } catch (\Exception $e) {
            $errorMessage                 = @json_decode($e->getMessage())->error->message;
            $adminNotification            = new AdminNotification();
            $adminNotification->user_id   = $user->id;
            $adminNotification->title     = 'In App Purchase Error: ' . $errorMessage;
            $adminNotification->click_url = '#';
            $adminNotification->save();

            $notify[] = 'Something went wrong';
            return response()->json([
                'remark'  => 'invalid_purchase',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        if ($response->getPurchaseState() != 0) {
            $notify[] = 'Invalid purchase';
            return response()->json([
                'remark'  => 'invalid_purchase',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }

        //the amount should be your product amount
        $amount = 10;
        $rate   = $request->amount / $amount;

        $data                  = new Deposit();
        $data->user_id         = $user->id;
        $data->method_code     = $request->method_code;
        $data->method_currency = $request->currency;
        $data->amount          = $amount;
        $data->charge          = 0;
        $data->rate            = $rate;
        $data->final_amount    = $request->amount;
        $data->btc_amount      = 0;
        $data->btc_wallet      = $request->purchase_token;
        $data->trx             = getTrx();
        $data->save();

        GatewayPaymentController::userDataUpdate($data);

        $notify[] = 'Payment confirmed successfully';
        return response()->json([
            'remark'  => 'payment_confirm',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }
}
