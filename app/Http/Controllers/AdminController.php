<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    public function index()
    {
        $admins = Admin::all();
        return response()->json($admins, 200);
    }

    public function show($id)
    {
        $admin = Admin::findOrFail($id);
        return response()->json($admin, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:admins|max:255',
            'password' => 'required|string|min:6',
        ]);

        $admin = Admin::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        return response()->json($admin, 201);
    }

    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        $validated = $request->validate([
            'username' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:admins,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $admin->update($validated);
        return response()->json($admin, 200);
    }

    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);
        $admin->delete();
        return response()->json(['message' => 'Admin deleted successfully'], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('admin-panel')->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => $admin,
            'message' => 'Login successful',
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function testDnevniFinansijski()
    {
        return response()->json(['status' => 'ok', 'message' => 'Test dnevni finansijski izvještaj']);
    }

    /**
     * Blokira slotove za određeni dan (dinamička tabela) -- PREMA ID-jevima slotova
     */
    public function blockSlots(Request $request)
    {
        \Log::info('blockSlots start', ['request' => $request->all()]);
        try {
            $data = $request->validate([
                'date' => 'required|date',
                'slots' => 'required|array|min:1',
                'slots.*' => 'integer'
            ]);

            $table = str_replace('-', '', $data['date']);

            if (!Schema::hasTable($table)) {
                return response()->json(['success' => false, 'message' => "Tabela za dan $table ne postoji."], 404);
            }

            $affected = [];
            foreach ($data['slots'] as $slotId) {
                $updated = DB::table($table)
                    ->where('time_slot_id', $slotId)
                    ->update(['available' => 0]);

                if ($updated) {
                    $affected[] = $slotId;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Slotovi su blokirani.',
                'blocked_slots' => $affected,
            ]);
        } catch (\Throwable $e) {
            \Log::error('blockSlots error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}