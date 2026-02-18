<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // 1. REGISTER (Create new user)
    public function register(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'student_id' => 'required|string|max:20', // New Field
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'student_id' => $validated['student_id'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role' => 'student', // Default role is student
        'is_approved' => false, // Explicitly set to false
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
    if ($user->role === 'student' && $user->is_approved == false) {
        return response()->json(['message' => 'Your account is pending approval. Please wait for an Admin.'], 403);
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
public function pendingUsers()
{
    // Get all users who are NOT approved
    $users = User::where('is_approved', false)->get();
    return response()->json($users);
}

// 5. APPROVE USER (For Admin Button)
public function approveUser($id)
{
    $user = User::find($id);
    if ($user) {
        $user->is_approved = true;
        $user->save();
        return response()->json(['message' => 'User approved successfully!']);
    }
    return response()->json(['message' => 'User not found'], 404);
}
}