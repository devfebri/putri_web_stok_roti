<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Get all users
    public function indexApi()
    {
        $users = User::where('status', '!=', 9)->get();
        return response()->json(['status' => true, 'data' => $users]);
    }

    // Get user by ID
    public function showApi($id)
    {
        $user = User::find($id);

        if (!$user || $user->status == 9) {
            return response()->json(['status' => false, 'message' => 'User tidak ditemukan'], 404);
        }
        return response()->json(['status' => true, 'data' => $user]);
    }

    // Create new user
    public function storeApi(Request $request)
    {
        // Validasi data
        $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'password_confirmation' => 'required|same:password',
            'phone' => 'required|string|min:10|max:15',
            'address' => 'nullable|string|max:500',
            'role' => 'required|string|in:admin,manager,employee,frontliner,kepalabakery,kepalatokokios,customer',
        ]);

        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->role = $request->role;
            $user->status = 1; // Active status

            if ($user->save()) {
                // Remove password from response
                $user->makeHidden(['password']);
                
                return response()->json([
                    'status' => true,
                    'message' => 'User berhasil ditambah',
                    'data' => $user
                ], 201);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User gagal ditambah'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update user
    public function updateApi(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user || $user->status == 9) {
            return response()->json(['status' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        // Validasi data
        $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($id),
            ],
            'password' => 'nullable|string|min:6',
            'password_confirmation' => 'nullable|same:password',
            'phone' => 'required|string|min:10|max:15',
            'address' => 'nullable|string|max:500',
            'role' => 'required|string|in:admin,manager,employee,frontliner,kepalabakery,kepalatokokios,customer',
        ]);

        try {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->role = $request->role;

            // Only update password if provided
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
            
            // Remove password from response
            $user->makeHidden(['password']);
            
            return response()->json([
                'status' => true, 
                'message' => 'User berhasil diupdate', 
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete user (soft delete)
    public function destroyApi($id)
    {
        $user = User::find($id);
        if (!$user || $user->status == 9) {
            return response()->json(['status' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        try {
            // Soft delete by setting status to 9
            $user->update(['status' => 9]);
            
            return response()->json([
                'status' => true, 
                'message' => 'User berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
