<?php

namespace App\Http\Controllers;

use App\Models\SleeperTeam;
use App\Models\SleeperDraft;
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
    // **************************************************** //
    public static function createFutureDraftPicks($league_id, $max_year, $rounds)
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
                while ($round <= $rounds)
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
        $rounds = [
            1 => "1st",
            2 => "2nd",
            3 => "3rd",
            4 => "4th"
        ];

        $score_array = [
            "A+" => 87,
            "A" => 83,
            "A-" => 80,
            "B+" => 77,
            "B" => 73,
            "B-" => 70,
            "C+" => 67,
            "C" => 63,
            "C-" => 60,
            "D+" => 57,
            "D" => 53,
            "F" => 0
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
                } else if (!empty($piece->draft_pick_id))
                {
                    $pick = SleeperDraftPick::select('round','year','original_owner_id')->where("id",$piece->draft_pick_id)->first();
                    $original_owner = SleeperTeam::select('team_name')->where("id",$pick->original_owner_id)->first();
                    $details[$piece->new_team_id][] = $original_owner->team_name." ".$pick->year." ".$rounds[$pick->round];
                }
            }
            $team1 = SleeperTeam::select('team_name', 'id', 'team_logo')
                ->where("roster_id",$trade->team1_roster_id)
                ->where("league_id",$league_id)
                ->first();
            $team2 = SleeperTeam::select('team_name', 'id', 'team_logo')
                ->where("roster_id",$trade->team2_roster_id)
                ->where("league_id",$league_id)
                ->first();
            
            $trade->team1_details = $details[$team1->id] ?? [];
            $trade->team1 = $team1;
            $trade->team2_details = $details[$team2->id] ?? [];
            $trade->team2 = $team2;

            $trade->team1_grade = (int) (75 + (($trade->team1_value)*416.7));
            $trade->team2_grade = (int) (75 + (($trade->team2_value)*416.7));
            $total_score = (int) (75 + (($trade->team2_value+$trade->team1_value)*250));

            foreach($score_array as $grade => $score)
            {
                if ($total_score >= $score)
                {
                    $trade->total_score = $grade;
                    break;
                }
            }

            $trade->team1_opacity = abs(($trade->team1_grade-75)/25);
            $trade->team2_opacity = abs(($trade->team2_grade-75)/25);
        }

        return [
            "success" => true,
            "trades" => json_encode($trades)
        ];
    }
}
