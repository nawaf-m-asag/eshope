<?php

Route::group(['namespace' => 'Botble\Marketplace\Http\Controllers', 'middleware' => ['web', 'core']], function () {

    Route::group(['prefix' => BaseHelper::getAdminPrefix(), 'middleware' => 'auth'], function () {

        Route::group(['prefix' => 'marketplaces', 'as' => 'marketplace.'], function () {
            Route::group(['prefix' => 'stores', 'as' => 'store.'], function () {
                Route::resource('', 'StoreController')->parameters(['' => 'store']);
                Route::delete('items/destroy', [
                    'as'         => 'deletes',
                    'uses'       => 'StoreController@deletes',
                    'permission' => 'marketplace.store.destroy',
                ]);
            });
        });

    });

});
