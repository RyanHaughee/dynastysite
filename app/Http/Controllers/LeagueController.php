<?php

namespace App\Http\Controllers;

use App\Models\SleeperTeam;
use Illuminate\Http\Request;
use App\Models\SleeperLeague;
use App\Models\SleeperDraftPick;
use Illuminate\Support\Facades\Log;

class LeagueController extends Controller
{
    // **************************************************** //
    // ***************** Get league info ****************** //
    // **************************************************** //

    public function getLeagueInfo($sleeperLeagueId)
    {
        // get league data
        $league = SleeperLeague::where("sleeper_league_id",$sleeperLeagueId)->first(); 

        if (empty($league)){
            return [
                "success" => false,
                "message" => "No league found for the Sleeper ID provided."
            ];
        }

        return [
            "success" => true,
            "league" => json_encode($league)
        ];
    }

    // **************************************************** //
    // ***************** Get league teams ***************** //
    // **************************************************** //

    public function getLeagueTeams($leagueId)
    {
        $league = SleeperLeague::find($leagueId);

        if (empty($league)){
            return [
                "success" => false,
                "message" => "No league found for the ID provided."
            ];
        }
        
        $teams = $league->getLeagueTeams();

        foreach ($teams as $team)
        {
            $team_value = $team->getTeamValue();
            $team->value = json_encode($team_value);
        }

        return [
            "success" => true,
            "teams" => json_encode($teams)
        ];
    }

    // **************************************************** //
    // **** Should only be called once for each league **** //
    // **************************************************** //
    public static function createFutureDraftPicks($league_id)
    {
        // return;
        $league = SleeperLeague::where('sleeper_league_id',$league_id)->first();
        $teams = SleeperTeam::where('sleeper_league_id',$league_id)->get();

        $year = 2024;
        while ($year <= 2025)
        {
            $round = 1;
            while ($round <= 3)
            {
                foreach($teams as $team)
                {
                    $draftpick = new SleeperDraftPick;
                    $draftpick->league_id = $league->id;
                    $draftpick->round = $round;
                    $draftpick->team_id = $team->id;
                    $draftpick->original_owner_id = $team->id;
                    $draftpick->year = $year;
                    $draftpick->save();
                }
                $round++;

            }
            $year++;
        }
    }
}
