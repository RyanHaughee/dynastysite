<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SleeperDraftPick extends Model
{
    public static function getActiveDraftPicksForTeam($team_id)
    {
        $team = SleeperTeam::find($team_id);
        $league = SleeperLeague::find($team->league_id);

        if ($league->status == "in_season")
        {
            $year = intval($league->season)+1;
        } else {
            $year = intval($league->season);
        }

        $picks = SleeperDraftPick::where('team_id',$team->id)
            ->where('year','>=',$year)
            ->orderby('round','asc')
            ->get();

        return $picks;
    }
}
