<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProfileController extends Controller
{
    protected ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function edit(): View
    {
        $user = auth()->user()->load(['company', 'roles']);

        return view('profile.edit', compact('user'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        try {
            $this->profileService->updateProfile($request->user(), $request->validated());

            return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
        } catch (\Throwable $th) {
            Log::error('Gagal memperbarui profil: ' . $th->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal memperbarui profil.')
                ->withInput();
        }
    }
}
