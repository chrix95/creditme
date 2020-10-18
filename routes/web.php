<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'GeneralController@index')->name('guest.home');

Route::get('/about-us', 'GeneralController@about')->name('about');

Route::get('/terms', 'GeneralController@terms')->name('terms');

Route::get('/our-services', 'GeneralController@services')->name('services');

Route::post('/subscribe/submit', 'GeneralController@subscribeSubmit')->name('subscribe.submit');

Route::get('/contact', 'GeneralController@contact')->name('contact');

Route::post('/contact', 'GeneralController@contactSubmit')->name('contact.submit');

Auth::routes();

Route::redirect('/home', '/', 301);

Route::get('/profile', 'HomeController@profile')->name('profile');

Route::get('/voucher', 'HomeController@voucher')->name('voucher')->middleware('validate.admin');

Route::get('data-bundles', 'Cronjob\UpdateBundles@loadBundle');

Route::get('tv-bundles', 'Cronjob\UpdateBundles@loadTVBundle');