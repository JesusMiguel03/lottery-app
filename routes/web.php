<?php

use App\Models\Client;
use App\Models\Lottery;
use App\Models\Ticket;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $lotteries = Lottery::where('finished_at', null)
        ->orWhere('finished_at', '>=', now())
        ->take(6)
        ->get();

    $winners = Ticket::query()
        ->where('winner', true)
        ->with(['client', 'lottery', 'prize'])
        ->orderBy('order')
        ->limit(6)
        ->get();

    $stats = [
        'lotteries' => Lottery::count(),
        'tickets' => Ticket::whereNotNull('client_id')->count(),
        'clients' => Client::count(),
        'winners' => Ticket::where('winner', true)->count(),
    ];

    return view('landing', compact('lotteries', 'winners', 'stats'));
});


