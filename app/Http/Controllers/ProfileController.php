<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('siswa')) {
            abort(403, 'Siswa tidak diizinkan mengubah profil.');
        }

        $validated = $request->validated();
        $oldAvatarPath = $user->avatar_path;

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nip' => $validated['nip'] ?? null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            if ($avatarPath === false) {
                return back()
                    ->withErrors(['avatar' => 'Foto profil gagal disimpan. Silakan coba lagi.'])
                    ->withInput();
            }

            $user->avatar_path = $avatarPath;
        } elseif ($request->boolean('remove_avatar')) {
            $user->avatar_path = null;
        }

        $user->save();

        if ($oldAvatarPath && $user->avatar_path !== $oldAvatarPath && Storage::disk('public')->exists($oldAvatarPath)) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()->hasRole('siswa')) {
            abort(403, 'Siswa tidak diizinkan mengajukan penghapusan akun. Hubungi petugas sekolah.');
        }

        $request->validateWithBag('userDeletion', [
            'confirm_name' => ['required', 'string', 'in:'.$request->user()->name],
            'password' => ['required', 'current_password'],
        ], [
            'confirm_name.in' => 'Nama konfirmasi tidak cocok dengan nama akun Anda.',
            'confirm_name.required' => 'Ketik nama akun Anda untuk mengonfirmasi penghapusan.',
        ]);

        $user = $request->user();

        if ($user->deletion_requested_at) {
            return Redirect::route('profile.edit')->with('status', 'deletion-request-pending');
        }

        $user->deletion_requested_at = now();
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'deletion-requested');
    }
}
