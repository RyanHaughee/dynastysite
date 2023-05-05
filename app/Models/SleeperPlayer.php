<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SleeperPlayer extends Model
{
    use HasFactory;

    public static function getSleeperTableColumns()
    {
        $columns = [
            'first_name',
            'last_name',
            'full_name',
            'position',
            'team',
            'age',
            'college',
            'fantasy_positions',
            'weight',
            'height',
            'number',
            'years_exp',
            'status'
        ];
        return $columns;
    }
}
