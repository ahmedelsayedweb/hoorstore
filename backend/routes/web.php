<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Facebook Catalog Feed
$router->get('feed/facebook', 'FacebookFeedController@feed');

// Products API
$router->group(['prefix' => 'api'], function () use ($router) {
    // Products
    $router->get('products', 'ProductController@index');
    $router->get('products/{id}', 'ProductController@show');
    $router->post('products', 'ProductController@store');
    $router->put('products/{id}', 'ProductController@update');
    $router->delete('products/{id}', 'ProductController@destroy');
    $router->post('upload/image', 'ProductController@uploadImage');
    $router->delete('upload/image', 'ProductController@deleteImage');
    $router->post('upload/cleanup', 'ProductController@cleanupUnusedFiles');

    // Cart (Per Browser)
    $router->get('cart/{browserId}', 'CartController@index');
    $router->post('cart/{browserId}', 'CartController@store');
    $router->put('cart/{browserId}/{id}', 'CartController@update');
    $router->delete('cart/{browserId}/{id}', 'CartController@destroy');
    $router->delete('cart/{browserId}', 'CartController@clear');

    // Orders
    $router->get('orders', 'OrderController@index');
    $router->get('orders/{id}', 'OrderController@show');
    $router->get('orders/number/{orderNumber}', 'OrderController@showByNumber');
    $router->get('orders/phone/{phone}', 'OrderController@getByPhone');
    $router->post('orders', 'OrderController@store');
    $router->put('orders/{id}', 'OrderController@update');
    $router->delete('orders/{id}', 'OrderController@destroy');

    // Shipping
    $router->get('shipping/governorates', 'ShippingController@index');
    $router->get('shipping/zones', 'ShippingController@zones');
    $router->post('shipping/price', 'ShippingController@getPrice');

    // Coupons
    $router->post('coupons/validate', 'CouponController@validateCoupon');
    $router->get('coupons', 'CouponController@index');
    $router->post('coupons', 'CouponController@store');
    $router->put('coupons/{id}', 'CouponController@update');
    $router->delete('coupons/{id}', 'CouponController@destroy');

    // Purchases
    $router->get('purchases', 'PurchaseController@index');
    $router->post('purchases', 'PurchaseController@store');
    $router->put('purchases/{id}', 'PurchaseController@update');
    $router->delete('purchases/{id}', 'PurchaseController@destroy');

    // Sales
    $router->get('sales', 'SaleController@index');
    $router->post('sales', 'SaleController@store');
    $router->put('sales/{id}', 'SaleController@update');
    $router->delete('sales/{id}', 'SaleController@destroy');

    // Migrations (Admin)
    $router->post('migrate/run', 'MigrationController@run');
    $router->get('migrate/status', 'MigrationController@status');
});
