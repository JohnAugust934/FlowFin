<section>
    <header>
        <h2 class="text-base font-semibold text-neutral-800 dark:text-neutral-100">Dados pessoais</h2>
        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Atualize seu nome, e-mail e a sua renda mensal estimada.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Nome</label>
            <input id="name" name="name" type="text" required autofocus autocomplete="name"
                   value="{{ old('name', $user->name) }}"
                   class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">E-mail</label>
            <input id="email" name="email" type="email" required autocomplete="username"
                   value="{{ old('email', $user->email) }}"
                   class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-neutral-700 dark:text-neutral-300">
                        Seu e-mail ainda não foi confirmado.

                        <button form="send-verification" class="underline text-sm text-brand-600 hover:text-brand-700 dark:text-brand-300 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-500">
                            Clique aqui para reenviar o e-mail de confirmação.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-success">
                            Um novo link de confirmação foi enviado para o seu e-mail.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <label for="monthly_income" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Renda mensal estimada</label>
            <div class="relative mt-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-neutral-500 dark:text-neutral-400">R$</span>
                <input id="monthly_income" name="monthly_income" type="text" inputmode="decimal" autocomplete="off"
                       placeholder="0,00"
                       value="{{ old('monthly_income') !== null ? \App\Support\Money::format((int) old('monthly_income')) : \App\Support\Money::format($user->monthly_income) }}"
                       class="block w-full pl-9 border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
            </div>
            <p class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">Usada para personalizar seus resumos. Opcional.</p>
            <x-input-error class="mt-2" :messages="$errors->get('monthly_income')" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Salvar</button>
        </div>
    </form>
</section>
