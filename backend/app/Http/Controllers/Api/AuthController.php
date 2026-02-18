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
            'email' => 'required|string|email|max:255|unique:users',
            'student_id' => ['required','string','unique:users,student_id', 'regex:/^\d{2}-\d{4}-\d{3}$/'],
            'password' => 'required|string|min:8',
        ]);

        // create user with pending status
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'student_id' => $validated['student_id'],
            'password' => Hash::make($validated['password']),
            'status' => 'pending',
        ]);

        // do not auto-sign in; the account requires admin approval
        return response()->json([
            'message' => 'Account created successfully. Your account is pending approval by an administrator.',
            'user' => $user,
            'is_admin' => ($user->email === 'admin@campus.edu')
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

        // Check if user exists AND password is correct
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        // refuse login if not approved
        if ($user->status !== 'approved') {
            $msg = $user->status === 'rejected'
                    ? 'Your account request was rejected. Please contact support.'
                    : 'Your account is still pending approval.';

            return response()->json([
                'message' => $msg
            ], 403);
        }

        // Issue a new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'is_admin' => ($user->email === 'admin@campus.edu')
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
}