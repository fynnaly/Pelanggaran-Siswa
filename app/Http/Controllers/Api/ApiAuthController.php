<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiAuthController extends Controller
{
    /**
     * Login Android → token Bearer (Sanctum).
     *
     * Pesan gagal sengaja identik untuk email tak dikenal dan password salah
     * supaya endpoint tidak dipakai menebak daftar email (user enumeration).
     * Token lama dengan device_name yang sama dicabut agar tidak menumpuk.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $deviceName = $credentials['device_name'] ?? 'android';
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName, ['*'], now()->addDays(30));

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $token->plainTextToken,
            'expires_in' => 30 * 24 * 3600,
            'user' => $this->userPayload($user),
        ]);
    }

    /** Profil pemilik token + role — dipakai app untuk menentukan menu. */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    /** Cabut token aktif (logout) — token langsung mati, tidak bisa dipakai ulang. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token dicabut.']);
    }

    /**
     * Ganti password sendiri — semua role. Wajib password lama yang benar;
     * token aktif TIDAK dicabut agar sesi di perangkat ini tetap berjalan.
     */
    public function changePassword(Request $request): JsonResponse
    {
        if ($request->user()?->hasRole('siswa')) {
            return response()->json([
                'message' => 'Akun siswa bersifat hanya-baca. Hubungi petugas sekolah untuk perubahan.',
            ], 403);
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama salah.'],
            ]);
        }

        $user->password = $validated['password'];
        $user->save();

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    /** Bentuk data user yang aman dikirim ke klien — tanpa password/remember_token. */
    private function userPayload(User $user): array
    {
        $user->loadMissing('student.currentEnrollment.schoolClass');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'nip' => $user->hasRole('siswa') ? null : $user->nip,
            'roles' => $user->getRoleNames(),
            'is_active' => $user->is_active,
            'avatar_path' => $user->avatar_path,
            'avatar_url' => $user->avatar_url,
            'siswa' => $user->student ? [
                'id' => $user->student->id,
                'nisn' => $user->student->nisn,
                'nama' => $user->student->full_name,
                'kelas' => $user->student->currentEnrollment?->schoolClass?->name,
            ] : null,
        ];
    }
}
