<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UploadAvatarRequest;
use App\Http\Requests\Web\UpdateAccountProfileRequest;
use App\Http\Requests\Web\UpdateNotificationPreferencesRequest;
use App\Services\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function show(Request $request): View
    {
        return view('profile.show', [
            'user' => $request->user(),
            'sessions' => $this->profiles->activeSessions($request->user(), $request->session()->getId()),
        ]);
    }

    public function update(UpdateAccountProfileRequest $request): RedirectResponse
    {
        $this->profiles->update($request->user(), $request->validated());

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function avatar(UploadAvatarRequest $request): RedirectResponse
    {
        $this->profiles->uploadAvatar($request->user(), $request->file('avatar'));

        return back()->with('status', 'Avatar berhasil diperbarui.');
    }

    public function preferences(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        $this->profiles->updateNotificationPreferences($request->user(), $request->preferences());

        return back()->with('status', 'Preferensi notifikasi disimpan.');
    }

    public function password(ChangePasswordRequest $request): RedirectResponse
    {
        $this->profiles->changePassword($request->user(), $request->validated('password'));
        $request->session()->regenerate();

        return back()->with('status', 'Password berhasil diubah.');
    }
}
