<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function() {
    // data and tv bundles updating
    Route::post('update', function(Request $request) {
        foreach($request->bundles as $bundle) {
            $data = array(
                'name'  => $bundle['title'],
                'amount'  => $bundle['price'],
                'code'  => $bundle['code'],
                'service_id'  => 9,
                'date_created'  => now(),
                'date_modified'  => now(),
            );
            \App\DataBundle::create($data);
        }
        return "Done";
    });
    // Entry route for all request
    Route::prefix('entry')->group(function() {
        Route::prefix('airtime')->group(function() {
            Route::post('verify', [
                'uses'  =>  'EntryController@airtimeEntry',
                'as'    =>  'airtime.entry.register'
            ]);
            Route::post('vend', [
                'uses'  =>  'EntryController@airtimeVend',
                'as'    =>  'airtime.entry.vend'
            ]);
            Route::get('process-airtime-transactions', 'Cronjob\ServicesVendController@processAirtimeTransactions');
        });
        Route::prefix('data')->group(function() {
            Route::get('bundles/{networkID}', [
                'uses'  =>  'EntryController@getBundles',
                'as'    =>  'data.entry.bundles'
            ]);
        });
    });
    // hashing testing
    Route::get('/hash/{apistring}', 'UtilityController@verifyAPIPasscode');
    // airtime request
    Route::prefix('airtime')->group(function() {
        Route::post('verify', [
            'uses'  =>  'Apis\AirtimeController@registerAttempt',
            'as'    =>  'airtime.register'
        ]);
        Route::post('vend', [
            'uses'  =>  'Apis\AirtimeController@request',
            'as'    =>  'airtime.request'
        ]);
    });
    // power request
    Route::prefix('power')->group(function() {
        Route::get('states', [
            'uses'  =>  'Apis\PowerController@index',
            'as'    =>  'power.states'
        ]);
        Route::post('verify', [
            'uses'  =>  'Apis\PowerController@getMeterInfo',
            'as'    =>  'power.verify'
        ]);
        Route::post('vend', [
            'uses'  =>  'Apis\PowerController@request',
            'as'    =>  'power.request'
        ]);
    });
    // tv request
    Route::prefix('tv')->group(function() {
        Route::get('get-tv-info/{providerID}', [
            'uses'  =>  'Apis\TVController@getTVInfo',
            'as'    =>  'tv.get-tv-info'
        ]);
        Route::post('verify', [
            'uses'  =>  'Apis\TVController@getCardInfo',
            'as'    =>  'tv.verify'
        ]);
        Route::post('vend', [
            'uses'  =>  'Apis\TVController@request',
            'as'    =>  'tv.request'
        ]);
    });
    // data request
    Route::prefix('data')->group(function() {
        Route::post('verify', [
            'uses'  =>  'Apis\DataController@registerAttempt',
            'as'    =>  'data.register'
        ]);
        Route::get('bundles/{networkID}', [
            'uses'  =>  'Apis\DataController@getBundles',
            'as'    =>  'data.bundles.get'
        ]);
        Route::post('vend', [
            'uses'  =>  'Apis\DataController@request',
            'as'    =>  'data.request'
        ]);
    });
    Route::prefix('users')->group(function () {
        Route::post('register', 'Apis\UserController@registerUser');
        Route::post('signin', 'Apis\UserController@signinUser');
        Route::post('fund_user_wallet', 'Apis\UserController@fund_user_wallet');
        Route::post('create_voucher', 'Apis\UserController@create_voucher');
        Route::delete('delete_voucher/{id}', 'Apis\UserController@delete_voucher');
    });
});
