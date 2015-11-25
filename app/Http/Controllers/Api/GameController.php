<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Game;
use App\Libraries\Http;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class GameController extends Controller
{
	public function get(Request $request, $gameId)
	{
		$game = new Game($gameId);

		if (!$gameId || $game->isEmpty()) {
			return response()->json(Http::responseFail());
		}

		return response()->json(Http::responseDone($game->all()));
	}
}
