<div class="mx-auto w-full max-w-md">
    <h1 class="mb-1 text-2xl font-semibold tracking-tight text-zinc-900">
        {{ __('Sign in') }}
    </h1>
    <p class="mb-6 text-sm text-zinc-600">
        {{ __('Use your account to access the dashboard.') }}
    </p>

    <form wire:submit.prevent="login" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5">
        {{-- Email --}}
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-zinc-700">
                {{ __('Email') }}
            </label>
            <input
                id="email"
                type="email"
                wire:model.defer="email"
                autocomplete="email"
                required
                class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-900
                       placeholder-zinc-400 focus:border-indigo-500 focus:ring-indigo-500"
            />
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-zinc-700">
                {{ __('Password') }}
            </label>
            <input
                id="password"
                type="password"
                wire:model.defer="password"
                autocomplete="current-password"
                required
                class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-900
                       placeholder-zinc-400 focus:border-indigo-500 focus:ring-indigo-500"
            />
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700">
                <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                <span>{{ __('Remember me') }}</span>
            </label>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white
                       hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
                <span wire:loading.remove>{{ __('Sign in') }}</span>
                <span class="inline-flex items-center gap-2" wire:loading>
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 004 12z"/>
                    </svg>
                    {{ __('Signing in...') }}
                </span>
            </button>
        </div>
    </form>
</div>
