<?php

Route::group([
    'namespace'  => 'Botble\Marketplace\Http\Controllers\Fronts',
    'middleware' => ['web', 'core'],
], function () {
    Route::group(['prefix' => 'vendor', 'as' => 'marketplace.vendor.', 'middleware' => ['vendor']], function () {
        Route::group(['prefix' => 'products', 'as' => 'products.'], function () {
            Route::resource('', 'ProductController')
                ->parameters(['' => 'product']);

            Route::delete('items/destroy', [
                'as'   => 'deletes',
                'uses' => 'ProductController@deletes',
            ]);

            Route::post('add-attribute-to-product/{id}', [
                'as'   => 'add-attribute-to-product',
                'uses' => 'ProductController@postAddAttributeToProduct',
            ]);

            Route::post('delete-version/{id}', [
                'as'   => 'delete-version',
                'uses' => 'ProductController@postDeleteVersion',
            ]);

            Route::post('add-version/{id}', [
                'as'   => 'add-version',
                'uses' => 'ProductController@postAddVersion',
            ]);

            Route::get('get-version-form/{id?}', [
                'as'   => 'get-version-form',
                'uses' => 'ProductController@getVersionForm',
            ]);

            Route::post('update-version/{id}', [
                'as'   => 'update-version',
                'uses' => 'ProductController@postUpdateVersion',
            ]);

            Route::post('generate-all-version/{id}', [
                'as'   => 'generate-all-versions',
                'uses' => 'ProductController@postGenerateAllVersions',
            ]);

            Route::post('store-related-attributes/{id}', [
                'as'   => 'store-related-attributes',
                'uses' => 'ProductController@postStoreRelatedAttributes',
            ]);

            Route::post('save-all-version/{id}', [
                'as'   => 'save-all-versions',
                'uses' => 'ProductController@postSaveAllVersions',
            ]);

            Route::get('get-list-product-for-search/{id?}', [
                'as'   => 'get-list-product-for-search',
                'uses' => 'ProductController@getListProductForSearch',
            ]);

            Route::get('get-relations-box/{id?}', [
                'as'   => 'get-relations-boxes',
                'uses' => 'ProductController@getRelationBoxes',
            ]);

            Route::get('get-list-products-for-select', [
                'as'   => 'get-list-products-for-select',
                'uses' => 'ProductController@getListProductForSelect',
            ]);

            Route::post('create-product-when-creating-order', [
                'as'   => 'create-product-when-creating-order',
                'uses' => 'ProductController@postCreateProductWhenCreatingOrder',
            ]);

            Route::get('get-all-products-and-variations', [
                'as'   => 'get-all-products-and-variations',
                'uses' => 'ProductController@getAllProductAndVariations',
            ]);

            Route::post('update-order-by', [
                'as'   => 'update-order-by',
                'uses' => 'ProductController@postUpdateOrderby',
            ]);
        });

        Route::get('tags/all', [
            'as'   => 'tags.all',
            'uses' => 'ProductTagController@getAllTags',
        ]);
    });
});


Route::group([
    'namespace'  => 'Botble\Ecommerce\Http\Controllers',
    'middleware' => ['web', 'core'],
], function () {
    Route::group(['prefix' => 'vendor', 'as' => 'marketplace.vendor.', 'middleware' => ['vendor']], function () {
        Route::get('tags/all', [
            'as'   => 'tags.all',
            'uses' => 'ProductTagController@getAllTags',
        ]);
    });
});

