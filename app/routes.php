<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/
/*
 * HEY! Before editing this!
 *
 * If you were planning to add a route that Brian missaid on air, DON'T.
 * This file would grow to 3 MB.
 */

// Home
Route::get('/', ['uses' => 'HomeController@home', 'as' => 'home']);

// Authentication
Route::group(['prefix' => 'auth'], function() {
	// Login, logout
	Route::post('login', ['uses' => 'AuthController@login', 'as' => 'auth.login']);
	Route::post('logout', ['uses' => 'AuthController@logout', 'as' => 'auth.logout']);

	// Registration
	Route::get('register', ['uses' => 'AuthController@registerForm', 'as' => 'auth.register.form']);
	Route::post('register', ['uses' => 'AuthController@register', 'as' => 'auth.register']);
});

// User profile
Route::get('user/{username}', ['uses' => 'UserController@show', 'as' => 'user.show']);

// Leagues page
Route::get('leagues', ['uses' => 'LeagueController@index', 'as' => 'league.index']);


Route::get('league/{league_slug}', ['uses' => 'LeagueController@show', 'as' => 'league.show']);


/**
 * Global route filters
 */
Route::when('*', 'csrf', ['post']);