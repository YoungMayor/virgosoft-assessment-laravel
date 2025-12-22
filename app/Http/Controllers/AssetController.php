<?php

namespace App\Http\Controllers;

class AssetController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        return response()->json([
            'usd_balance' => (float) $user->balance,
            'assets' => $user->assets->map(function ($asset) {
                return [
                    'symbol' => $asset->symbol,
                    'amount' => (float) $asset->amount,
                    'locked_amount' => (float) $asset->locked_amount,
                ];
            }),
        ]);
    }
}
