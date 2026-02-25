<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    // 1. REGISTER (Create new user)
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // student ID format ##-####-###
            'student_id' => ['required','string','max:20','regex:/^\d{2}-\d{4}-\d{3}$/'],
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            // require front-of-ID image during registration
            'id_card_image' => 'required|image|max:2048',
        ]);

        // store file on public disk; path will be used when displaying later
        $path = $request->file('id_card_image')->store('id_cards', 'public');

        $user = User::create([
            'name' => $validated['name'],
            'student_id' => $validated['student_id'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student', // Default role is student
            'is_approved' => false, // Explicitly set to false
            'id_card_image' => $path,
        ]);

        // Note: We DO NOT create a token here anymore.
        return response()->json([
            'message' => 'Registration successful! Please wait for Admin approval before logging in.',
        ], 201);
    }

    // 2. LOGIN (Check credentials)
    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid login credentials'], 401);
    }

    // --- NEW LOGIC: SEPARATE ADMIN VS STUDENT ---

    // If it is a STUDENT, check if they are approved
    if ($user->role === 'student') {
    if ($user->status === 'rejected') {
        return response()->json([
            'message' => "Your account was rejected. Reason: " . $user->rejection_reason
        ], 403);
    }
    if ($user->is_approved == false) {
        return response()->json(['message' => 'Your account is pending approval.'], 403);
    }
}

    // If it is an ADMIN, they skip the check and login immediately.

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'role' => $user->role, // Send role to frontend so we can redirect them
        'user' => $user
    ]);
}
    
    // 3. LOGOUT (Destroy token)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    // 4. PROFILE (Get current user)
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    // 5. UPDATE PROFILE (name, email, phone)
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'preferences' => 'nullable|array',
        ];

        $validated = $request->validate($rules);

        // If preferences present, merge them into validated so mass assignment will save
        $user->update($validated);

        return response()->json(['message' => 'Profile updated', 'user' => $user]);
    }

    // 6. CHANGE PASSWORD
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    // 4. GET PENDING USERS (For Admin Dashboard)
// 1. Update Pending Users (only status 'pending')
public function pendingUsers()
{
    $users = User::where('status', 'pending')
                 ->where('is_approved', false)
                 ->get();
    return response()->json($users);
}

// 2. Add Rejected Users function
public function rejectedUsers()
{
    $users = User::where('status', 'rejected')->get();
    return response()->json($users);
}

// 3. Update the Reject function (ensure it sets the status)
public function rejectUser(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $user = User::find($id);
        if ($user) {
            $user->status = 'rejected';
            $user->is_approved = false;
            $user->rejection_reason = $request->reason;
            $user->save();

            // 4. Create Activity Log
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'rejected',
                'target_model' => 'User',
                'target_id' => $id,
                'description' => "Rejected user: {$user->name}. Reason: {$request->reason}"
            ]);

            return response()->json(['message' => 'User rejected and logged.']);
        }
        return response()->json(['message' => 'User not found'], 404);
    }

    public function approveUser($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->is_approved = true;
            $user->status = 'approved'; // Sync status
            $user->save();

            // 3. Create Activity Log
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'approved',
                'target_model' => 'User',
                'target_id' => $id,
                'description' => "Approved user: {$user->name} ({$user->email})"
            ]);

            return response()->json(['message' => 'User approved successfully!']);
        }
        return response()->json(['message' => 'User not found'], 404);
    }
}