<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\SleeperAPIController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    return view('home');
});

Route::get('/test', function () {
    return view('test');
});

Route::get('/setup/league/{league_id}', [SleeperAPIController::class, 'setupLeague']);
Route::get('/import/league/{league_id}', [SleeperAPIController::class, 'importLeague']);
Route::get('/import/league/teams/{league_id}', [SleeperAPIController::class, 'importTeamsFromLeague']);
Route::get('/import/players/all', [SleeperAPIController::class, 'importAllPlayers']);
Route::get('/import/league/teams/rosters/{league_id}', [SleeperAPIController::class, 'importTeamInfo']);
Route::get('/import/league/draft/picks/{league_id}', [SleeperAPIController::class, 'importDraftPicks']);
Route::get('/import/league/transactions/{league_id}', [SleeperAPIController::class, 'importTransactions']);

Route::get('/league/{league_id}', [LeagueController::class, 'getLeagueInfo']);
Route::get('/league/get-teams/{league_id}', [LeagueController::class, 'getLeagueTeams']);
Route::get('/league/create-draft-picks/{league_id}', [LeagueController::class, 'createFutureDraftPicks']);

Route::get('/team/value/{team_id}', [TeamController::class, 'getTeamValue']);
Route::get('/team/value/expanded/{team_id}', [TeamController::class, 'getExpandedTeamData']);
Route::get('/team/value/picks/{league_id}', [TeamController::class, 'pickValueCalculation']);


Route::get('/trade/score/{trade_id}', [TradeController::class, 'getTradeScore']);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Auth::routes();