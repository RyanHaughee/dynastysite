<?php

namespace App\Http\Controllers;

use App\Models\SleeperTeam;
use App\Models\SleeperDraft;
use App\Models\SleeperTrade;
use Illuminate\Http\Request;
use App\Models\SleeperLeague;
use App\Models\SleeperPlayer;
use App\Models\SleeperDraftPick;
use App\Models\MatchPlayerToTeam;
use App\Models\SleeperTradePiece;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
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
            $this->makeDraftPicks($leagueId);
            $this->importTransactions($leagueId);
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

        // clear any caches surronding this league
        Redis::del("leagueTransactions:".$league->id);

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
        return true;

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

            // Invalidate the redis cache
            Redis::del("expandedteam:".$team->id);
        }
        
        return true;
    }

    // **************************************************** //
    // ******* Import Draft Picks from Sleeper API ******** //
    // **************************************************** //

    public function importDraftPicks($leagueId)
    {
        $league = SleeperLeague::where('sleeper_league_id',$leagueId)->first();
        $in_season = $league->status == "in_season" ?? false;

        // Create draft
        $response = Http::get("https://api.sleeper.app/v1/league/".$league->sleeper_league_id."/drafts");
        $draft_obj = json_decode($response, true);

        foreach($draft_obj as $do)
        {

            // check if draft exists
            $draft_exists = SleeperDraft::where('league_id',$league->id)
                ->where('season',intval($do["season"]))
                ->first();
            
            if (empty($draft_exists))
            {
                $new_draft = new SleeperDraft;
                $new_draft->league_id = $league->id;
                $new_draft->season = intval($do["season"]);
                $new_draft->status = $do["status"];
                $new_draft->settings = json_encode($do["settings"]);
                $new_draft->start_time = strval($do["start_time"]);
                $new_draft->last_picked = strval($do["last_picked"]);
                $new_draft->save(); 
            }
        }

        // Get draft pick info from Sleeper
        $response = Http::get("https://api.sleeper.app/v1/draft/".$league->sleeper_draft_id);
        $draft = json_decode($response, true);
        $draft_order = $draft["draft_order"];
        $round = 1;

        // create initial draft picks 
        foreach($draft_order as $userId => $pick)
        {
            $round = 1;
            while ($round <= $draft['settings']['rounds'])
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
        $year = $in_season ? 2026 : 2025;

        $needToUploadPicksCheck = SleeperDraftPick::where("league_id",$league->id)
                ->where("year", $year)
                ->first();
        
        if (empty($needToUploadPicksCheck))
        {
            LeagueController::createFutureDraftPicks($leagueId, $year, $draft['settings']['rounds']);
        }

        // backfill the traded picks
        $response = Http::get("https://api.sleeper.app/v1/league/".$league->sleeper_league_id."/traded_picks");
        $traded_picks = json_decode($response, true);

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
        $league = SleeperLeague::where('sleeper_league_id',$leagueId)->first();
        $league_id = $league->id;
        $log = [];

        // Get transactions from the cache or sleeper API
        $transactions = json_decode(Redis::get("leagueTransactions:".$league->id), true);

        if (empty($transactions))
        {
            // Get transaction info from Sleeper
            $response = Http::get("https://api.sleeper.app/v1/league/".$league->sleeper_league_id."/transactions/1");
            $transactions = json_decode($response, true);
            Redis::set("leagueTransactions:".$league->id, json_encode($transactions));
        }

        $rosters = [];

        $teams = SleeperTeam::where('league_id',$league->id)->get();
        foreach ($teams as $team)
        {
            // Get team roster from the cache or Sleeper API
            $team_info = json_decode(Redis::get("expandedteam:".$team->id));
            if (empty($team_info))
            {
               $team_info = $team->getExpandedTeamData();
               Redis::set("expandedteam:".$team->id, json_encode($team_info)); 
            }
            $players = json_decode($team_info->players);
            $draft_picks = json_decode($team_info->draft_picks);
            if (!isset($rosters[$team->roster_id]))
            {
                foreach($players as $player)
                {
                    $rosters[$team->roster_id]["players"][$player->sleeper_player_id] = $player;
                    if (!isset($log[1]))
                    {
                        Log::info("1: ");
                        Log::info(gettype($player));
                        $log[1] = true;
                    }
                }
                foreach($draft_picks as $pick)
                {
                    $rosters[$team->roster_id]["picks"][$pick->id] = $pick;
                }
            }
        }

        // get when the 2023 draft happened
        $draft_times = SleeperDraft::where('league_id',$league->id)
            ->where('season', 2023)
            ->where('status','complete')
            ->first();

        $team_draft_picks_added = [];

        foreach ($transactions as $transaction)
        {
            $scores = [];
            $players = [];

            $predraft = !empty($draft_times->start_time) ? $transaction["status_updated"] <= (float)$draft_times->start_time : true;

            if ($transaction["type"] == "trade") {
                $trade_obj = SleeperTrade::where("sleeper_transaction_id",$transaction["transaction_id"])->first();
                if (empty($trade_obj))
                {
                    $trade_obj = new SleeperTrade;
                    $trade_obj->sleeper_transaction_id = $transaction["transaction_id"];
                    $trade_obj->league_id = $league->id;
                }
            
                $teamIdx = 1;
                foreach ($transaction["roster_ids"] as $rId)
                {
                    if (empty($team_draft_picks_added[$rId]) && $predraft)
                    {
                        foreach($rosters[$rId]["players"] as $ply)
                        {
                            $ply = (object) $ply;
                            if (empty($ply->years_exp))
                            {
                                unset($rosters[$rId]["players"][$ply->sleeper_player_id]);

                                // check to see if he should be converted to a draft pick
                                $pick = SleeperDraftPick::where('league_id',$league->id)
                                    ->where('year',2023)
                                    ->where('player_id',$ply->sleeper_player_id)
                                    ->first();

                                if (!empty($pick))
                                {
                                    $rosters[$rId]["picks"][$pick->id] = $pick;
                                }
                            }
                        }
                    }

                    if ($teamIdx == 1)
                    {
                        $trade_obj->team1_roster_id = $rId;
                    } else if ($teamIdx == 2) {
                        $trade_obj->team2_roster_id = $rId;
                    }
                    $teamIdx++;

                    $team_value = SleeperTeam::computeRosterValue($rosters[$rId]["players"],$league->id);
                    $draftValue = SleeperTeam::computeDraftValue($rosters[$rId]["picks"], $rosters[$rId]["players"]);
                    $team_value["draft"] = $draftValue;
                    $team_value["total"]["value"] += $team_value["draft"]["total"]["value"];
                    $scores[$rId]["players"] = [
                        "after" => $team_value
                    ];
                }
                $trade_obj->save();
            }
            // Add or remove the players
            if ($transaction["adds"])
            {
                foreach ($transaction["adds"] as $player_id => $roster_id)
                {
                    if ($transaction["type"] == "trade") 
                    {
                        $player_to_add = DB::table('sleeper_players')
                            ->select('sleeper_players.*', 'player_ktc_value.player_value')
                            ->leftJoin('player_ktc_value','player_ktc_value.player_id','=','sleeper_players.id')
                            ->where('sleeper_players.sleeper_player_id',$player_id)
                            ->first();

                        $new_team = SleeperTeam::where("roster_id",$roster_id)
                            ->where("league_id",$league->id)
                            ->first();

                        $trade_piece = SleeperTradePiece::where('trade_id','=',$trade_obj->id)    
                            ->where('player_id',$player_to_add->id)
                            ->where('new_team_id',$new_team->id)
                            ->first();
                        
                        if (empty($trade_piece))
                        {
                            $trade_piece = new SleeperTradePiece;
                            $trade_piece->trade_id = $trade_obj->id;
                            $trade_piece->player_id = $player_to_add->id;
                            $trade_piece->new_team_id = $new_team->id;
                            $trade_piece->save();
                        }

                    }
                    unset($rosters[$roster_id]["players"][$player_id]);
                }
            }

            if ($transaction["drops"])
            {
                foreach ($transaction["drops"] as $player_id => $roster_id)
                {
                    $players[$roster_id]["players"][] = $player_id; 
                    $player_to_add = DB::table('sleeper_players')
                        ->select('sleeper_players.*', 'player_ktc_value.player_value')
                        ->leftJoin('player_ktc_value','player_ktc_value.player_id','=','sleeper_players.id')
                        ->where('sleeper_players.sleeper_player_id',$player_id)
                        ->first();

                    $rosters[$roster_id]["players"][$player_id] = $player_to_add;
                }
            }
            foreach ($transaction['draft_picks'] as $pick)
            {
                $draft_pick = DB::table('sleeper_draft_picks')
                    ->select('sleeper_draft_picks.*')
                    ->join('sleeper_teams','sleeper_teams.id','=','sleeper_draft_picks.original_owner_id')
                    ->where('sleeper_draft_picks.year',$pick["season"])
                    ->where('sleeper_draft_picks.round',$pick["round"])
                    ->where('sleeper_teams.roster_id',$pick["roster_id"])
                    ->where('sleeper_draft_picks.league_id',$league->id)
                    ->first();

                $new_team = SleeperTeam::where("roster_id",$pick["owner_id"])
                    ->where("league_id",$league->id)
                    ->first();

                if (!empty($draft_pick->player_id) && !$predraft && $draft_pick->team_id == $new_team->id)
                {
                    $players[$roster_id]["players"][] = $draft_pick->player_id; 
                    $player_to_add = DB::table('sleeper_players')
                        ->select('sleeper_players.*', 'player_ktc_value.player_value')
                        ->leftJoin('player_ktc_value','player_ktc_value.player_id','=','sleeper_players.id')
                        ->where('sleeper_players.sleeper_player_id',$draft_pick->player_id)
                        ->first();

                    $rosters[$pick["previous_owner_id"]]["picks"][$draft_pick->id] = $draft_pick;
                    unset($rosters[$pick["owner_id"]]["players"][$draft_pick->player_id]);

                    $trade_piece = SleeperTradePiece::where('trade_id','=',$trade_obj->id)
                        ->where(function ($query) use ($draft_pick, $player_to_add) {
                            $query->where('player_id',$player_to_add->id)
                                ->orWhere('draft_pick_id',$draft_pick->id);
                        })
                        ->where('new_team_id',$new_team->id)
                        ->first();
                    
                    if (empty($trade_piece))
                    {
                        $trade_piece = new SleeperTradePiece;
                        $trade_piece->trade_id = $trade_obj->id;
                        $trade_piece->player_id = $player_to_add->id;
                        $trade_piece->new_team_id = $new_team->id;
                        $trade_piece->save();
                    }
                } else {
                    unset($rosters[$pick["owner_id"]]["picks"][$draft_pick->id]);
                    $rosters[$pick["previous_owner_id"]]["picks"][$draft_pick->id] = $draft_pick;
                    $players[$pick["previous_owner_id"]]["picks"][] = $draft_pick->id; 
    
                    $trade_piece = SleeperTradePiece::where('trade_id','=',$trade_obj->id)    
                        ->where('draft_pick_id',$draft_pick->id)
                        ->where('new_team_id',$new_team->id)
                        ->first();
                    
                    if (empty($trade_piece))
                    {
                        $trade_piece = new SleeperTradePiece;
                        $trade_piece->trade_id = $trade_obj->id;
                        $trade_piece->draft_pick_id = $draft_pick->id;
                        $trade_piece->new_team_id = $new_team->id;
                        $trade_piece->save();
                    } 
                }
                
            }
            if ($transaction["type"] == "trade") {
                foreach ($transaction["roster_ids"] as $rId)
                {
                    $team_value = SleeperTeam::computeRosterValue($rosters[$rId]["players"],$league->id);
                    $draftValue = SleeperTeam::computeDraftValue($rosters[$rId]["picks"],$rosters[$rId]["players"]);
                    $team_value["draft"] = $draftValue;
                    $team_value["total"]["value"] += $team_value["draft"]["total"]["value"];
                    $scores[$rId]["players"]["before"] = $team_value;
                }

                // if ($trade_obj->id == 3)
                // {
                //     echo "<pre>";
                //     print_r($scores);
                //     echo "</pre>";
                // }

                $difference = [];
                $teamIdx = 1;

                foreach($scores as $rosterId => $score)
                {
                    $after = (float)$score["players"]["after"]["total"]["value"];
                    $before = (float)$score["players"]["before"]["total"]["value"];
                    $difference[$rosterId] = ($after - $before)/$before;

                    $trade_obj = SleeperTrade::find($trade_obj->id);
                    if ($trade_obj->team1_roster_id == $rosterId)
                    {
                        $trade_obj->team1_value = sprintf("%.3f", $difference[$rosterId]);
                    } else if ($trade_obj->team2_roster_id == $rosterId) {
                        $trade_obj->team2_value = sprintf("%.3f", $difference[$rosterId]);
                    }

                    $trade_obj->save();
                }
            }

        }
    

        return true;
    }

    public function makeDraftPicks($leagueId)
    {
        $league = SleeperLeague::where('sleeper_league_id',$leagueId)->first();
        // Get draft pick info from Sleeper
        $response = Http::get("https://api.sleeper.app/v1/draft/".$league->sleeper_draft_id."/picks");
        $draft = json_decode($response, true);

        foreach ($draft as $pick)
        {
            SleeperDraftPick::where('year',2023)
                ->where('round',$pick["round"])
                ->where('pick',$pick["pick_no"]-(($pick["round"]-1)*12))
                ->where('league_id',$league->id)
                ->update(['player_id' => $pick["player_id"]]);
        }
    }


}
