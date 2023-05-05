<?php

namespace App\Http\Controllers;

use App\Models\SleeperTeam;
use App\Models\SleeperTrade;
use Illuminate\Http\Request;
use App\Models\SleeperLeague;
use App\Models\SleeperPlayer;
use App\Models\SleeperDraftPick;
use App\Models\MatchPlayerToTeam;
use App\Models\SleeperTradePiece;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SleeperAPIController extends Controller
{

    // **************************************************** //
    // *************** Quick League Setup ***************** //
    // **************************************************** //

    public function setupLeague($leagueId)
    {
        // try {
            $this->importLeague($leagueId);
            $this->importTeamsFromLeague($leagueId);
            $this->importTeamInfo($leagueId);
            $this->importDraftPicks($leagueId);
        // } catch (\Exception $e)
        // {
        //     $response = [
        //         "success" => false,
        //         "message" => $e->getMessage()
        //     ];
        //     return $response;
        // }
        
        return ["success" => true];

    }

    // **************************************************** //
    // ******* Import League from the Sleeper API ********* //
    // **************************************************** //

    public function importLeague($leagueId)
    {
        // Check to see if league exists
        $league = SleeperLeague::where('sleeper_league_id',$leagueId)->first();
        if (empty($league))
        {
            $league = new SleeperLeague; 
        }

        // Get league info
        $response = Http::get("https://api.sleeper.app/v1/league/".$leagueId);
        $sleeperLeague = $response->object();

        // Set league info & save
        $league->sleeper_league_id = $sleeperLeague->league_id;
        $league->sleeper_draft_id = $sleeperLeague->draft_id;
        $columns = SleeperLeague::getTableColumns(); 
        foreach ($columns as $column)
        {
            if (!empty($sleeperLeague->$column))
            {
                if (is_string($sleeperLeague->$column))
                {
                    $league->$column = $sleeperLeague->$column;
                }
                else 
                {
                    $league->$column = json_encode($sleeperLeague->$column);
                }   
            }
        }
        $league->save();
        return true;
    }

    // **************************************************** //
    // ***** Import League Teams from the Sleeper API ***** //
    // **************************************************** //

    public function importTeamsFromLeague($leagueId)
    {
        // Check to see if league exists
        $league = SleeperLeague::where('sleeper_league_id',$leagueId)->first();
        if (empty($league))
        {
            return [
                "success" => false,
                "message" => "No league found for the Sleeper League ID provided."
            ];
        }

        // Get user info from Sleeper
        $response = Http::get("https://api.sleeper.app/v1/league/".$leagueId."/users");
        $leagueTeams = json_decode($response);

        foreach($leagueTeams as $sleeperTeam)
        {
            $sleeperTeam = (object)$sleeperTeam;

            // Check to see if team exists
            $team = SleeperTeam::where('sleeper_league_id',$league->sleeper_league_id)
                ->where('sleeper_user_id',$sleeperTeam->user_id)
                ->first();

            if (empty($team))
            {
                $team = new SleeperTeam;
            }

            // Set team info
            $metadata = (object)$sleeperTeam->metadata;
            
            $team->league_id = $league->id;
            $team->sleeper_league_id = $league->sleeper_league_id;
            $team->sleeper_user_id = $sleeperTeam->user_id;

            if (!empty($metadata->team_name)){
                $team->team_name = $metadata->team_name;
            } else {
                $team->team_name = $sleeperTeam->display_name;
            }

            if (!empty($metadata->avatar))
            {
                $team->team_logo = $metadata->avatar; 
            } else if (!empty($sleeperTeam->avatar))
            {
                $team->team_logo = "https://sleepercdn.com/avatars/".$sleeperTeam->avatar; 
            }

            $team->save();
        }

        return true;
    }

    // **************************************************** //
    // ******* Import Players from the Sleeper API ******** //
    // *********** ONLY CALL ONCE PER DAY MAX ************* //
    // **************************************************** //

    public function importAllPlayers()
    {
        // comment this out to actually fetch the players
        // return true;

        // Get player info from Sleeper
        $response = Http::get("https://api.sleeper.app/v1/players/nfl");

        // Log::info($response);
        $players = json_decode($response, true);
        $playerColumns = SleeperPlayer::getSleeperTableColumns(); 

        foreach ($players as $playerId => $player)
        {
            $newPlayer = SleeperPlayer::where('sleeper_player_id',$playerId)->first();
            if(empty($newPlayer))
            {
                $newPlayer = new SleeperPlayer;
                $newPlayer->sleeper_player_id = $playerId;
            }

            foreach($playerColumns as $column)
            {
                if (empty($player[$column]))
                {
                    $newPlayer->$column = null;
                } else {
                    if(is_string($player[$column]) || is_integer($player[$column]))
                    {
                        $newPlayer->$column = $player[$column];
                    }
                    else 
                    {
                        $newPlayer->$column = json_encode($player[$column]);
                    }
                }
            }
            $newPlayer->save();
        }

        return "SUCCESS!";

    }

    // **************************************************** //
    // ***** Import Team Information from Sleeper API ***** //
    // **************************************************** //

    public function importTeamInfo($leagueId)
    {
        // Get player info from Sleeper
        $response = Http::get("https://api.sleeper.app/v1/league/".$leagueId."/rosters");
        $rosters = json_decode($response, true);

        $league = SleeperLeague::where('sleeper_league_id',$leagueId)->first();

        // inactive all players in the league
        MatchPlayerToTeam::where('leagueId',$league->id)->update(['active' => 0]);

        foreach($rosters as $roster)
        {
            // Update team record
            $team = SleeperTeam::where("sleeper_user_id",$roster["owner_id"])
                ->where("sleeper_league_id",$roster["league_id"])
                ->first();

            if (empty($team)){
                Log::alert("NO TEAM FOUND... OWNER ID:".$roster["owner_id"]." ... LEAGUE_ID:".$roster["league_id"]);
                continue;
            }

            $team->roster_id = $roster["roster_id"];
            $teamColumns = SleeperTeam::getTeamResultTableColumns();
            foreach($teamColumns as $col)
            {
                if (!empty($roster["settings"][$col]))
                {
                    $team->$col = $roster["settings"][$col];
                }
            }
            $team->save();

            // Map playerIds to each team
            foreach($roster['players'] as $playerId)
            {
                Log::info($playerId);
                $player = SleeperPlayer::where('sleeper_player_id',$playerId)->first();
                if ($player->position == "K" || $player->position == "DEF")
                {
                    continue;
                }

                $matchPlayerToTeam = MatchPlayerToTeam::where('playerId',$playerId)
                    ->where('teamId',$team->id)
                    ->where('leagueId',$team->league_id)
                    ->first();

                if (empty($matchPlayerToTeam))
                {
                    $matchPlayerToTeam = new MatchPlayerToTeam;
                    $matchPlayerToTeam->playerId = $playerId;
                    $matchPlayerToTeam->leagueId = $team->league_id;
                    $matchPlayerToTeam->teamId = $team->id;
                }
                $matchPlayerToTeam->active = 1;
                $matchPlayerToTeam->save();
            }
        }

        return true;
    }

    // **************************************************** //
    // ******* Import Draft Picks from Sleeper API ******** //
    // **************************************************** //

    public function importDraftPicks($leagueId)
    {

        $league = SleeperLeague::where('sleeper_league_id',$leagueId)->first();

        // Get draft pick info from Sleeper
        $response = Http::get("https://api.sleeper.app/v1/draft/".$league->sleeper_draft_id);
        $draft = json_decode($response, true);
        $draft_order = $draft["draft_order"];
        $round = 1;

        // create initial draft picks 
        foreach($draft_order as $userId => $pick)
        {
            $round = 1;
            while ($round <= 3)
            {
                $team = SleeperTeam::where("sleeper_user_id",$userId)
                    ->where("league_id",$league->id)
                    ->first();

                $draftpick = SleeperDraftPick::where("league_id",$league->id)
                    ->where("round", $round)
                    ->where("pick",$pick)
                    ->first();

                if (empty($draftpick)){
                    $draftpick = new SleeperDraftPick;
                    $draftpick->league_id = $league->id;
                    $draftpick->round = $round;
                    $draftpick->pick = $pick;
                    $draftpick->team_id = $team->id;
                    $draftpick->original_owner_id = $team->id;
                    $draftpick->year = 2023;
                    $draftpick->save();
                }
                $round++;
            }
        }

        // If needed, get the new picks
        $needToUploadPicksCheck = SleeperDraftPick::where("league_id",$league->id)
                ->where("year", 2024)
                ->first();
        
        if (empty($needToUploadPicksCheck))
        {
            LeagueController::createFutureDraftPicks($leagueId);
        }

        // backfill the traded picks
        $response = Http::get("https://api.sleeper.app/v1/league/".$league->sleeper_league_id."/traded_picks");
        $traded_picks = json_decode($response, true);

        Log::info($traded_picks);

        foreach($traded_picks as $pick)
        {
            $originalOwner = SleeperTeam::where("roster_id", $pick["roster_id"])
                ->where('league_id',$league->id)
                ->first();  
            $newOwner = SleeperTeam::where("roster_id", $pick["owner_id"])
                ->where('league_id',$league->id)
                ->first();  

            $draftpick = SleeperDraftPick::where("league_id",$league->id)
                    ->where("round", $pick["round"])
                    ->where("original_owner_id",$originalOwner->id)
                    ->where("year",$pick["season"])
                    ->update(['team_id' => $newOwner->id]);
        }

        return true;
    }

    // **************************************************** //
    // ******* Import Transaction from Sleeper API ******** //
    // **************** WORK IN PROGRESS ****************** //
    // **************************************************** //

    public function importTransactions($leagueId)
    {
        return true;
        $league = SleeperLeague::where('sleeper_league_id',$leagueId)->first();

        // Get transaction info from Sleeper
        $response = Http::get("https://api.sleeper.app/v1/league/".$league->sleeper_league_id."/transactions/1");
        $transactions = json_decode($response, true);

        foreach ($transactions as $transaction)
        {
            if ($transaction["type"] != "trade" || $transaction["status"] != "complete")
            {
                continue;
            }

            $trade = SleeperTrade::where('sleeper_transaction_id',$transaction["transaction_id"])->first();
            if (empty($trade))
            {
                $team1 = SleeperTeam::where("roster_id",$transaction["roster_ids"][0])
                    ->where("league_id",$league->id)
                    ->first();
                $team2 = SleeperTeam::where("roster_id",$transaction["roster_ids"][1])
                    ->where("league_id",$league->id)
                    ->first();

                $trade = new SleeperTrade;
                $trade->sleeper_transaction_id = $transaction["transaction_id"];
                $trade->team1_roster_id = $team1->id;
                $trade->team2_roster_id = $team2->id;
                $trade->save();

                // $transaction["drops"] = json_decode($transaction["drops"], true);

                // Players
                if ($transaction["drops"])
                {
                    foreach($transaction["drops"] as $playerId => $rosterId)
                    {
                        $player = SleeperPlayer::where("sleeper_player_id", $playerId)->first();
                        
                        $piece = new SleeperTradePiece;
                        $piece->trade_id = $trade->id;
                        $piece->player_id = $player->id;
                        $piece->old_team_id = ($team1->roster_id == $rosterId ? $team1->id : $team2->id);
                        if (!empty($transaction["adds"][$playerId]))
                        {
                            $piece->new_team_id = ($team1->roster_id == $rosterId ? $team1->id : $team2->id);
                        }
                        $piece->save();
                    }
                }

                // Picks
                if ($transaction["draft_picks"])
                {
                    foreach($transaction["draft_picks"] as $pick)
                    {
                        $originalOwner = SleeperTeam::where("roster_id",$pick["roster_id"])
                            ->where("league_id",$league->id)
                            ->first();

                        $pick = SleeperDraftPick::where("year", 2023)
                            ->where("round", $pick["round"])
                            ->where("original_owner_id", $originalOwner->id)
                            ->where("league_id",$league->id)
                            ->first();
                        
                        $piece = new SleeperTradePiece;
                        $piece->trade_id = $trade->id;
                        $piece->draft_pick_id = $pick->id;
                        $piece->old_team_id = ($team1->roster_id == $pick["previous_owner_id"] ? $team1->id : $team2->id);
                        $piece->new_team_id = ($team1->roster_id != $pick["previous_owner_id"] ? $team1->id : $team2->id);
                        $piece->save();
                    }
                }
            }
        }
    

        return true;
    }


}
