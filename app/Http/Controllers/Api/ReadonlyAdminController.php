<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ReadonlyAdminController extends Controller
{
    public function login(Request $request)
    {
        \Log::info('Readonly admin login attempt', [
            'username' => $request->input('username'),
            'has_password' => !empty($request->input('password')),
            'headers' => $request->headers->all()
        ]);

        try {
            $username = $request->input('username');
            $password = $request->input('password');

            // Pronađi readonly admina po username
            $admin = \App\Models\Admin::where('username', $username)->first();

            \Log::info('Admin lookup result', [
                'username' => $username,
                'admin_found' => $admin ? true : false,
                'admin_username' => $admin ? $admin->username : null,
                'admin_id' => $admin ? $admin->id : null
            ]);

            if (!$admin) {
                \Log::warning('Readonly admin not found', ['username' => $username]);
                return response()->json(['message' => 'Nema korisnika'], 401);
            }

            if ($admin->username !== 'control') {
                \Log::warning('User is not readonly admin', [
                    'username' => $username,
                    'admin_username' => $admin->username
                ]);
                return response()->json(['message' => 'Nije readonly user'], 401);
            }

            \Log::info('Password check', [
                'username' => $username,
                'password_exists' => !empty($admin->password),
                'password_length' => strlen($admin->password ?? ''),
                'input_password' => $password,
                'stored_password_hash' => $admin->password,
                'hash_check_result' => Hash::check($password, $admin->password)
            ]);

            if (!Hash::check($password, $admin->password)) {
                \Log::warning('Invalid password for readonly admin', [
                    'username' => $username,
                    'input_password' => $password,
                    'stored_password_hash' => $admin->password
                ]);
                return response()->json(['message' => 'Pogrešna lozinka'], 401);
            }

            // Kreiraj Sanctum token
            $token = $admin->createToken('readonly-token')->plainTextToken;
            
            \Log::info('Readonly admin login successful', [
                'username' => $username,
                'admin_id' => $admin->id,
                'token_created' => !empty($token)
            ]);
            
            return response()->json([
                'token' => $token,
                'message' => 'Login successful'
            ]);
        } catch (\Throwable $e) {
            \Log::error('Readonly admin login error', [
                'username' => $request->input('username'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => $e->getMessage(),
                //'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}