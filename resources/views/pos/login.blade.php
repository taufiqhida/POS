<x-layouts.pos-plain title="Masuk Kasir — Jelly Potter">
<div class="min-h-screen grid place-items-center p-4"
     x-data="{
        outlet: @js(old('outlet_id', $outlets->first()?->id)),
        user: @js(old('user_id')),
        pin: '',
        users: @js($users),
        get list() { return this.users.filter(u => u.role !== 'kasir' || !u.outlet_id || u.outlet_id === this.outlet) },
        press(d) { if (this.pin.length < 6) this.pin += d; },
     }">
    <form method="POST" action="{{ route('pos.login.submit') }}" class="w-full max-w-md">
        @csrf
        <div class="flex items-center gap-3 mb-6">
            <div class="grid size-10 place-items-center rounded-lg bg-indigo-600 text-sm font-bold text-white">JP</div>
            <div>
                <h1 class="text-lg font-semibold leading-tight">Jelly Potter Kasir</h1>
                <p class="text-sm text-slate-500">Masuk dengan akun & PIN pribadi</p>
            </div>
        </div>

        <div class="card p-5 space-y-5">
            @if ($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700" role="alert">{{ $errors->first() }}</div>
            @endif

            <div>
                <span class="label">Outlet</span>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($outlets as $o)
                        <button type="button" @click="outlet = @js($o->id); user = null; pin = ''"
                                :class="outlet === @js($o->id) && 'option-active'" class="option">{{ $o->name }}</button>
                    @endforeach
                </div>
                <input type="hidden" name="outlet_id" :value="outlet">
            </div>

            <div>
                <span class="label">Pegawai</span>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="u in list" :key="u.id">
                        <button type="button" @click="user = u.id; pin = ''" :class="user === u.id && 'option-active'" class="option text-left">
                            <span class="block font-semibold" x-text="u.name"></span>
                            <span class="block text-xs capitalize text-slate-500" x-text="u.role"></span>
                        </button>
                    </template>
                </div>
                <input type="hidden" name="user_id" :value="user">
            </div>

            <div x-show="user" x-cloak>
                <span class="label">PIN</span>
                <div class="flex justify-center gap-3 py-3" aria-hidden="true">
                    <template x-for="i in 6">
                        <span class="size-3 rounded-full transition" :class="i <= pin.length ? 'bg-indigo-600' : 'bg-slate-200'"></span>
                    </template>
                </div>
                <input type="hidden" name="pin" :value="pin">
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="d in ['1','2','3','4','5','6','7','8','9']">
                        <button type="button" @click="press(d)" class="keypad-key" x-text="d"></button>
                    </template>
                    <button type="button" @click="pin = pin.slice(0, -1)" class="keypad-key text-sm text-slate-500">Hapus</button>
                    <button type="button" @click="press('0')" class="keypad-key">0</button>
                    <button type="submit" :disabled="pin.length < 4" class="btn btn-primary h-14 text-base">Masuk</button>
                </div>
            </div>
        </div>

        <p class="mt-4 text-center text-sm text-slate-500">Owner atau manager? <a href="/admin" class="font-medium text-indigo-700 hover:underline">Buka panel admin</a></p>
    </form>
</div>
</x-layouts.pos-plain>
