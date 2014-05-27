<?php

Route::get('/', 'HomeController@index');
Route::get('/stream', 'StreamController@index');
Route::get('/users', 'UserController@index');
Route::get('/stars', 'StarController@index');