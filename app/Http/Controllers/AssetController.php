<?php

namespace App\Http\Controllers;

use App\Http\Resources\AssetResource;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'usd_balance' => (float) $user->balance,
            'assets' => AssetResource::collection($user->assets),
        ]);
    }
}
