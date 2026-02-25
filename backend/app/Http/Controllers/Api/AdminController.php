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
    // simple helper to assert the authenticated user is the admin
    protected function ensureAdmin()
    {
        $user = auth()->user();
        if (! $user || $user->email !== 'admin@campus.edu') {
            abort(403, 'Unauthorized');
        }
    }

    // Get all users (optionally include status)
    public function getUsers()
    {
        $this->ensureAdmin();
        return response()->json(User::all());
    }

    // Get users waiting for approval
    public function getPendingUsers()
    {
        $this->ensureAdmin();
        return response()->json(
            User::where('status', 'pending')->get()
        );
    }

    // approve a pending student
    public function approveUser($id)
    {
        $this->ensureAdmin();
        $user = User::findOrFail($id);
        $user->status = 'approved';
        $user->save();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'approved',
            'target_model' => 'User',
            'target_id' => $id,
            'description' => "Approved user: {$user->name} ({$user->email})"
        ]);

        return response()->json(['message' => 'User approved successfully', 'user' => $user]);
    }

    // reject a pending student
    public function rejectUser($id)
    {
        $this->ensureAdmin();
        $user = User::findOrFail($id);
        $user->status = 'rejected';
        $user->save();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'rejected',
            'target_model' => 'User',
            'target_id' => $id,
            'description' => "Rejected user: {$user->name} ({$user->email})"
        ]);

        return response()->json(['message' => 'User rejected', 'user' => $user]);
    }

    // Delete a user
    // Delete a user
public function deleteUser($id)
{
    $this->ensureAdmin();
    $user = User::findOrFail($id);
    
    // Save the info for the log before deleting
    $userName = $user->name;
    $userEmail = $user->email;

    // --- ADD THIS LINE ---
    $user->delete(); 

    // Log the action
    ActivityLog::create([
        'user_id' => auth()->id(),
        'action' => 'deleted',
        'target_model' => 'User',
        'target_id' => $id,
        'description' => "Deleted user: {$userName} ({$userEmail})"
    ]);

    return response()->json(['message' => 'User deleted successfully']);
}

    // Create a new space
    public function createSpace(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
        ]);

        // Set occupancy to 0 and status will be calculated by the Space model
        $space = Space::create([
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'occupancy' => 0,
            'status' => 'low',
        ]);

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
