<div class="w-full">
  <div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-semibold">Settings</h1>
    <p class="mt-1 text-sm text-zinc-600">Manage your profile and account settings</p>
  </div>

  @include('partials.settings-tabs')

  <div class="rounded-b-xl rounded-tr-xl border border-zinc-200 border-t-0 bg-white p-4 md:p-6">
    <h2 class="text-lg font-semibold">Password</h2>
    <p class="mt-1 text-sm text-zinc-600">Update your account password</p>

    <form wire:submit.prevent="updatePassword" class="mt-6 space-y-6">
      <div>
        <label for="current_password" class="mb-1 block text-sm font-medium text-zinc-700">Current password</label>
        <input id="current_password" type="password" wire:model.defer="current_password" required autocomplete="current-password"
               class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"/>
        @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
      </div>

      <div>
        <label for="password" class="mb-1 block text-sm font-medium text-zinc-700">New password</label>
        <input id="password" type="password" wire:model.defer="password" required autocomplete="new-password"
               class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"/>
        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
      </div>

      <div>
        <label for="password_confirmation" class="mb-1 block text-sm font-medium text-zinc-700">Confirm password</label>
        <input id="password_confirmation" type="password" wire:model.defer="password_confirmation" required autocomplete="new-password"
               class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"/>
      </div>

      <div class="flex items-center gap-4">
        <button type="submit"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          Save
        </button>
        <x-action-message on="password-updated" >Saved.</x-action-message>
      </div>
    </form>
  </div>
</div>
