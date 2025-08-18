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
        try {
            $users = User::where('status', '!=', 9)->orderBy('created_at', 'desc')->get();
            
            // Remove password from response
            $users = $users->map(function ($user) {
                $user->makeHidden(['password']);
                return $user;
            });
            
            return response()->json([
                'status' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get user by ID
    public function showApi($id)
    {
        try {
            $user = User::find($id);

            if (!$user || $user->status == 9) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Remove password from response
            $user->makeHidden(['password']);
            
            return response()->json([
                'status' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Create new user
    public function storeApi(Request $request)
    {
        try {
            // Validasi data
            $validated = $request->validate([
                'name' => 'required|string|min:2|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|string|in:admin,frontliner,kepalabakery,kepalatokokios,pimpinan',
                'kepalatokokios_id' => 'nullable|exists:users,id',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            // Validasi kepalatokokios_id jika role frontliner atau admin
            if (in_array($validated['role'], ['frontliner', 'admin']) && !empty($validated['kepalatokokios_id'])) {
                $kepalaTokokios = User::where('id', $validated['kepalatokokios_id'])
                                    ->where('role', 'kepalatokokios')
                                    ->where('status', '!=', 9)
                                    ->first();
                
                if (!$kepalaTokokios) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Kepala Toko Kios tidak valid atau tidak ditemukan'
                    ], 400);
                }
            }

            // Generate username from email if not provided
            $emailPart = explode('@', $validated['email'])[0];
            $username = $request->username ?? $emailPart;
            
            // Make sure username is unique
            $originalUsername = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }

            $user = new User();
            $user->name = $validated['name'];
            $user->username = $username;
            $user->email = $validated['email'];
            $user->password = Hash::make($validated['password']);
            $user->role = $validated['role'];
            $user->kepalatokokios_id = $validated['kepalatokokios_id'] ?? null;
            $user->phone = $validated['phone'] ?? null;
            $user->address = $validated['address'] ?? null;
            $user->status = isset($validated['is_active']) && $validated['is_active'] ? 1 : 0;

            if ($user->save()) {
                // Remove password from response
                $user->makeHidden(['password']);
                
                return response()->json([
                    'status' => true,
                    'message' => 'User created successfully',
                    'data' => $user
                ], 201);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to create user'
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update user
    public function updateApi(Request $request, $id)
    {
        try {
            $user = User::find($id);
            if (!$user || $user->status == 9) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Validasi data
            $validated = $request->validate([
                'name' => 'required|string|min:2|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($id),
                ],
                'username' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('users')->ignore($id),
                ],
                'password' => 'nullable|string|min:6',
                'role' => 'required|string|in:admin,frontliner,kepalabakery,kepalatokokios,pimpinan',
                'kepalatokokios_id' => 'nullable|exists:users,id',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'is_active' => 'boolean'
            ]);

            // Validasi kepalatokokios_id jika role frontliner atau admin
            if (in_array($validated['role'], ['frontliner', 'admin']) && !empty($validated['kepalatokokios_id'])) {
                $kepalaTokokios = User::where('id', $validated['kepalatokokios_id'])
                                    ->where('role', 'kepalatokokios')
                                    ->where('status', '!=', 9)
                                    ->first();
                
                if (!$kepalaTokokios) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Kepala Toko Kios tidak valid atau tidak ditemukan'
                    ], 400);
                }
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            $user->kepalatokokios_id = $validated['kepalatokokios_id'] ?? null;
            
            // Update phone and address
            $user->phone = $validated['phone'] ?? null;
            $user->address = $validated['address'] ?? null;
            
            // Update username if provided
            if (!empty($validated['username'])) {
                $user->username = $validated['username'];
            }
            
            if (isset($validated['is_active'])) {
                $user->status = $validated['is_active'] ? 1 : 0;
            }

            // Only update password if provided
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            
            // Remove password from response
            $user->makeHidden(['password']);
            
            return response()->json([
                'status' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete user (soft delete)
    public function destroyApi($id)
    {
        try {
            $user = User::find($id);
            if (!$user || $user->status == 9) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Prevent deleting the last admin user
            if ($user->role === 'admin') {
                $adminCount = User::where('role', 'admin')
                    ->where('id', '!=', $id)
                    ->where('status', '!=', 9)
                    ->count();
                if ($adminCount === 0) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Cannot delete the last admin user'
                    ], 422);
                }
            }

            // Soft delete by setting status to 9
            $user->update(['status' => 9]);
            
            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get list of Kepala Toko Kios untuk dropdown
    public function getKepalaTokokios()
    {
        try {
            $kepalaTokokios = User::where('role', 'kepalatokokios')
                                 ->where('status', '!=', 9)
                                 ->select('id', 'name', 'email')
                                 ->orderBy('name')
                                 ->get();
            
            return response()->json([
                'status' => true,
                'data' => $kepalaTokokios
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch Kepala Toko Kios',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
