<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Space;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function index()
    {
        return response()->json(Space::all());
    }

    public function update(Request $request, $id)
    {
        // 1. Find the space by ID
        $space = \App\Models\Space::find($id);

        if ($space) {
            // 2. Update the number
            $space->occupancy = $request->occupancy;
            $space->save();

            return response()->json($space);
        }

        return response()->json(['message' => 'Space not found'], 404);
    }
}
