<?php

namespace App\Http\Controllers;

use App\Models\SleeperTeam;
use App\Models\SleeperTrade;
use Illuminate\Http\Request;
use App\Models\SleeperLeague;
use App\Models\SleeperPlayer;
use App\Models\SleeperDraftPick;
use App\Models\SleeperTradePiece;
use Illuminate\Support\Facades\DB;
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

    public function loadTransactions($league_id)
    {
        $trades = SleeperTrade::where("league_id",$league_id)->get();

        $grades = [
            "A+" => 0.06,
            "A" => 0.05,
            "A-" => 0.04,
            "B+" => 0.03,
            "B" => 0.02,
            "B-" => 0.01,
            "C+" => 0,
            "C" => -0.01,
            "C-" => -0.02,
            "D+" => -0.03,
            "D" => -0.04,
            "D-" => -0.05,
            "F" => -1
        ];

        foreach ($trades as $trade)
        {
            $pieces = SleeperTradePiece::where("trade_id",$trade->id)->get();
            $details = [];
            foreach ($pieces as $piece)
            {
                if (!empty($piece->player_id))
                {
                    $player = SleeperPlayer::select('full_name')->where("id",$piece->player_id)->first(); 
                    $details[$piece->new_team_id][] = $player->full_name;
                    if ($piece->player_id == 3421)
                    {
                        Log::info($details);
                    }
                } else if (!empty($piece->draft_pick_id))
                {
                    $pick = SleeperDraftPick::select('round','year','original_owner_id')->where("id",$piece->draft_pick_id)->first();
                    $original_owner = SleeperTeam::select('team_name')->where("id",$pick->original_owner_id)->first();
                    $details[$piece->new_team_id][] = $original_owner->team_name." ".$pick->year." ".$pick->round;
                }
            }
            $team1 = SleeperTeam::select('team_name', 'id')->where("roster_id",$trade->team1_roster_id)->first();
            $team2 = SleeperTeam::select('team_name', 'id')->where("roster_id",$trade->team2_roster_id)->first();
            $trade->team1_details = $details[$team1->id] ?? [];
            $trade->team1 = $team1;
            $trade->team2_details = $details[$team2->id] ?? [];
            $trade->team2 = $team2;

            $team1_grade = (float) $trade->team1_value;
            $team2_grade = (float) $trade->team2_value;

            foreach($grades as $grade => $score)
            {
                if (empty($trade->team1_grade) && $team1_grade > $score)
                {
                    $trade->team1_grade = $grade;
                }
                if (empty($trade->team2_grade) && $team2_grade > $score)
                {
                    $trade->team2_grade = $grade;
                }
            }
        }

        return [
            "success" => true,
            "trades" => json_encode($trades)
        ];
    }
}
