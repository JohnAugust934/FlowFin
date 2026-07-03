{{-- Landing do FlowFin: apresenta o produto e leva para entrar/criar conta.
     Mesma linguagem visual do app (fundo ambiente + vidro fosco + Sora). --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FlowFin — Sobrou ou faltou este mês?</title>
    <meta name="description" content="FlowFin: registre entradas e saídas em segundos e veja para onde vai o seu dinheiro. Grátis, simples e funciona no celular.">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Aplica o tema salvo antes da pintura (mesmo script do app, evita flash). --}}
    <script>
        (function () {
            var mode = localStorage.getItem('theme') || 'system';
            var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="app-canvas">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 min-h-screen flex flex-col">

            {{-- Topo: marca + entrar --}}
            <header class="flex items-center justify-between">
                <x-brand-logo class="h-9 sm:h-10" />
                <nav class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="btn-secondary">Entrar</a>
                    <a href="{{ route('register') }}" class="btn-primary hidden sm:inline-flex">Criar conta grátis</a>
                </nav>
            </header>

            <main class="flex-1 flex flex-col justify-center py-10 sm:py-14">
                {{-- Tese do produto --}}
                <div class="text-center">
                    <h1 class="font-display text-4xl sm:text-5xl font-bold tracking-tight text-neutral-900 dark:text-neutral-50">
                        Sobrou ou faltou<br class="sm:hidden"> este mês?
                    </h1>
                    <p class="mt-4 max-w-xl mx-auto text-base sm:text-lg text-neutral-600 dark:text-neutral-300">
                        O FlowFin responde essa pergunta todo dia. Registre o que entra e o que sai em segundos
                        e veja para onde o seu dinheiro está indo — sem planilha e sem jargão.
                    </p>
                    <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="{{ route('register') }}" class="btn-primary w-full sm:w-auto px-6 py-3 text-base">Criar conta grátis</a>
                        <a href="{{ route('login') }}" class="btn-secondary w-full sm:w-auto px-6 py-3 text-base">Já tenho conta</a>
                    </div>
                </div>

                {{-- Amostra do painel (a assinatura do app) --}}
                <div class="glass relative overflow-hidden p-6 mt-10 max-w-md w-full mx-auto" aria-hidden="true">
                    <div class="pointer-events-none absolute -top-16 -right-10 w-56 h-56 rounded-full opacity-40 blur-3xl bg-emerald-400/40"></div>
                    <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Sobrou neste mês</p>
                    <p class="money mt-1 text-4xl font-bold tracking-tight text-brand-600 dark:text-brand-300">R$ 482,60</p>
                    <p class="mt-1 text-xs text-neutral-400 dark:text-neutral-500">Você fechou o mês no positivo.</p>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="glass-row p-3">
                            <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Entrou</p>
                            <p class="money mt-1 text-lg font-bold text-emerald-600 dark:text-emerald-400">R$ 3.250,00</p>
                        </div>
                        <div class="glass-row p-3">
                            <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Saiu</p>
                            <p class="money mt-1 text-lg font-bold text-danger">R$ 2.767,40</p>
                        </div>
                    </div>
                </div>

                {{-- O que o FlowFin faz por você --}}
                <div class="mt-10 grid gap-3 sm:grid-cols-3">
                    <div class="card">
                        <p class="font-display font-semibold text-neutral-800 dark:text-neutral-100">Registre em 3 toques</p>
                        <p class="mt-1.5 text-sm text-neutral-500 dark:text-neutral-400">Valor, categoria, salvar. Rápido o bastante para virar hábito.</p>
                    </div>
                    <div class="card">
                        <p class="font-display font-semibold text-neutral-800 dark:text-neutral-100">Veja para onde foi</p>
                        <p class="mt-1.5 text-sm text-neutral-500 dark:text-neutral-400">Gráficos simples por categoria e a diferença entre necessidade e desejo.</p>
                    </div>
                    <div class="card">
                        <p class="font-display font-semibold text-neutral-800 dark:text-neutral-100">Funciona offline</p>
                        <p class="mt-1.5 text-sm text-neutral-500 dark:text-neutral-400">Instale no celular e registre mesmo sem internet — sincroniza depois.</p>
                    </div>
                </div>
            </main>

            <footer class="py-6 text-center text-xs text-neutral-400 dark:text-neutral-500">
                FlowFin — suas finanças em dia, sem complicação.
            </footer>
        </div>
    </div>
</body>
</html>
