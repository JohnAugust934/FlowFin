{{-- Gestão de categorias personalizadas. As do app (pré-definidas) ficam protegidas. --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-bold text-neutral-800 dark:text-neutral-100">Categorias</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Organize suas entradas e saídas do seu jeito</p>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" x-data="categoriesManager">

        {{-- Formulário de criar/editar --}}
        <x-card :title="null">
            <h2 class="text-base font-semibold text-neutral-800 dark:text-neutral-100 mb-3" x-text="editing ? 'Editar categoria' : 'Nova categoria'"></h2>
            <form @submit.prevent="save()" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Nome</label>
                        <input type="text" x-model="form.name" placeholder="Ex.: Pets"
                            class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500"
                            :class="{ 'border-danger': fieldError('name') }">
                        <p x-show="fieldError('name')" x-text="fieldError('name')" class="text-sm text-danger mt-1"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Ícone</label>
                        <select x-model="form.icon" class="block w-full border-neutral-300 rounded-lg focus:border-brand-500 focus:ring-brand-500">
                            <option value="tag">Etiqueta</option>
                            <option value="home">Casa</option>
                            <option value="shopping-cart">Carrinho</option>
                            <option value="truck">Transporte</option>
                            <option value="heart">Saúde</option>
                            <option value="sparkles">Lazer</option>
                            <option value="academic-cap">Educação</option>
                            <option value="shopping-bag">Compras</option>
                            <option value="rectangle-stack">Assinaturas</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Cor</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="form.color" class="h-10 w-14 rounded border border-neutral-300 cursor-pointer">
                            <span class="flex items-center justify-center w-10 h-10 rounded-full"
                                  :style="`background:${form.color}1A; color:${form.color}`"
                                  x-html="window.FlowFin.iconSvg(form.icon, 'w-5 h-5')"></span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" :disabled="saving" class="btn-primary">
                        <span x-text="saving ? 'Salvando…' : (editing ? 'Salvar alterações' : 'Criar categoria')"></span>
                    </button>
                    <button type="button" x-show="editing" @click="startCreate()" class="btn-secondary">Cancelar</button>
                </div>
            </form>
        </x-card>

        {{-- Carregando --}}
        <div x-show="loading" class="text-center text-neutral-400 dark:text-neutral-500 py-6">Carregando…</div>

        {{-- Categorias personalizadas --}}
        <div x-show="!loading">
            <h2 class="text-sm font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wide mb-2">Suas categorias</h2>
            <div x-show="custom.length === 0" class="text-sm text-neutral-400 dark:text-neutral-500 py-3">Você ainda não criou categorias.</div>
            <div class="space-y-2">
                <template x-for="cat in custom" :key="cat.id">
                    <div class="glass-row shadow-card px-4 py-3 flex items-center gap-3">
                        <span class="flex items-center justify-center w-10 h-10 rounded-full shrink-0"
                              :style="`background:${cat.color || '#6B7280'}1A; color:${cat.color || '#6B7280'}`"
                              x-html="iconFor(cat)"></span>
                        <p class="flex-1 font-medium text-neutral-800 dark:text-neutral-100" x-text="cat.name"></p>

                        <template x-if="confirmingId !== cat.id">
                            <div class="flex items-center gap-1">
                                <button type="button" @click="startEdit(cat)" class="p-2 text-neutral-400 hover:text-brand-600" aria-label="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                </button>
                                <button type="button" @click="confirmDelete(cat.id)" class="p-2 text-neutral-400 hover:text-danger" aria-label="Excluir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166M18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165" /></svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="confirmingId === cat.id">
                            <div class="flex items-center gap-1">
                                <button type="button" @click="remove(cat)" class="btn-danger px-2 py-1 text-xs">Excluir</button>
                                <button type="button" @click="cancelDelete()" class="btn-secondary px-2 py-1 text-xs">Cancelar</button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Categorias do app (protegidas) --}}
        <div x-show="!loading">
            <h2 class="text-sm font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wide mb-2">Categorias do app</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                <template x-for="cat in predefined" :key="cat.id">
                    <div class="glass-row px-3 py-2 flex items-center gap-2">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full shrink-0"
                              :style="`background:${cat.color || '#6B7280'}1A; color:${cat.color || '#6B7280'}`"
                              x-html="iconFor(cat)"></span>
                        <span class="text-sm text-neutral-700 dark:text-neutral-200 truncate" x-text="cat.name"></span>
                    </div>
                </template>
            </div>
            <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-2">As categorias do app não podem ser editadas nem excluídas.</p>
        </div>
    </div>
</x-app-layout>
