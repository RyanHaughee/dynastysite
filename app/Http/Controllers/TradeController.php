<?php

namespace App\Http\Controllers;

use App\Models\SleeperTrade;
use Illuminate\Http\Request;

class TradeController extends Controller
{
    public function getTradeScore($tradeId)
    {
        $trade = SleeperTrade::find($tradeId);
        $trade->calculateTradeScore();
    }
}
