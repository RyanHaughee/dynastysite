<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SleeperLeague extends Model
{
    use HasFactory;

    // **************************************************** //
    // ** These columns are the columns to be set from  *** //
    // ****************** Sleeper API  ******************** //
    // **************************************************** //

    public static function getTableColumns()
    {
        $columns = [
            'total_rosters',
            'status',
            'sport',
            'settings',
            'season_type',
            'season',
            'scoring_settings',
            'roster_positions',
            'name',
            'avatar'
        ];
        return $columns;
    }

    public function getLeagueTeams()
    {
        $leagueId = $this->id;
        $teams = SleeperTeam::where("league_id",$leagueId)->get();

        return $teams;
    }

    public function getRosterParameters()
    {
        $roster_settings = array_count_values(json_decode($this->roster_positions,true));
        return $roster_settings;
    }
}
