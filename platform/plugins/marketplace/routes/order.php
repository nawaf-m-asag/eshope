<?php

Route::group([
    'namespace'  => 'Botble\Marketplace\Http\Controllers\Fronts',
    'middleware' => ['web', 'core'],
], function () {
    Route::group(['prefix' => 'vendor', 'as' => 'marketplace.vendor.', 'middleware' => ['vendor']], function () {
        Route::group(['prefix' => 'orders', 'as' => 'orders.'], function () {
            Route::resource('', 'OrderController')->parameters(['' => 'order'])->except(['create', 'store']);

            Route::delete('items/destroy', [
                'as'   => 'deletes',
                'uses' => 'OrderController@deletes',
            ]);

            Route::get('generate-invoice/{id}', [
                'as'   => 'generate-invoice',
                'uses' => 'OrderController@getGenerateInvoice',
            ]);

            Route::post('confirm', [
                'as'   => 'confirm',
                'uses' => 'OrderController@postConfirm',
            ]);

            Route::post('send-order-confirmation-email/{id}', [
                'as'   => 'send-order-confirmation-email',
                'uses' => 'OrderController@postResendOrderConfirmationEmail',
            ]);

            Route::post('create-shipment/{id}', [
                'as'   => 'create-shipment',
                'uses' => 'OrderController@postCreateShipment',
            ]);

            Route::post('cancel-shipment/{id}', [
                'as'   => 'cancel-shipment',
                'uses' => 'OrderController@postCancelShipment',
            ]);

            Route::post('update-shipping-address/{id}', [
                'as'   => 'update-shipping-address',
                'uses' => 'OrderController@postUpdateShippingAddress',
            ]);

            Route::post('cancel-order/{id}', [
                'as'   => 'cancel',
                'uses' => 'OrderController@postCancelOrder',
            ]);

            Route::get('print-shipping-order/{id}', [
                'as'   => 'print-shipping-order',
                'uses' => 'OrderController@getPrintShippingOrder',
            ]);

            Route::post('confirm-payment/{id}', [
                'as'   => 'confirm-payment',
                'uses' => 'OrderController@postConfirmPayment',
            ]);

            Route::get('get-shipment-form/{id}', [
                'as'   => 'get-shipment-form',
                'uses' => 'OrderController@getShipmentForm',
            ]);

            Route::post('refund/{id}', [
                'as'   => 'refund',
                'uses' => 'OrderController@postRefund',
            ]);

            Route::get('get-available-shipping-methods', [
                'as'   => 'get-available-shipping-methods',
                'uses' => 'OrderController@getAvailableShippingMethods',
            ]);

            Route::post('coupon/apply', [
                'as'   => 'apply-coupon-when-creating-order',
                'uses' => 'OrderController@postApplyCoupon',
            ]);

        });
    });
});
