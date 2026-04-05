<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
#[Title('Profile Settings')]
class ProfileForm extends Component
{
    use WithFileUploads;

    #[Validate('required|string|min:2|max:100')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:500')]
    public string $bio = '';

    #[Validate('nullable|string|timezone')]
    public string $timezone = '';

    #[Validate('nullable|string|in:en,es,fr,de,ja')]
    public string $language = 'en';

    #[Validate('nullable|image|max:2048')]
    public $avatar;

    public string $successMessage = '';
    public string $errorMessage = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->bio = $user->bio ?? '';
        $this->timezone = $user->timezone ?? config('app.timezone');
        $this->language = $user->language ?? config('app.locale');
    }

    public function saveProfile(): void
    {
        $validated = $this->validate();

        try {
            $user = Auth::user();
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'bio' => $validated['bio'],
                'timezone' => $validated['timezone'],
                'language' => $validated['language'],
            ]);

            if ($this->avatar) {
                $path = $this->avatar->store('avatars', 'public');
                $user->update(['avatar_path' => $path]);
            }

            Log::info('User profile updated', ['user_id' => $user->id]);

            $this->successMessage = 'Your profile has been updated successfully.';
            session()->flash('success', 'Profile saved!');
        } catch (\Exception $e) {
            Log::error('Profile update failed', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);

            $this->errorMessage = 'We could not save your changes. Please try again.';
            session()->flash('error', 'Something went wrong while saving your profile.');
        }
    }

    public function deleteAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            \Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);

            Log::info('User avatar deleted', ['user_id' => $user->id]);

            $this->successMessage = 'Profile photo removed.';
        } else {
            $this->errorMessage = 'No profile photo to remove.';
        }
    }

    public function resendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->errorMessage = 'Your email is already verified.';
            return;
        }

        $user->sendEmailVerificationNotification();

        Log::info('Verification email resent', ['user_id' => $user->id, 'email' => $user->email]);

        $this->successMessage = 'Verification email sent! Check your inbox.';
        session()->flash('success', 'A verification link has been sent to your email address.');
    }

    public function deactivateAccount(): void
    {
        Log::warning('Account deactivation requested', ['user_id' => Auth::id()]);

        $user = Auth::user();
        $user->update(['active' => false]);

        session()->flash('info', 'Your account has been deactivated.');

        redirect()->to(route('login'));
    }

    public function render()
    {
        $maxUploadSize = config('filesystems.max_upload_size', 2048);
        $supportUrl = config('app.support_url', '#');

        return <<<HTML
        <div class="max-w-2xl mx-auto py-8 px-4">
            {{-- Flash Messages --}}
            @if (session()->has('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if (\$successMessage)
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                    {{ \$successMessage }}
                </div>
            @endif

            @if (\$errorMessage)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    {{ \$errorMessage }}
                </div>
            @endif

            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Profile Settings') }}</h1>
            <p class="text-gray-600 mb-8">{{ __('Manage your account details and preferences.') }}</p>

            <form wire:submit="saveProfile" class="space-y-6">
                {{-- Avatar --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Profile Photo') }}</label>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-gray-200 overflow-hidden">
                            @if (Auth::user()->avatar_path)
                                <img src="{{ Storage::url(Auth::user()->avatar_path) }}" alt="{{ __('Profile photo') }}" class="w-full h-full object-cover" />
                            @else
                                <span class="flex items-center justify-center w-full h-full text-gray-400 text-xl">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <input wire:model="avatar" type="file" accept="image/*" class="text-sm" />
                            <p class="text-xs text-gray-500 mt-1">{{ __('JPG, PNG or GIF. Max {$maxUploadSize}KB.') }}</p>
                        </div>
                        @if (Auth::user()->avatar_path)
                            <button
                                type="button"
                                wire:click="deleteAvatar"
                                wire:confirm="{{ __('Remove your profile photo?') }}"
                                class="text-sm text-red-600 hover:underline"
                            >
                                {{ __('Remove Photo') }}
                            </button>
                        @endif
                    </div>
                    @error('avatar')
                        <p class="mt-1 text-sm text-red-600">{{ \$message }}</p>
                    @enderror
                </div>

                {{-- Name --}}
                <div>
                    <label for="profile-name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Full Name') }}</label>
                    <input
                        wire:model="name"
                        id="profile-name"
                        type="text"
                        placeholder="{{ __('Enter your full name') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    />
                    <p class="mt-1 text-xs text-gray-500">{{ __('This is how your name will appear across the application.') }}</p>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ \$message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="profile-email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email Address') }}</label>
                    <input
                        wire:model="email"
                        id="profile-email"
                        type="email"
                        placeholder="you@example.com"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    />
                    <p class="mt-1 text-xs text-gray-500">{{ __("We'll send a verification email if you change this.") }}</p>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ \$message }}</p>
                    @enderror

                    @if (! Auth::user()->hasVerifiedEmail())
                        <div class="mt-2 flex items-center gap-2">
                            <span class="text-sm text-yellow-600">{{ __('Your email is not verified.') }}</span>
                            <button type="button" wire:click="resendVerification" class="text-sm text-blue-600 hover:underline">
                                {{ __('Resend Verification Email') }}
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Bio --}}
                <div>
                    <label for="profile-bio" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Bio') }}</label>
                    <textarea
                        wire:model="bio"
                        id="profile-bio"
                        rows="3"
                        placeholder="{{ __('Tell us a little about yourself...') }}"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Brief description for your profile. Maximum 500 characters.') }}</p>
                    @error('bio')
                        <p class="mt-1 text-sm text-red-600">{{ \$message }}</p>
                    @enderror
                </div>

                {{-- Timezone --}}
                <div>
                    <label for="profile-timezone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Timezone') }}</label>
                    <select
                        wire:model="timezone"
                        id="profile-timezone"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">{{ __('Select your timezone') }}</option>
                        <option value="America/New_York">{{ __('Eastern Time (US & Canada)') }}</option>
                        <option value="America/Chicago">{{ __('Central Time (US & Canada)') }}</option>
                        <option value="America/Denver">{{ __('Mountain Time (US & Canada)') }}</option>
                        <option value="America/Los_Angeles">{{ __('Pacific Time (US & Canada)') }}</option>
                        <option value="Europe/London">{{ __('London') }}</option>
                        <option value="Europe/Paris">{{ __('Paris') }}</option>
                        <option value="Asia/Tokyo">{{ __('Tokyo') }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Used for displaying dates and scheduling reminders.') }}</p>
                </div>

                {{-- Language --}}
                <div>
                    <label for="profile-language" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Language') }}</label>
                    <select
                        wire:model="language"
                        id="profile-language"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="en">{{ __('English') }}</option>
                        <option value="es">{{ __('Spanish') }}</option>
                        <option value="fr">{{ __('French') }}</option>
                        <option value="de">{{ __('German') }}</option>
                        <option value="ja">{{ __('Japanese') }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">{{ __('Choose your preferred language for the interface.') }}</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium"
                    >
                        {{ __('Save Changes') }}
                    </button>
                    <button
                        type="button"
                        wire:click="deactivateAccount"
                        wire:confirm="{{ __('Are you sure you want to deactivate your account? This action can be reversed by contacting support.') }}"
                        class="text-sm text-red-600 hover:underline"
                    >
                        {{ __('Deactivate Account') }}
                    </button>
                </div>
            </form>

            <p class="text-sm text-gray-400 mt-8 text-center">
                {{ __('Need help? Contact our support team.') }}
            </p>
        </div>
        HTML;
    }
}
