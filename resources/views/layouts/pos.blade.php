<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $title ?? 'Kasir — Jelly Potter' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 text-slate-900 antialiased min-h-screen">
    {{ $slot }}

    <div x-data="{ toasts: [] }"
         @toast.window="let id = Date.now(); toasts.push({ id, ...$event.detail }); setTimeout(() => toasts = toasts.filter(t => t.id !== id), 3000)"
         class="fixed bottom-5 inset-x-0 flex flex-col items-center gap-2 z-[100] pointer-events-none px-4" aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium shadow-lg ring-1"
                 :class="t.type === 'error' ? 'bg-white text-rose-700 ring-rose-200' : 'bg-slate-900 text-white ring-slate-900'">
                <span class="size-2 rounded-full" :class="t.type === 'error' ? 'bg-rose-500' : 'bg-emerald-400'"></span>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>
    @livewireScripts
</body>
</html>
