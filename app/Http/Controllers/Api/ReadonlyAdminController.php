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
        try {
            $username = $request->input('username');
            $password = $request->input('password');

            // Pronađi readonly admina po username
            $admin = DB::table('admins')->where('username', $username)->first();

            if (!$admin) {
                return response()->json(['message' => 'Nema korisnika'], 401);
            }

            if ($admin->username !== 'control') {
                return response()->json(['message' => 'Nije readonly user'], 401);
            }

            if (!Hash::check($password, $admin->password)) {
                return response()->json(['message' => 'Pogrešna lozinka'], 401);
            }

            $token = base64_encode('readonly-' . time());
            return response()->json([
                'token' => $token,
                'message' => 'Login successful'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                //'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}