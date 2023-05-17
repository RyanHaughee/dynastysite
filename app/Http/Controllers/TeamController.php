<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\SleeperTeam;
use Illuminate\Http\Request;
use App\Models\SleeperDraftPick;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TeamController extends Controller
{

    // **************************************************** //
    // ******** Model function to get team value ********** //
    // **************************************************** //

    public function getTeamValue($team_id)
    {
        $team = SleeperTeam::find($team_id);
        $team->getTeamValue();
    }

    // **************************************************** //
    // ********* Grabs players and roster value *********** //
    // **************************************************** //

    public function getExpandedTeamData($team_id)
    {
        try {
            $team_data = json_decode(Redis::get("expandedteam:".$team_id));
            if (empty($team_data))
            {
                $team = SleeperTeam::find($team_id);
                $team_data = $team->getExpandedTeamData();

                Redis::set("expandedteam:".$team_id, json_encode($team_data));
            }
            
        } catch(Throwable $e) {
            Log::error($e);
            return [
                "success" => false,
                "team" => "Something went wrong fetching advanced team data."
            ];
        }

        return [
            "success" => true,
            "team" => $team_data
        ];
    }
}
