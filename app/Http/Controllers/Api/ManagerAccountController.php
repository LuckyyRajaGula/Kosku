<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ManagerAccountController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorizeOwner();

        $data = User::query()
            ->where('role', 'pengelola')
            ->select('id_user', 'nama', 'username', 'email', 'no_telpon', 'role', 'status_akun')
            ->orderBy('nama')
            ->get();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeOwner();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:user,username'],
            'email' => ['required', 'email', 'max:100', 'unique:user,email'],
            'password' => ['required', 'string', 'min:8'],
            'no_telpon' => ['nullable', 'string', 'max:15'],
        ]);

        $manager = User::query()->create([
            'nama' => $validated['nama'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'no_telpon' => $validated['no_telpon'] ?? null,
            'role' => 'pengelola',
            'status_akun' => 'Aktif',
        ]);

        return response()->json([
            'message' => 'Akun pengelola berhasil dibuat.',
            'data' => $manager->only(['id_user', 'nama', 'username', 'email', 'no_telpon', 'role', 'status_akun']),
        ], 201);
    }

    public function update(Request $request, int $idUser): JsonResponse
    {
        $this->authorizeOwner();

        $manager = User::query()->where('id_user', $idUser)->where('role', 'pengelola')->firstOrFail();

        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:100'],
            'username' => ['sometimes', 'required', 'string', 'max:50', 'unique:user,username,'.$manager->id_user.',id_user'],
            'email' => ['sometimes', 'required', 'email', 'max:100', 'unique:user,email,'.$manager->id_user.',id_user'],
            'password' => ['nullable', 'string', 'min:8'],
            'no_telpon' => ['nullable', 'string', 'max:15'],
        ]);

        if (array_key_exists('password', $validated) && $validated['password']) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $manager->fill($validated)->save();

        return response()->json([
            'message' => 'Akun pengelola berhasil diperbarui.',
            'data' => $manager->only(['id_user', 'nama', 'username', 'email', 'no_telpon', 'role', 'status_akun']),
        ]);
    }

    public function updateStatus(Request $request, int $idUser): JsonResponse
    {
        $this->authorizeOwner();

        $validated = $request->validate([
            'status_akun' => ['required', 'in:Aktif,Nonaktif'],
        ]);

        $manager = User::query()->where('id_user', $idUser)->where('role', 'pengelola')->firstOrFail();
        $manager->status_akun = $validated['status_akun'];
        $manager->save();

        return response()->json([
            'message' => 'Status akun pengelola berhasil diperbarui.',
            'data' => $manager->only(['id_user', 'nama', 'username', 'email', 'no_telpon', 'role', 'status_akun']),
        ]);
    }

    private function authorizeOwner(): void
    {
        $user = Auth::guard('api')->user();

        abort_unless($user && $user->role === 'pemilik', 403, 'Hanya pemilik yang bisa mengelola akun pengelola.');
    }
}
