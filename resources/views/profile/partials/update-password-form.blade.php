<section>
    <header>
        <h2 class="text-base font-semibold text-neutral-800 dark:text-neutral-100">Alterar senha</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Use uma senha longa e difícil de adivinhar para manter sua conta segura.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Senha atual</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password"
                   class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Nova senha</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                   class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Confirmar nova senha</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Salvar</button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                   class="text-sm text-success font-medium">Senha atualizada ✓</p>
            @endif
        </div>
    </form>
</section>
