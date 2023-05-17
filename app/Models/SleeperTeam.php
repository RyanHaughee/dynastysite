<?php

namespace App\Models;

use App\Models\SleeperLeague;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SleeperTeam extends Model
{
    use HasFactory;

    public static function getTeamResultTableColumns()
    {
        $columns = [
            'wins',
            'waiver_position',
            'waiver_budget_used',
            'total_moves',
            'ties',
            'losses',
            'fpts_decimal',
            'fpts_against_decimal',
            'fpts_against',
            'fpts',
            'division'
        ];
        return $columns;
    }

    public function getTeamPlayers()
    {
        $players = DB::table('sleeper_teams')
            ->select('sleeper_players.*', 'player_ktc_value.player_value') 
            ->leftJoin('match_player_to_team', function($join)
                {
                    $join->on('match_player_to_team.teamId','=','sleeper_teams.id');
                    $join->where('match_player_to_team.active','=',1);
                })
            ->join('sleeper_players','match_player_to_team.playerId','=','sleeper_players.sleeper_player_id')
            ->leftJoin('player_ktc_value','player_ktc_value.player_id','=','sleeper_players.id')
            ->where('sleeper_teams.id','=',$this->id)
            ->orderBy('sleeper_players.position')
            ->orderBy('player_ktc_value.player_value', "DESC")
            ->get();

        return $players;
    }

    public function getTeamPlayersByValue()
    {
        $players = DB::table('sleeper_teams')
            ->select('sleeper_players.*', 'player_ktc_value.player_value') 
            ->leftJoin('match_player_to_team', function($join)
                {
                    $join->on('match_player_to_team.teamId','=','sleeper_teams.id');
                    $join->where('match_player_to_team.active','=',1);
                })
            ->join('sleeper_players','match_player_to_team.playerId','=','sleeper_players.sleeper_player_id')
            ->leftJoin('player_ktc_value','player_ktc_value.player_id','=','sleeper_players.id')
            ->where('sleeper_teams.id','=',$this->id)
            ->orderBy('player_ktc_value.player_value', "DESC")
            ->get();

        return $players;
    }

    // **************************************************** //
    // ********* Get Team Value for Current Team ********** //
    // **************************************************** //

    public function getTeamValue($pos = null)
    {
        // Get players
        $players = $this->getTeamPlayersByValue();
        $teamValue = $this::computeRosterValue($players,$this->league_id);
        
        return $teamValue;
        
    }

    // **************************************************** //
    // ********** Actual Team Value Computation *********** //
    // **************************************************** //

    public static function computeRosterValue($players, $league_id)
    {
        $league = SleeperLeague::find($league_id);
        $roster_settings = $league->getRosterParameters();

        if (isset($roster_settings["SUPER_FLEX"]))
        {
            if (isset($roster_settings["QB"]))
            {
                $roster_settings["QB"] += $roster_settings["SUPER_FLEX"];
                unset($roster_settings["SUPER_FLEX"]);
            }
        }

        if (isset($roster_settings["FLEX"]))
        {
            $eligible_positions = ["RB","WR"];

            foreach($eligible_positions as $pos)
            {
                if (isset($roster_settings[$pos]))
                {
                    $roster_settings[$pos] += ($roster_settings["FLEX"]/2);
                }
            }
            unset($roster_settings["FLEX"]);
        }

        $players = collect($players);

        $players = $players->sortByDesc(function($player)
        {
            return $player->player_value;
        }); 

        $teamValue = [];
        $teamValue["total"] = [
            "count" => 0,
            "value" => 0
        ];

        foreach($players as $player)
        {
            if (!isset($teamValue[$player->position]))
            {
                $teamValue[$player->position] = [
                    "count" => 0,
                    "value" => 0
                ];
            }
            $teamValue[$player->position]["count"] += 1;
            $teamValue["total"]["count"] += 1;

            // Computational Variables
            $fullValueCount = ($roster_settings[$player->position])+1;
            $partialValueCount = (($roster_settings[$player->position])*2)+1;

            $multiplier = ($partialValueCount - $teamValue[$player->position]["count"]) / $fullValueCount;
            
            if ($multiplier >= 1)
            {
                $teamValue[$player->position]["value"] += $player->player_value;
                $teamValue["total"]["value"] += $player->player_value;
            }
            else if ($multiplier >= 0)
            {
                $teamValue[$player->position]["value"] += ($multiplier * $player->player_value);
                $teamValue["total"]["value"] += ($multiplier * $player->player_value);
            }
        }

        return $teamValue;
    }

    // **************************************************** //
    // ********** Actual Team Value Computation *********** //
    // **************************************************** //

    public static function computeDraftValue($picks, $players)
    {
        $playerValues = [];
        foreach($players as $player)
        {
            $playerValues[] = $player->player_value;
        }
        sort($playerValues);

        $pickValue = [
            "total" => ["count" => 0, "value" => 0]
        ];

        // create future picks array
        $future_pick_value_arr = [];
        $verbiage = [
            1 => "1st",
            2 => "2nd",
            3 => "3rd"
        ];

        $futurePicks = DB::table("player_ktc_value")
            ->select("player_ktc_value.*")
            ->whereNull('player_id')
            ->orderBy('player_name', "ASC")
            ->skip(12)
            ->take(24)
            ->get();

        foreach ($futurePicks as $pick)
        {
            $future_pick_value_arr[$pick->player_name] = $pick->player_value;
        }

        // $pick_array = [];

        foreach($picks as $pick)
        {
            if (!isset($pickValue[$pick->year]))
            {
                $pickValue[$pick->year] = ["count" => 0, "value" => 0];
            }

            $pick_year_value = ($pick->year > 2025) ? 2025 : $pick->year;

            // Get pick value
            if (!empty($pick->pick))
            {
                $skip = ($pick->pick-1)+(($pick->round-1)*12);

                $value = DB::table('sleeper_players')
                    ->select(DB::raw("player_ktc_value.player_value"))
                    ->join("player_ktc_value","player_ktc_value.player_id","=","sleeper_players.id")
                    ->whereNull("years_exp")
                    ->orderBy('player_ktc_value.player_value','desc')
                    ->skip($skip)
                    ->take(1)
                    ->get();

                $pick->value = $value[0]->player_value;
            } else {
                $teamComparable = SleeperDraftPick::where("original_owner_id",$pick->original_owner_id)
                    ->where("round",$pick->round)
                    ->whereNotNull("pick")
                    ->first();

                // $yearConversion -- value used to average out pick value
                // $valueToAverage -- pick number + $yearConversion

                $yearConversion = (($pick->year)-2023)*(6+(($pick->round-1)*12));
                $valueToAverage = $yearConversion + ($teamComparable->pick + (($teamComparable->round-1)*12));
                $pick_value = $valueToAverage / (($pick->year)-2022);

                $value = 0;
                $roundAdj = 12*($pick->round-1);
                if ($pick_value < 6.5 || ($pick_value >= 10.5 && $pick_value < 18.5) || ($pick_value >= 22.5 && $pick_value < 30.5))
                {
                    $distanceFromEarly = abs($pick_value - (2.5+$roundAdj));
                    $weight = (4-$distanceFromEarly) / 4;

                    $value = $value + ($future_pick_value_arr[$pick_year_value." Early ".$verbiage[$pick->round]] * $weight);
                }
                if ($pick_value >= 2.5 && $pick_value < 10.5 || ($pick_value >= 14.5 && $pick_value < 22.5) || ($pick_value >= 26.5 && $pick_value < 34.5))
                {
                    $distanceFromMid = abs($pick_value - (6.5+$roundAdj));
                    $weight = (4-$distanceFromMid) / 4;

                    $value = $value + ($future_pick_value_arr[$pick_year_value." Mid ".$verbiage[$pick->round]] * $weight);
                }
                if ($pick_value >= 6.5 && $pick_value < 14.5 || ($pick_value >= 18.5 && $pick_value < 26.5) || ($pick_value >= 30.5))
                {
                    $distanceFromLate = abs($pick_value - (10.5+$roundAdj));
                    $weight = (4-$distanceFromLate) / 4;

                    $value = $value + ($future_pick_value_arr[$pick_year_value." Late ".$verbiage[$pick->round]] * $weight);
                }
                $pick->value = $value;
            }
        }

        $picks = collect($picks);

        $picks = $picks->sortBy(function($pick)
        {
            return $pick->value;
        });

        $pickValues = [];

        foreach ($picks as $pick)
        {
            $pickValue[$pick->year]["count"] += 1;
            $pickValue["total"]["count"] += 1;

            $pickValues[$pick->year][] = $pick->value;
        }

        foreach($pickValues as $year => $values)
        {
            $pickIndex = 0;
            $playerIndex = 0;
            $nextPickIndex = 1;
            $nextPlayerIndex = 1;

            while ($pickIndex < sizeOf($values))
            {
                if ($values[$pickIndex] <= $playerValues[$playerIndex])
                {
                    $pickIndex++;
                } else {
                    if (!empty($values[$nextPickIndex]) && !empty($playerValues[$nextPlayerIndex]) && $values[$nextPickIndex] <= $playerValues[$nextPlayerIndex])
                    {
                        $pickIndex++;
                    } else {
                        $pickValue[$year]["value"] += $values[$pickIndex] - $playerValues[$playerIndex];
                        $pickValue["total"]["value"] += $values[$pickIndex] - $playerValues[$playerIndex];
                        $pickIndex++;
                        $playerIndex++;
                        $nextPickIndex++;
                        $nextPlayerIndex++;
                    }
                }
            }
        }

        // echo "<pre>";
        // print_r($pickValue);
        // echo "</pre>";


        return $pickValue;
    }

    public function getExpandedTeamData()
    {
        $team = $this;
        $player_array = $team->getTeamPlayers();
        $team->players = json_encode($player_array);

        $pos_array = [];
        foreach($player_array as $player)
        {
            if ($player->position == "K")
            {
                unset($player);
                continue;
            }
            if (!isset($pos_array[$player->position]))
            {
                $pos_array[$player->position] = [];
            }
            $pos_array[$player->position][] = $player;
        }
        $team->pos_array = json_encode($pos_array);

        $team_value = $team->getTeamValue();

        $picks = SleeperDraftPick::getActiveDraftPicksForTeam($team->id);

        $team->draft_picks = json_encode($picks);

        $draftValue = SleeperTeam::computeDraftValue($picks, $this->getTeamPlayersByValue());
        $team_value["draft"] = $draftValue;
        $team_value["total"]["value"] += $team_value["draft"]["total"]["value"];
        $team->value = json_encode($team_value);

        return $team;
    }

    public function fullRosterValueComputation($picks, $players)
    {
        $playerValues = [];
        foreach($players as $player)
        {
            $playerValues[] = $player->player_value;
        }
        asort($playerValues);

        $pickValue = [
            "total" => ["count" => 0, "value" => 0]
        ];

        // create future picks array
        $future_pick_value_arr = [];
        $verbiage = [
            1 => "1st",
            2 => "2nd",
            3 => "3rd"
        ];

        $futurePicks = DB::table("player_ktc_value")
            ->select("player_ktc_value.*")
            ->whereNull('player_id')
            ->orderBy('player_name', "ASC")
            ->skip(12)
            ->take(24)
            ->get();

        foreach ($futurePicks as $pick)
        {
            $future_pick_value_arr[$pick->player_name] = $pick->player_value;
        }

        // $pick_array = [];

        foreach($picks as $pick)
        {
            if (!isset($pickValue[$pick->year]))
            {
                $pickValue[$pick->year] = ["count" => 0, "value" => 0];
            }

            $pick_year_value = ($pick->year > 2025) ? 2025 : $pick->year;

            // Get pick value
            if (!empty($pick->pick))
            {
                $skip = ($pick->pick-1)+(($pick->round-1)*12);

                $value = DB::table('sleeper_players')
                    ->select(DB::raw("player_ktc_value.player_value"))
                    ->join("player_ktc_value","player_ktc_value.player_id","=","sleeper_players.id")
                    ->whereNull("years_exp")
                    ->orderBy('player_ktc_value.player_value','desc')
                    ->skip($skip)
                    ->take(1)
                    ->get();

                $pick->value = $value[0]->player_value;
            } else {
                $teamComparable = SleeperDraftPick::where("original_owner_id",$pick->original_owner_id)
                    ->where("round",$pick->round)
                    ->whereNotNull("pick")
                    ->first();

                // $yearConversion -- value used to average out pick value
                // $valueToAverage -- pick number + $yearConversion

                $yearConversion = (($pick->year)-2023)*(6+(($pick->round-1)*12));
                $valueToAverage = $yearConversion + ($teamComparable->pick + (($teamComparable->round-1)*12));
                $pick_value = $valueToAverage / (($pick->year)-2022);

                $value = 0;
                $roundAdj = 12*($pick->round-1);
                if ($pick_value < 6.5 || ($pick_value >= 10.5 && $pick_value < 18.5) || ($pick_value >= 22.5 && $pick_value < 30.5))
                {
                    $distanceFromEarly = abs($pick_value - (2.5+$roundAdj));
                    $weight = (4-$distanceFromEarly) / 4;

                    $value = $value + ($future_pick_value_arr[$pick_year_value." Early ".$verbiage[$pick->round]] * $weight);
                }
                if ($pick_value >= 2.5 && $pick_value < 10.5 || ($pick_value >= 14.5 && $pick_value < 22.5) || ($pick_value >= 26.5 && $pick_value < 34.5))
                {
                    $distanceFromMid = abs($pick_value - (6.5+$roundAdj));
                    $weight = (4-$distanceFromMid) / 4;

                    $value = $value + ($future_pick_value_arr[$pick_year_value." Mid ".$verbiage[$pick->round]] * $weight);
                }
                if ($pick_value >= 6.5 && $pick_value < 14.5 || ($pick_value >= 18.5 && $pick_value < 26.5) || ($pick_value >= 30.5))
                {
                    $distanceFromLate = abs($pick_value - (10.5+$roundAdj));
                    $weight = (4-$distanceFromLate) / 4;

                    $value = $value + ($future_pick_value_arr[$pick_year_value." Late ".$verbiage[$pick->round]] * $weight);
                }
                $pick->value = $value;
            }
        }

        $picks = collect($picks);

        $picks = $picks->sortBy(function($pick)
        {
            return $pick->value;
        });

        $pickValues = [];

        foreach ($picks as $pick)
        {
            $pickValue[$pick->year]["count"] += 1;
            $pickValue["total"]["count"] += 1;

            $pickValues[$pick->year][] = $pick->value;
        }

        foreach($pickValues[$pick->year] as $year => $values)
        {
            $pickIndex = 0;
            $playerIndex = 0;
            $nextPickIndex = 1;
            $nextPlayerIndex = 1;

            while ($pickIndex < sizeOf($values))
            {
                if ($values[$pickIndex] <= $playerValues[$playerIndex])
                {
                    $pickIndex++;
                } else {
                    if (!empty($values[$nextPickIndex]) && !empty($playerValues[$nextPlayerIndex]) && $values[$nextPickIndex] <= $playerValues[$nextPlayerIndex])
                    {
                        $pickIndex++;
                    } else {
                        $pickValue[$year]["value"] += ($values[$pickIndex] - $playerValues[$playerIndex]);
                        $pickValue["total"]["value"] += ($values[$pickIndex] - $playerValues[$playerIndex]);
                        $pickIndex++;
                        $playerIndex++;
                        $nextPickIndex++;
                        $nextPlayerIndex++;
                    }
                }
            }
        }

        return $pickValue;
    }
}
