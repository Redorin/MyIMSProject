<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Space;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function index()
    {
        $spaces = Space::all();
        // Add occupancy_percentage to each space
        $spaces = $spaces->map(function($space) {
            $space->occupancy_percentage = $space->capacity > 0 ? round(($space->occupancy / $space->capacity) * 100) : 0;
            // Calculate status based on occupancy percentage
            $percentage = $space->occupancy_percentage;
            if ($percentage >= 80) {
                $space->status = 'high';
            } elseif ($percentage >= 50) {
                $space->status = 'medium';
            } else {
                $space->status = 'low';
            }
            return $space;
        });
        return response()->json($spaces);
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
