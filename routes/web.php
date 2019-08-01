<?php

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

$router->group(['middleware' => 'auth', 'prefix' => '/api/v1/'], function() use ($router) {

    $router->get('/', function () use ($router) {
        return $router->app->version();
    });

    $router->get('/users', ['as' => 'users', 'uses' => 'v1\UsersController@users']);
    $router->get('/feed', ['as' => 'momonation.feed', 'uses' => 'v1\FeedsController@allFeed']);
    $router->post('/transfer', ['as' => 'momonation.transfer', 'uses' => 'v1\MomoBankController@transfer']);
    $router->put('/comment', ['as' => 'comment', 'uses' => 'v1\CommentsController@store']);
});
