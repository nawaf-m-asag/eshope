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





    Route::post('get_categories',App\Http\Controllers\API\CategoryController::class . '@getCat');
    Route::post('get_product_rating',App\Http\Controllers\API\ReviewsController::class . '@getReview');
    Route::post('get_faqs',App\Http\Controllers\API\FaqsController::class . '@getFaqs');
    Route::post('add_to_favorites',App\Http\Controllers\API\Wish_listsController::class . '@_setFav');
    Route::post('remove_from_favorites',App\Http\Controllers\API\Wish_listsController::class . '@_removeFav');
    Route::post('get_products',App\Http\Controllers\API\ProductController::class . '@getProduct');
    Route::post('get_favorites',App\Http\Controllers\API\Wish_listsController::class . '@_getFav');
    Route::post('set_product_rating',App\Http\Controllers\API\ReviewsController::class . '@setRating');
    Route::post('delete_product_rating',App\Http\Controllers\API\ReviewsController::class . '@delete_product_rating');



    
