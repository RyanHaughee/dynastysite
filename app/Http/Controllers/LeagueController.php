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

        return [
            "success" => true,
            "teams" => json_encode($teams)
        ];
    }

    // **************************************************** //
    // **** Should only be called once for each league **** //
    // ****** TODO: Create a way to cross-check this ****** //
    // **************************************************** //
    public static function createFutureDraftPicks($league_id, $max_year)
    {
        $league = SleeperLeague::where('sleeper_league_id',$league_id)->first();

        $pickCheck = SleeperDraftPick::where('league_id',$league->id)
            ->where('year',$max_year)
            ->count();

        if ($pickCheck > 0)
        {
            return;
        }

        $teams = SleeperTeam::where('sleeper_league_id',$league_id)->get();

        $year = 2024;
        while ($year <= $max_year)
        {
            $needToUploadPicksCheck = SleeperDraftPick::where("league_id",$league->id)
                ->where("year", $max_year)
                ->first();

            if (empty($needToUploadPicksCheck))
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
            }
            $year++;
        }
    }
}
