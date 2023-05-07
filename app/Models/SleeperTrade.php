<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SleeperTrade extends Model
{
    use HasFactory;

    public function calculateTradeScore()
    {
        $trade_value = [
            $this->team1_roster_id => [
                "before" => [],
                "after" => []
            ],
            $this->team2_roster_id => [
                "before" => [],
                "after" => []
            ]
        ];

        // get teams
        $team1 = SleeperTeam::find($this->team1_roster_id);
        $team2 = SleeperTeam::find($this->team2_roster_id);

        // get player value before
        $trade_value[$this->team1_roster_id]["before"]["players"] = $team1->getTeamValue();
        $trade_value[$this->team2_roster_id]["before"]["players"] = $team2->getTeamValue();

        // get draft value before
        $team1_picks = SleeperDraftPick::where('team_id',$this->team1_roster_id)
            ->where('league_id',1)
            ->get();

        $team2_picks = SleeperDraftPick::where('team_id',$this->team2_roster_id)
            ->where('league_id',1)
            ->get();

        $trade_value[$this->team1_roster_id]["before"]["draft"] = SleeperTeam::computeDraftValue($team1_picks);
        $trade_value[$this->team2_roster_id]["before"]["draft"] = SleeperTeam::computeDraftValue($team2_picks);


        // $pieces = SleeperTradePiece::where("trade_id",$this->id)->get();
        // foreach($pieces as $piece)
        // {
        //     if (!empty($piece))
        // }
    }
}
