<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MainController;
use App\Http\Controllers\Api\Order\CartController;
use App\Http\Controllers\Api\Order\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\User\ClientAuthController;
use App\Http\Controllers\Api\User\UserController;
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

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::post('login', [ClientAuthController::class, 'login']);
Route::post('new-user', [ClientAuthController::class, 'register']);
Route::post('social-login', [UserController::class, 'social_register']);
Route::get('social-login-status', [UserController::class, 'socialStatus']);
Route::post('reset-password', [UserController::class, 'resetPassword']);
Route::post('forget-password', [UserController::class, 'forgetPassword']);
Route::post('forget-password-confirm', [UserController::class, 'forgetPasswordConfirm']);
Route::post('users/activate', [UserController::class, 'activateUser']);
Route::get('cities', [AddressController::class, 'cities']);
Route::get('currencies', [MainController::class, 'currencies']);
Route::get('attributes', [MainController::class, 'attributes']);
Route::get('about', [MainController::class, 'about']);
Route::post('contact', [MainController::class, 'contact']);
Route::get('product-reviews/{id}',[ReviewController::class ,'productReviews']);

Route::get('branches', [MainController::class, 'branches']);

//PROFILE
Route::middleware('auth:sanctum')->group(function (){
    Route::get('profile', [UserController::class, 'profile']);
    Route::post('profile', [UserController::class, 'profilePost']);
    Route::post('user/delete', [UserController::class, 'requestDeleteUser']);
    Route::post('user/delete/otp', [UserController::class, 'deleteUser']);
    Route::post('checkout', [CartController::class, 'store']);

    Route::get('addresses', [AddressController::class, 'index']);
    Route::post('address', [AddressController::class, 'store']);
    Route::post('address/{address}', [AddressController::class, 'update']);
    Route::get('address-delete', [AddressController::class, 'destroy']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('order/{id}', [OrderController::class, 'order']);

    Route::post('review', [ReviewController::class, 'review']);
    Route::get('reviews', [ReviewController::class, 'reviews']);

    Route::get('favorites', [FavoriteController::class, 'getFavorite']);
    Route::post('favorites', [FavoriteController::class, 'addToFavorite']);


});

Route::get('categories', [CategoryController::class, 'index']);
Route::get('products', [ProductController::class, 'index']);
Route::get('banners', [CategoryController::class, 'banners']);
Route::any('payment/tap/redirect', [PaymentController::class, 'redirectUrl']);
Route::any('payment/tap/webhook', [PaymentController::class, 'webhookUrl']);
Route::any('app/status', [PaymentController::class, 'appStatus']);
Route::get('commands/apply', [PaymentController::class, 'commands']);
