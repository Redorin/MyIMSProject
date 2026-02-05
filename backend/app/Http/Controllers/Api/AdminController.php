<?php

namespace App\Http\Controllers\Api;

use App\Models\ActivityLog;
use App\Models\Space;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // Get all users
    public function getUsers()
    {
        return response()->json(User::all());
    }

    // Delete a user
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'target_model' => 'User',
            'target_id' => $id,
            'description' => "Deleted user: {$user->name} ({$user->email})"
        ]);

        return response()->json(['message' => 'User deleted successfully']);
    }

    // Create a new space
    public function createSpace(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'occupancy' => 'required|integer|min:0',
            'status' => 'required|in:low,medium,high',
        ]);

        $space = Space::create($validated);

        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'target_model' => 'Space',
            'target_id' => $space->id,
            'description' => "Created space: {$space->name}"
        ]);

        return response()->json(['message' => 'Space created successfully', 'space' => $space], 201);
    }

    // Delete a space
    public function deleteSpace($id)
    {
        $space = Space::findOrFail($id);
        $spaceName = $space->name;
        $space->delete();

        // Log the action
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'target_model' => 'Space',
            'target_id' => $id,
            'description' => "Deleted space: {$spaceName}"
        ]);

        return response()->json(['message' => 'Space deleted successfully']);
    }

    // Get activity logs
    public function getActivityLogs()
    {
        return response()->json(
            ActivityLog::with('user')->orderBy('created_at', 'desc')->limit(100)->get()
        );
    }

    // Dashboard stats
    public function getDashboardStats()
    {
        $totalSpaces = Space::count();
        $totalUsers = User::count();
        $totalCapacity = Space::sum('capacity');
        $totalOccupancy = Space::sum('occupancy');
        $avgOccupancyRate = $totalCapacity > 0 ? round(($totalOccupancy / $totalCapacity) * 100, 2) : 0;

        return response()->json([
            'total_spaces' => $totalSpaces,
            'total_users' => $totalUsers,
            'total_capacity' => $totalCapacity,
            'total_occupancy' => $totalOccupancy,
            'avg_occupancy_rate' => $avgOccupancyRate,
        ]);
    }
}
