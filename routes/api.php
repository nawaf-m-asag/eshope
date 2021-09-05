<?php

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



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ProductController;


Route::group(['middleware' => ['jwt.verify']], function() {
  
});



Route::post('get_products',App\Http\Controllers\API\ProductController::class . '@getProduct');
Route::post('get_favorites',App\Http\Controllers\API\Wish_listsController::class . '@_getFav');
Route::post('set_product_rating',App\Http\Controllers\API\ReviewsController::class . '@setRating');
Route::post('delete_product_rating',App\Http\Controllers\API\ReviewsController::class . '@delete_product_rating');
Route::post('get_slider_images',App\Http\Controllers\API\Simple_slidersController::class . '@getSlider');
Route::post('get_sections',App\Http\Controllers\API\CollectionsController::class . '@getSection');
Route::post('get_offer_images',App\Http\Controllers\API\AdsController::class . '@getOfferImages');
Route::post('manage_cart',App\Http\Controllers\API\CartController::class . '@addToCart');
Route::post('remove_from_cart',App\Http\Controllers\API\CartController::class . '@removeFromCart');
Route::post('get_user_cart',App\Http\Controllers\API\CartController::class . '@_getCart');
Route::post('place_order',App\Http\Controllers\API\OrderController::class . '@placeOrder');
Route::post('get_cities',App\Http\Controllers\API\CitiesController::class . '@getCities');
Route::post('get_areas_by_city_id',App\Http\Controllers\API\AreasController::class . '@getArea');
Route::post('add_address',App\Http\Controllers\API\AddressController::class . '@addNewAddress');
Route::post('delete_address',App\Http\Controllers\API\AddressController::class . '@deleteAddress');
Route::post('get_faqs',App\Http\Controllers\API\FaqsController::class . '@getFaqs');
Route::post('get_categories',App\Http\Controllers\API\CategoryController::class . '@getCat');
Route::post('get_product_rating',App\Http\Controllers\API\ReviewsController::class . '@getReview');

Route::post('add_to_favorites',App\Http\Controllers\API\Wish_listsController::class . '@_setFav');
Route::post('remove_from_favorites',App\Http\Controllers\API\Wish_listsController::class . '@_removeFav');
    //user
    Route::post('verify_user',App\Http\Controllers\API\CustomerController::class . '@getVerifyUser');
    Route::post('register_user',App\Http\Controllers\API\CustomerController::class . '@getRegisterUser');
    Route::post('update_user',App\Http\Controllers\API\CustomerController::class . '@update_user');
    Route::post('reset_password',App\Http\Controllers\API\CustomerController::class . '@reset_password');
    //get_notifications
    Route::post('get_notifications',App\Http\Controllers\API\NotificationsController::class . '@getNotification');

Route::post('login',App\Http\Controllers\API\CustomerController::class . '@getLoginUser');
Route::post('get_setting',App\Http\Controllers\API\SettingsController::class . '@getSetting');




   
    
   


    
