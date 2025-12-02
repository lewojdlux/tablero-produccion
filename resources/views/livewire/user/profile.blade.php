<div class="w-full">
  <div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-semibold">Settings</h1>
    <p class="mt-1 text-sm text-zinc-600">Manage your profile and account settings</p>
  </div>

  @include('partials.settings-tabs')

  {{-- Card de contenido: sin borde superior para unir con la pestaña activa --}}
  <div class="rounded-b-xl rounded-tr-xl border border-zinc-200 border-t-0 bg-white p-4 md:p-6">
    <h2 class="text-lg font-semibold">Profile</h2>
    <p class="mt-1 text-sm text-zinc-600">Update your name and email address</p>

    <form wire:submit.prevent="updateProfileInformation" class="mt-6 space-y-6">
      <div>
        <label for="name" class="mb-1 block text-sm font-medium text-zinc-700">Name</label>
        <input id="name" type="text" wire:model.defer="name" required autocomplete="name"
               class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"/>
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
      </div>

      <div>
        <label for="email" class="mb-1 block text-sm font-medium text-zinc-700">Email</label>
        <input id="email" type="email" wire:model.defer="email" required autocomplete="email"
               class="block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"/>
        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
      </div>

      <div class="flex items-center gap-4">
        <button type="submit"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">
          Save
        </button>
        <x-action-message on="profile-updated"  >Saved.</x-action-message>
      </div>
    </form>
  </div>
</div>
