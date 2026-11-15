<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApiProfileController extends Controller
{
    /**
     * Return the authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->profilePayload($request->user())]);
    }

    /**
     * Update allowed profile fields and manage the authenticated user's avatar.
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $isStudent = $user->hasRole('siswa');

        $validated = $request->validate([
            'name' => $isStudent ? ['prohibited'] : ['sometimes', 'required', 'string', 'max:255'],
            'email' => $isStudent
                ? ['prohibited']
                : ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nip' => $isStudent ? ['prohibited'] : ['sometimes', 'nullable', 'string', 'max:40'],
            'avatar' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ]);

        $oldAvatarPath = $user->avatar_path;

        if (! $isStudent) {
            $user->fill(collect($validated)->only(['name', 'email', 'nip'])->all());

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
        }

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            if ($avatarPath === false) {
                return response()->json(['message' => 'Foto profil gagal disimpan. Silakan coba lagi.'], 500);
            }

            $user->avatar_path = $avatarPath;
        } elseif ($request->boolean('remove_avatar')) {
            $user->avatar_path = null;
        }

        $user->save();

        if ($oldAvatarPath && $user->avatar_path !== $oldAvatarPath && Storage::disk('public')->exists($oldAvatarPath)) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $this->profilePayload($user->fresh()),
        ]);
    }

    /**
     * Build the profile fields safe to expose to an authenticated client.
     *
     * @return array<string, mixed>
     */
    private function profilePayload(User $user): array
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
            'avatar_url' => $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null,
            'student' => $user->student ? [
                'id' => $user->student->id,
                'nis' => $user->student->nis,
                'nisn' => $user->student->nisn,
                'name' => $user->student->full_name,
                'class' => $user->student->currentEnrollment?->schoolClass?->name,
            ] : null,
        ];
    }
}
