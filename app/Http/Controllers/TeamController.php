<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\SleeperTeam;
use Illuminate\Http\Request;
use App\Models\SleeperDraftPick;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    public function getTeamValue($team_id)
    {
        $team = SleeperTeam::find($team_id);
        $team->getTeamValue();
    }

    public function getExpandedTeamData($team_id)
    {
        try {
            $team = SleeperTeam::find($team_id);
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

            $picks = SleeperDraftPick::where('team_id',$team->id)->orderby('round','asc')->get();
            $draftValue = SleeperTeam::computeDraftValue($picks);
            $team_value["draft"] = $draftValue;
            $team_value["total"]["value"] += $team_value["draft"]["total"]["value"];
            $team->value = json_encode($team_value);
        } catch(Throwable $e) {
            Log::error($e);
            return [
                "success" => false,
                "team" => "Something went wrong fetching advanced team data."
            ];
        }

        return [
            "success" => true,
            "team" => $team
        ];
    }

    public function pickValueCalculation($league_id)
    {
        $picks = SleeperDraftPick::where('round',1)
            ->where('pick',1)
            ->where('league_id',1)
            ->get();
        $draftValue = SleeperTeam::computeDraftValue($picks);
    }
}
