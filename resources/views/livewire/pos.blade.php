@php
    $tones = ['bg-violet-100 text-violet-700', 'bg-amber-100 text-amber-800', 'bg-sky-100 text-sky-700', 'bg-emerald-100 text-emerald-700', 'bg-rose-100 text-rose-700'];
    $tone = fn ($key) => $tones[abs(crc32((string) $key)) % count($tones)];
    $initials = fn ($name) => collect(explode(' ', $name))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $tabs = [
        'kasir' => ['Kasir', 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z'],
        'riwayat' => ['Riwayat', 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
        'stok' => ['Stok', 'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z'],
        'shift' => ['Shift & Kas', 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z'],
    ];
    $icon = fn ($d, $cls = 'size-5') => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="'.$cls.'" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="'.$d.'"/></svg>';
    $num = fn ($v) => rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
@endphp

<div class="min-h-screen flex flex-col">
    {{-- ================= HEADER ================= --}}
    <header class="sticky top-0 z-30 bg-white border-b border-slate-200">
        <div class="flex h-16 items-center gap-4 px-4 lg:px-6">
            <div class="flex items-center gap-3 min-w-0">
                <div class="grid size-9 shrink-0 place-items-center rounded-lg bg-indigo-600 text-xs font-bold text-white">JP</div>
                <div class="min-w-0 leading-tight">
                    <div class="flex items-center gap-2">
                        <span class="truncate text-sm font-semibold text-slate-900">Outlet {{ $this->outlet->name }}</span>
                        @if ($this->shift)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span>Shift {{ $this->shift->opened_at->format('H:i') }}
                            </span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">Shift belum dibuka</span>
                        @endif
                    </div>
                    <div class="truncate text-xs text-slate-500">{{ auth()->user()->name }} · <span class="capitalize">{{ auth()->user()->role }}</span></div>
                </div>
            </div>

            @if ($this->shift)
                <nav class="ml-auto hidden md:flex h-full items-stretch gap-1" aria-label="Navigasi kasir">
                    @foreach ($tabs as $k => [$label, $path])
                        <button wire:click="$set('tab', '{{ $k }}')" @if($tab === $k) aria-current="page" @endif
                                class="relative flex items-center gap-2 px-3 text-sm font-medium transition
                                       {{ $tab === $k ? 'text-indigo-700' : 'text-slate-500 hover:text-slate-900' }}">
                            {!! $icon($path, 'size-[18px]') !!} {{ $label }}
                            <span class="absolute inset-x-2 -bottom-px h-0.5 rounded-full {{ $tab === $k ? 'bg-indigo-600' : 'bg-transparent' }}"></span>
                        </button>
                    @endforeach
                </nav>
            @endif

            <form method="POST" action="{{ route('pos.logout') }}" class="{{ $this->shift ? 'md:ml-2 ml-auto' : 'ml-auto' }}">
                @csrf
                <button class="btn btn-ghost btn-sm">Keluar</button>
            </form>
        </div>

        {{-- Tab bawah untuk layar kecil --}}
        @if ($this->shift)
            <nav class="md:hidden grid grid-cols-4 border-t border-slate-200" aria-label="Navigasi kasir">
                @foreach ($tabs as $k => [$label, $path])
                    <button wire:click="$set('tab', '{{ $k }}')" class="flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium border-t-2 -mt-px
                        {{ $tab === $k ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500' }}">
                        {!! $icon($path) !!} {{ $label }}
                    </button>
                @endforeach
            </nav>
        @endif
    </header>

    @error('shift') <div class="border-b border-rose-200 bg-rose-50 px-6 py-2.5 text-sm font-medium text-rose-700" role="alert">{{ $message }}</div> @enderror

    @if (! $this->shift)
        {{-- ================= BUKA SHIFT ================= --}}
        <main class="flex-1 grid place-items-center p-4">
            <div class="w-full max-w-md space-y-4">
                @if ($closedShift)
                    @php($s = $closedShift->summary())
                    @php($d = $closedShift->cash_difference)
                    <section class="card">
                        <div class="card-header"><h2 class="card-title">Ringkasan shift yang baru ditutup</h2></div>
                        <dl class="px-5 py-4 grid grid-cols-2 gap-y-2 text-sm">
                            <dt class="text-slate-500">Transaksi</dt><dd class="text-right font-medium tabular-nums">{{ $s['transactions'] }}</dd>
                            <dt class="text-slate-500">Total penjualan</dt><dd class="text-right font-medium tabular-nums">{{ $rp($s['sales_total']) }}</dd>
                            @foreach ($s['by_method'] as $m => $v)
                                <dt class="pl-3 text-slate-400">{{ \App\Models\Transaction::METHODS[$m] ?? $m }}</dt><dd class="text-right tabular-nums text-slate-500">{{ $rp($v) }}</dd>
                            @endforeach
                            <dt class="text-slate-500">Modal awal</dt><dd class="text-right tabular-nums">{{ $rp($closedShift->opening_cash) }}</dd>
                            <dt class="text-slate-500">Pengeluaran</dt><dd class="text-right tabular-nums">{{ $rp($s['expenses']) }}</dd>
                            <dt class="text-slate-500">Cash seharusnya</dt><dd class="text-right font-medium tabular-nums">{{ $rp($closedShift->expected_cash) }}</dd>
                            <dt class="text-slate-500">Cash fisik</dt><dd class="text-right font-medium tabular-nums">{{ $rp($closedShift->actual_cash) }}</dd>
                        </dl>
                        <div class="mx-5 mb-5 flex items-center justify-between rounded-lg px-4 py-3 {{ $d == 0 ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">
                            <span class="text-sm font-medium">Selisih kas</span>
                            <span class="text-lg font-semibold tabular-nums">{{ $rp($d) }}</span>
                        </div>
                        @php($wa = app(\App\Services\ReportService::class)->whatsappLink(app(\App\Services\ReportService::class)->shiftCloseMessage($closedShift)))
                        @if ($wa)
                            <div class="px-5 pb-5"><a href="{{ $wa }}" target="_blank" class="btn btn-secondary w-full">Kirim ringkasan ke WhatsApp owner</a></div>
                        @endif
                    </section>
                @endif

                <form wire:submit="openShift" class="card">
                    <div class="px-5 pt-5">
                        <h2 class="text-lg font-semibold">Buka shift</h2>
                        <p class="mt-1 text-sm text-slate-500">Hitung uang di laci, lalu isi modal awal kas sebelum mulai melayani.</p>
                    </div>
                    <div class="px-5 py-5 space-y-3">
                        <label for="openingCash" class="label">Modal awal kas</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-lg font-medium text-slate-400">Rp</span>
                            <input id="openingCash" type="number" inputmode="numeric" wire:model="openingCash" autofocus placeholder="0"
                                   class="input h-16 pl-14 text-2xl font-semibold tabular-nums">
                        </div>
                        @error('openingCash') <p class="text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ([100000, 150000, 200000] as $v)
                                <button type="button" wire:click="$set('openingCash', {{ $v }})" class="option py-2 tabular-nums {{ $openingCash == $v ? 'option-active' : '' }}">{{ $rp($v) }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="border-t border-slate-200 p-5">
                        <button class="btn btn-primary btn-lg w-full">Buka shift</button>
                    </div>
                </form>
            </div>
        </main>

    @elseif ($tab === 'kasir')
        {{-- ================= KASIR ================= --}}
        <main class="flex-1 grid lg:grid-cols-[minmax(0,1fr)_400px]">
            <section class="min-w-0 p-4 lg:p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="relative shrink-0 sm:w-56">
                        <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                        <input type="search" wire:model.live.debounce.250ms="search" placeholder="Cari menu" class="input h-10 pl-9 text-sm" aria-label="Cari menu">
                    </div>
                    <div class="flex min-w-0 gap-1 overflow-x-auto rounded-lg bg-white p-1 ring-1 ring-slate-200" role="tablist">
                        <button wire:click="$set('category', '{{ \App\Livewire\Pos::BEST }}')"
                                class="chip gap-1.5 {{ $category === \App\Livewire\Pos::BEST ? 'chip-active' : 'chip-idle' }}">
                            <svg class="size-4 {{ $category === \App\Livewire\Pos::BEST ? 'text-amber-300' : 'text-amber-500' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401Z"/></svg>
                            Best
                        </button>
                        <button wire:click="$set('category', '')" class="chip {{ $category === '' ? 'chip-active' : 'chip-idle' }}">Semua</button>
                        @foreach ($this->categories as $c)
                            <button wire:click="$set('category', @js($c))" class="chip {{ $category === $c ? 'chip-active' : 'chip-idle' }}">{{ $c }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
                    @forelse ($this->menus as $m)
                        @php($from = $m->variants->first()?->price ?? $m->base_price)
                        <button wire:click="pick('{{ $m->id }}')" wire:key="m-{{ $m->id }}"
                                class="group card overflow-hidden text-left transition hover:border-indigo-300 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 active:scale-[.99]">
                            <div class="aspect-[4/3] w-full overflow-hidden border-b border-slate-200 bg-slate-100">
                                @if ($m->imageUrl())
                                    <img src="{{ $m->imageUrl() }}" alt="" loading="lazy" class="size-full object-cover transition group-hover:scale-[1.03]">
                                @else
                                    <div class="grid size-full place-items-center {{ $tone($m->category) }}">
                                        <span class="text-2xl font-semibold tracking-tight opacity-80">{{ $initials($m->name) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="p-3">
                                <div class="flex flex-wrap gap-1">
                                    @if (in_array($m->id, $this->best[0]))
                                        <span class="badge bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200">★ Best</span>
                                    @endif
                                    <span class="badge bg-slate-100 text-slate-600">{{ $m->category }}</span>
                                </div>
                                <div class="mt-1.5 line-clamp-2 min-h-[2.5rem] text-sm font-semibold leading-5 text-slate-900">{{ $m->name }}</div>
                                <div class="mt-1 flex items-baseline gap-1">
                                    @if ($m->variants->count() > 1)<span class="text-xs text-slate-500">mulai</span>@endif
                                    <span class="text-base font-semibold tabular-nums text-indigo-700">{{ $rp($from) }}</span>
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full card grid place-items-center py-16 text-sm text-slate-500">
                            @if ($search)
                                Menu tidak ditemukan.
                            @elseif ($category === \App\Livewire\Pos::BEST)
                                <div class="text-center">
                                    <p class="font-medium text-slate-700">Belum ada best menu</p>
                                    <p class="mt-1 text-xs">Akan terisi otomatis dari menu terlaris, atau owner bisa menandainya di Admin › Menu & Resep.</p>
                                </div>
                            @else
                                Belum ada menu untuk outlet ini.
                            @endif
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Panel pesanan --}}
            <aside class="flex flex-col border-t lg:border-t-0 lg:border-l border-slate-200 bg-white lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)]">
                <div class="flex items-center gap-3 px-5 h-14 border-b border-slate-200">
                    <h2 class="text-sm font-semibold">Pesanan</h2>
                    @if ($cart)
                        <span class="badge bg-indigo-50 text-indigo-700">{{ collect($cart)->sum('qty') }} item</span>
                    @endif
                    <label class="sr-only" for="channel">Kanal pesanan</label>
                    <select id="channel" wire:model.live="channel" class="input ml-auto h-9 w-auto py-0 pr-8 text-xs font-medium">
                        @foreach (\App\Models\Transaction::CHANNELS as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @forelse ($cart as $key => $line)
                        @php($p = collect($this->priced['lines'])->values()[$loop->index] ?? null)
                        <div class="px-5 py-4 border-b border-slate-100" wire:key="c-{{ $key }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-900">{{ $line['name'] }}</div>
                                    <div class="mt-0.5 text-xs text-slate-500">
                                        {{ $line['size'] }}@if($line['topping_names']) · {{ $line['topping_names'] }}@endif
                                    </div>
                                    <div class="mt-0.5 text-xs tabular-nums text-slate-500">
                                        {{ $rp($p['each'] ?? 0) }} / cup
                                        @if ($line['price_override'] !== null)<span class="ml-1 badge bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200">harga diubah</span>@endif
                                    </div>
                                </div>
                                <div class="text-sm font-semibold tabular-nums">{{ $rp($p['lineTotal'] ?? 0) }}</div>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <div class="inline-flex items-center rounded-lg border border-slate-300">
                                    <button wire:click="qty('{{ $key }}', -1)" class="grid size-9 place-items-center text-slate-600 hover:bg-slate-50 rounded-l-lg" aria-label="Kurangi">−</button>
                                    <span class="w-8 text-center text-sm font-semibold tabular-nums">{{ $line['qty'] }}</span>
                                    <button wire:click="qty('{{ $key }}', 1)" class="grid size-9 place-items-center text-slate-600 hover:bg-slate-50 rounded-r-lg" aria-label="Tambah">+</button>
                                </div>
                                <button wire:click="askPin('price', {key: '{{ $key }}'})" class="btn btn-ghost btn-sm ml-auto">Ubah harga</button>
                                <button wire:click="removeLine('{{ $key }}')" class="btn btn-ghost btn-sm text-rose-600 hover:bg-rose-50 hover:text-rose-700">Hapus</button>
                            </div>
                        </div>
                    @empty
                        <div class="grid h-full min-h-48 place-items-center px-8 text-center">
                            <div>
                                <div class="mx-auto mb-3 grid size-12 place-items-center rounded-full bg-slate-100 text-slate-400">{!! $icon($tabs['kasir'][1], 'size-6') !!}</div>
                                <p class="text-sm font-medium text-slate-700">Belum ada pesanan</p>
                                <p class="mt-1 text-xs text-slate-500">Pilih menu di sebelah kiri untuk memulai.</p>
                            </div>
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-slate-200 bg-slate-50/60 px-5 py-4 space-y-4">
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="tabular-nums">{{ $rp($this->priced['subtotal']) }}</dd></div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500 truncate">Diskon @if($this->priced['discount_label'])<span class="text-xs text-emerald-700">· {{ $this->priced['discount_label'] }}</span>@endif</dt>
                            <dd class="tabular-nums {{ $this->priced['discount'] ? 'text-emerald-700' : '' }}">−{{ $rp($this->priced['discount']) }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between border-t border-dashed border-slate-300 pt-2.5">
                            <dt class="font-semibold">Total</dt>
                            <dd class="text-2xl font-bold tabular-nums tracking-tight">{{ $rp($this->priced['total']) }}</dd>
                        </div>
                    </dl>
                    <div class="grid grid-cols-[auto_auto_1fr] gap-2">
                        <button wire:click="askPin('discount')" @disabled(empty($cart)) class="btn btn-secondary">Diskon</button>
                        <button wire:click="clearCart" @disabled(empty($cart)) class="btn btn-secondary">Batal</button>
                        <button wire:click="openPay" @disabled(empty($cart)) class="btn btn-primary btn-lg h-11 text-base">
                            Bayar @if($cart)<span class="tabular-nums font-medium opacity-90">· {{ $rp($this->priced['total']) }}</span>@endif
                        </button>
                    </div>
                </div>
            </aside>
        </main>

    @elseif ($tab === 'riwayat')
        {{-- ================= RIWAYAT ================= --}}
        <main class="flex-1 p-4 lg:p-6">
            <section class="card mx-auto max-w-5xl">
                <div class="card-header">
                    <h2 class="card-title">Transaksi hari ini</h2>
                    <span class="ml-auto text-xs text-slate-500">{{ $this->history->count() }} transaksi</span>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($this->history as $t)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3.5" wire:key="h-{{ $t->id }}">
                            <div class="min-w-0 flex-1 basis-60">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold tabular-nums">{{ $t->number }}</span>
                                    <span class="text-xs text-slate-400 tabular-nums">{{ $t->created_at->format('H:i') }}</span>
                                    @if ($t->status !== 'paid')
                                        <span class="badge bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200">{{ \App\Models\Transaction::STATUSES[$t->status] }}</span>
                                    @endif
                                </div>
                                <div class="mt-0.5 truncate text-sm text-slate-600">{{ $t->items->map(fn($i) => $i->qty.'× '.$i->menu_name)->implode(', ') }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $t->cashier->name }} · {{ \App\Models\Transaction::METHODS[$t->payment_method] }} · {{ \App\Models\Transaction::CHANNELS[$t->channel] }}</div>
                            </div>
                            <div class="text-base font-semibold tabular-nums {{ $t->status !== 'paid' ? 'text-slate-400 line-through' : '' }}">{{ $rp($t->total) }}</div>
                            @if ($t->status === 'paid')
                                <div class="flex gap-1.5">
                                    <a href="{{ route('receipt', $t) }}" target="_blank" class="btn btn-secondary btn-sm">Struk</a>
                                    <button wire:click="askPin('void', {id: '{{ $t->id }}'})" class="btn btn-secondary btn-sm">Void</button>
                                    <button wire:click="askPin('refund', {id: '{{ $t->id }}'})" class="btn btn-secondary btn-sm">Refund</button>
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-16 text-center text-sm text-slate-500">Belum ada transaksi hari ini.</li>
                    @endforelse
                </ul>
            </section>
        </main>

    @elseif ($tab === 'shift')
        {{-- ================= SHIFT & KAS ================= --}}
        @php($s = $this->shift->summary())
        <main class="flex-1 p-4 lg:p-6">
            <div class="mx-auto max-w-5xl grid gap-4 lg:grid-cols-2">
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Ringkasan shift</h2></div>
                    <dl class="px-5 py-4 grid grid-cols-2 gap-y-2.5 text-sm">
                        <dt class="text-slate-500">Dibuka oleh</dt><dd class="text-right font-medium">{{ $this->shift->opener->name }}</dd>
                        <dt class="text-slate-500">Jam buka</dt><dd class="text-right tabular-nums">{{ $this->shift->opened_at->format('d/m H:i') }}</dd>
                        <dt class="text-slate-500">Transaksi</dt><dd class="text-right tabular-nums">{{ $s['transactions'] }}</dd>
                        <dt class="text-slate-500">Total penjualan</dt><dd class="text-right font-medium tabular-nums">{{ $rp($s['sales_total']) }}</dd>
                    </dl>
                    <div class="mx-5 mb-5 rounded-lg border border-slate-200">
                        <dl class="divide-y divide-slate-100 text-sm">
                            <div class="flex justify-between px-4 py-2.5"><dt class="text-slate-500">Modal awal</dt><dd class="tabular-nums">{{ $rp($s['opening_cash']) }}</dd></div>
                            <div class="flex justify-between px-4 py-2.5"><dt class="text-slate-500">+ Penjualan tunai</dt><dd class="tabular-nums">{{ $rp($s['cash_sales']) }}</dd></div>
                            <div class="flex justify-between px-4 py-2.5"><dt class="text-slate-500">− Pengeluaran</dt><dd class="tabular-nums">{{ $rp($s['expenses']) }}</dd></div>
                            <div class="flex justify-between bg-indigo-50 px-4 py-3 rounded-b-lg"><dt class="font-semibold text-indigo-900">Cash seharusnya</dt><dd class="text-lg font-semibold tabular-nums text-indigo-900">{{ $rp($s['expected_cash']) }}</dd></div>
                        </dl>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header"><h2 class="card-title">Pengeluaran outlet</h2></div>
                    <form wire:submit="addExpense" class="px-5 pt-4 grid grid-cols-[1fr_9rem] gap-2">
                        <div>
                            <label class="label" for="expenseDesc">Keterangan</label>
                            <input id="expenseDesc" wire:model="expenseDesc" placeholder="mis. beli es batu" class="input h-10 text-sm">
                        </div>
                        <div>
                            <label class="label" for="expenseAmount">Jumlah</label>
                            <input id="expenseAmount" type="number" inputmode="numeric" wire:model="expenseAmount" placeholder="Rp" class="input h-10 text-sm tabular-nums">
                        </div>
                        @error('expenseDesc') <p class="col-span-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @error('expenseAmount') <p class="col-span-2 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <button class="btn btn-secondary col-span-2">Simpan pengeluaran</button>
                    </form>
                    <ul class="mt-4 divide-y divide-slate-100 border-t border-slate-100 text-sm">
                        @forelse ($this->shift->expenses()->with('creator')->latest()->get() as $e)
                            <li class="flex justify-between gap-3 px-5 py-2.5">
                                <span>{{ $e->description }} <span class="text-xs text-slate-400">· {{ $e->creator->name }}</span></span>
                                <span class="tabular-nums font-medium">{{ $rp($e->amount) }}</span>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-xs text-slate-500">Belum ada pengeluaran di shift ini.</li>
                        @endforelse
                    </ul>
                </section>

                <form wire:submit="confirmCloseShift" class="card lg:col-span-2" x-data="{ cash: @entangle('actualCash'), expected: {{ $s['expected_cash'] }} }">
                    <div class="card-header">
                        <h2 class="card-title">Tutup shift</h2>
                        <span class="ml-auto text-xs text-slate-500">Hitung semua uang tunai di laci</span>
                    </div>
                    <div class="grid gap-4 p-5 md:grid-cols-[1fr_1fr_auto] md:items-end">
                        <div>
                            <label class="label" for="actualCash">Cash fisik di laci</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-slate-400">Rp</span>
                                <input id="actualCash" type="number" inputmode="numeric" x-model.number="cash" class="input h-12 pl-10 text-lg font-semibold tabular-nums">
                            </div>
                            @error('actualCash') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex h-12 items-center justify-between rounded-lg px-4 text-sm"
                             :class="cash === null || cash === '' ? 'bg-slate-100 text-slate-500' : (cash - expected === 0 ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800')">
                            <span class="font-medium">Selisih</span>
                            <span class="text-base font-semibold tabular-nums"
                                  x-text="cash === null || cash === '' ? '—' : (cash - expected < 0 ? '−' : '') + 'Rp' + Math.abs(cash - expected).toLocaleString('id-ID')"></span>
                        </div>
                        <button class="btn btn-danger h-12">Tutup shift</button>
                        <input wire:model="closeNote" placeholder="Catatan (opsional)" class="input h-10 text-sm md:col-span-3">
                    </div>
                </form>
            </div>
        </main>

    @elseif ($tab === 'stok')
        {{-- ================= STOK ================= --}}
        <main class="flex-1 p-4 lg:p-6">
            <div class="mx-auto max-w-6xl grid gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
                <form wire:submit="saveStockCount" class="card overflow-hidden">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Stok bahan & cek fisik</h2>
                            <p class="text-xs text-slate-500">Isi kolom “Fisik” hanya untuk bahan yang dihitung.</p>
                        </div>
                        <button class="btn btn-primary btn-sm ml-auto">Simpan hitungan</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-xs font-medium text-slate-500">
                                <tr>
                                    <th class="px-5 py-2.5 text-left font-medium">Bahan</th>
                                    <th class="px-3 py-2.5 text-right font-medium">Sistem</th>
                                    <th class="px-3 py-2.5 text-right font-medium">Min</th>
                                    <th class="px-3 py-2.5 text-right font-medium">Fisik</th>
                                    <th class="px-5 py-2.5 text-right font-medium">Selisih</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                            @foreach ($this->ingredients as $i)
                                <tr wire:key="i-{{ $i->id }}">
                                    <td class="px-5 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-slate-900">{{ $i->name }}</span>
                                            @if ($i->isCritical())<span class="badge bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200">Kritis</span>@endif
                                        </div>
                                    </td>
                                    <td class="px-3 text-right tabular-nums {{ $i->isCritical() ? 'font-semibold text-rose-700' : '' }}">{{ $num($i->stock_current) }} <span class="text-xs text-slate-400">{{ $i->unit }}</span></td>
                                    <td class="px-3 text-right tabular-nums text-slate-400">{{ $num($i->stock_min) }}</td>
                                    <td class="px-3 text-right"><input type="number" step="0.01" wire:model.live.debounce.400ms="physical.{{ $i->id }}" class="input h-9 w-24 ml-auto text-right text-sm tabular-nums" aria-label="Stok fisik {{ $i->name }}"></td>
                                    <td class="px-5 text-right font-medium tabular-nums">
                                        @if (isset($physical[$i->id]) && $physical[$i->id] !== '' && is_numeric($physical[$i->id]))
                                            @php($d = $physical[$i->id] - $i->stock_current)
                                            <span class="{{ $d < 0 ? 'text-rose-700' : ($d > 0 ? 'text-emerald-700' : 'text-slate-500') }}">{{ $d > 0 ? '+' : '' }}{{ $num($d) }}</span>
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>

                <section class="card self-start overflow-hidden">
                    <div class="card-header"><h2 class="card-title">Riwayat pemakaian</h2></div>
                    <ul class="max-h-[70vh] divide-y divide-slate-100 overflow-y-auto text-sm">
                        @foreach ($this->usageHistory as $mv)
                            <li class="flex items-start justify-between gap-3 px-5 py-2.5">
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $mv->ingredient->name }}</div>
                                    <div class="text-xs text-slate-500">{{ \App\Models\StockMovement::REASONS[$mv->reason] ?? $mv->reason }} · {{ $mv->created_at->format('d/m H:i') }}</div>
                                </div>
                                <span class="tabular-nums font-medium {{ $mv->qty_change < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $mv->qty_change > 0 ? '+' : '' }}{{ $num($mv->qty_change) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>
        </main>
    @endif

    {{-- ================= MODAL: ukuran & topping ================= --}}
    @if ($pickMenuId)
        @php($pm = $this->menus->firstWhere('id', $pickMenuId) ?? \App\Models\Menu::with('variants')->find($pickMenuId))
        <div class="modal-backdrop" wire:click.self="$set('pickMenuId', null)" role="dialog" aria-modal="true" aria-labelledby="pick-title">
            <div class="modal sm:max-w-lg">
                <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                    <div class="size-12 shrink-0 overflow-hidden rounded-lg">
                        @if ($pm->imageUrl())
                            <img src="{{ $pm->imageUrl() }}" alt="" class="size-full object-cover">
                        @else
                            <div class="grid size-full place-items-center text-sm font-semibold {{ $tone($pm->category) }}">{{ $initials($pm->name) }}</div>
                        @endif
                    </div>
                    <div>
                        <h3 id="pick-title" class="text-base font-semibold">{{ $pm->name }}</h3>
                        <p class="text-xs text-slate-500">{{ $pm->category }}</p>
                    </div>
                    <button wire:click="$set('pickMenuId', null)" class="btn-icon ml-auto border-0" aria-label="Tutup">✕</button>
                </div>
                <div class="space-y-5 px-5 py-5">
                    @if ($pm->variants->isNotEmpty())
                        <fieldset>
                            <legend class="label">Ukuran</legend>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($pm->variants as $v)
                                    <button wire:click="$set('pickVariantId', '{{ $v->id }}')" class="option text-left {{ $pickVariantId === $v->id ? 'option-active' : '' }}">
                                        <span class="block font-semibold">{{ $v->size }}</span>
                                        <span class="block text-xs tabular-nums {{ $pickVariantId === $v->id ? 'text-indigo-700' : 'text-slate-500' }}">{{ $rp($v->price) }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                    @php($groupToppings = $this->toppings->where('group', $pm->topping_group))
                    @if ($groupToppings->isNotEmpty())
                    <fieldset>
                        <legend class="label">{{ ['waffle' => 'Isian & olesan', 'makanan' => 'Tambahan'][$pm->topping_group] ?? 'Topping' }}
                            @if ($pm->free_toppings)
                                <span class="ml-1 badge bg-emerald-50 text-emerald-800 ring-1 ring-inset ring-emerald-200">{{ $pm->free_toppings }} topping gratis</span>
                            @else
                                <span class="font-normal text-slate-400">(opsional)</span>
                            @endif
                        </legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($groupToppings as $t)
                                @php($on = in_array($t->id, $pickToppings))
                                <button wire:click="toggleTopping('{{ $t->id }}')" class="option flex items-center gap-2 py-2.5 {{ $on ? 'option-active' : '' }}" aria-pressed="{{ $on ? 'true' : 'false' }}">
                                    <span class="grid size-4 shrink-0 place-items-center rounded border {{ $on ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300' }}">
                                        @if ($on)<svg class="size-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z"/></svg>@endif
                                    </span>
                                    <span class="flex-1 text-left">{{ $t->name }}</span>
                                    <span class="text-xs tabular-nums text-slate-500">+{{ $rp($t->price) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>
                    @endif
                </div>
                <div class="flex gap-2 border-t border-slate-200 px-5 py-4">
                    <button wire:click="$set('pickMenuId', null)" class="btn btn-secondary">Batal</button>
                    <button wire:click="addToCart" class="btn btn-primary flex-1">Tambah ke pesanan</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= MODAL: pembayaran ================= --}}
    @if ($showPay)
        @php($total = $this->priced['total'])
        <div class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="pay-title">
            <div class="modal sm:max-w-lg">
                <div class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
                    <h3 id="pay-title" class="text-base font-semibold">Pembayaran</h3>
                    <span class="badge bg-slate-100 text-slate-700">{{ \App\Models\Transaction::CHANNELS[$channel] }}</span>
                </div>
                <div class="space-y-5 px-5 py-5">
                    <div class="rounded-lg bg-slate-50 px-4 py-4 text-center ring-1 ring-inset ring-slate-200">
                        <div class="text-xs font-medium text-slate-500">Total tagihan</div>
                        <div class="mt-1 text-3xl font-bold tabular-nums tracking-tight">{{ $rp($total) }}</div>
                    </div>
                    <fieldset>
                        <legend class="label">Metode bayar</legend>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach (\App\Models\Transaction::METHODS as $k => $v)
                                <button wire:click="$set('method', '{{ $k }}')" class="option px-2 {{ $method === $k ? 'option-active' : '' }}">{{ $v }}</button>
                            @endforeach
                        </div>
                    </fieldset>
                    @if ($method === 'cash' && $channel === 'offline')
                        <div>
                            <label class="label" for="paid">Uang diterima</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">Rp</span>
                                <input id="paid" type="number" inputmode="numeric" wire:model.live.debounce.300ms="paid" class="input h-14 pl-12 text-xl font-semibold tabular-nums">
                            </div>
                            <div class="mt-2 grid grid-cols-4 gap-2">
                                @foreach (array_unique([$total, (int) ceil($total / 10000) * 10000, (int) ceil($total / 50000) * 50000, 100000]) as $v)
                                    @if ($v >= $total)
                                        <button wire:click="$set('paid', {{ $v }})" class="option px-2 py-2 text-xs tabular-nums {{ (int) $paid === $v ? 'option-active' : '' }}">{{ $v === $total ? 'Uang pas' : $rp($v) }}</button>
                                    @endif
                                @endforeach
                            </div>
                            @if ($paid && $paid >= $total)
                                <div class="mt-3 flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-3 text-emerald-800">
                                    <span class="text-sm font-medium">Kembalian</span>
                                    <span class="text-xl font-semibold tabular-nums">{{ $rp($paid - $total) }}</span>
                                </div>
                            @endif
                        </div>
                    @elseif ($method === 'qris')
                        <p class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">Minta pembeli memindai QRIS outlet, lalu konfirmasi setelah dana masuk.</p>
                    @endif
                    @foreach (['paid', 'pin', 'cart', 'payment'] as $f)
                        @error($f) <p class="text-sm font-medium text-rose-600" role="alert">{{ $message }}</p> @enderror
                    @endforeach
                </div>
                <div class="flex gap-2 border-t border-slate-200 px-5 py-4">
                    <button wire:click="$set('showPay', false)" class="btn btn-secondary">Kembali</button>
                    <button wire:click="checkout" wire:loading.attr="disabled" class="btn btn-primary btn-lg flex-1 h-12">
                        <span wire:loading.remove wire:target="checkout">Konfirmasi pembayaran</span>
                        <span wire:loading wire:target="checkout">Memproses…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= MODAL: transaksi berhasil ================= --}}
    @if ($this->lastTrx)
        @php($lt = $this->lastTrx)
        @php($digital = \Illuminate\Support\Facades\URL::signedRoute('receipt.public', $lt))
        <div class="modal-backdrop" role="dialog" aria-modal="true">
            <div class="modal sm:max-w-sm">
                <div class="px-6 pt-6 text-center">
                    <div class="mx-auto grid size-12 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    </div>
                    <h3 class="mt-3 text-base font-semibold">Pembayaran berhasil</h3>
                    <p class="text-xs text-slate-500 tabular-nums">{{ $lt->number }}</p>
                    <div class="mt-4 text-3xl font-bold tabular-nums tracking-tight">{{ $rp($lt->total) }}</div>
                    @if ($lt->change_amount > 0)
                        <div class="mt-3 flex items-center justify-between rounded-lg bg-emerald-50 px-4 py-2.5 text-emerald-800">
                            <span class="text-sm font-medium">Kembalian</span>
                            <span class="text-lg font-semibold tabular-nums">{{ $rp($lt->change_amount) }}</span>
                        </div>
                    @endif
                </div>
                <div class="grid grid-cols-2 gap-2 px-6 pt-5">
                    <a href="{{ route('receipt', $lt) }}?print=1" target="_blank" class="btn btn-secondary">Cetak struk</a>
                    <a href="https://wa.me/?text={{ rawurlencode('Struk Jelly Potter '.$lt->number.': '.$digital) }}" target="_blank" class="btn btn-secondary">Struk digital</a>
                </div>
                <div class="p-6 pt-3">
                    <button wire:click="newOrder" class="btn btn-primary btn-lg w-full">Pesanan baru</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= MODAL: konfirmasi tutup shift ================= --}}
    @if ($confirmingClose && $this->shift)
        @php($exp = $this->shift->computeExpectedCash())
        @php($diff = (int) $actualCash - $exp)
        <div class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="close-title">
            <div class="modal sm:max-w-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 id="close-title" class="text-base font-semibold">Tutup shift sekarang?</h3>
                    <p class="mt-1 text-xs text-slate-500">Pastikan uang di laci sudah dihitung. Shift yang ditutup tidak bisa dibuka lagi.</p>
                </div>
                <dl class="space-y-2 px-5 py-4 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Cash seharusnya</dt><dd class="font-medium tabular-nums">{{ $rp($exp) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Cash fisik</dt><dd class="font-medium tabular-nums">{{ $rp((int) $actualCash) }}</dd></div>
                    <div class="flex items-center justify-between rounded-lg px-3 py-2.5 {{ $diff === 0 ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800' }}">
                        <dt class="font-medium">Selisih</dt><dd class="text-base font-semibold tabular-nums">{{ $rp($diff) }}</dd>
                    </div>
                </dl>
                <div class="flex gap-2 border-t border-slate-200 px-5 py-4">
                    <button type="button" wire:click="$set('confirmingClose', false)" class="btn btn-secondary">Batal</button>
                    <button type="button" wire:click="closeShift" wire:loading.attr="disabled" class="btn btn-danger flex-1">Ya, tutup shift</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= MODAL: PIN owner/manager ================= --}}
    @if ($pinAction)
        <div class="modal-backdrop z-50" x-data="{ pin: @entangle('pin') }" role="dialog" aria-modal="true" aria-labelledby="pin-title">
            <form wire:submit="confirmPin" class="modal sm:max-w-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <svg class="size-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                        <h3 id="pin-title" class="text-base font-semibold">{{ ['price' => 'Ubah harga', 'discount' => 'Diskon manual', 'void' => 'Void transaksi', 'refund' => 'Refund transaksi'][$pinAction] }}</h3>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Butuh PIN owner/manager. Tercatat atas nama {{ auth()->user()->name }}.</p>
                </div>
                <div class="space-y-4 px-5 py-4">
                    @if (in_array($pinAction, ['price', 'discount']))
                        <div>
                            <label class="label" for="pinValue">{{ $pinAction === 'price' ? 'Harga dasar baru per cup' : 'Potongan' }}</label>
                            <input id="pinValue" type="number" inputmode="numeric" wire:model="pinValue" placeholder="Rp" class="input h-11 tabular-nums">
                            @error('pinValue') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div>
                            <label class="label" for="pinReason">Alasan</label>
                            <input id="pinReason" wire:model="pinReason" placeholder="mis. salah input pesanan" class="input h-11">
                            @error('pinReason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div>
                        <div class="flex justify-center gap-3 py-2" aria-hidden="true">
                            <template x-for="i in 6"><span class="size-3 rounded-full" :class="i <= (pin || '').length ? 'bg-indigo-600' : 'bg-slate-200'"></span></template>
                        </div>
                        @error('pin') <p class="text-center text-sm font-medium text-rose-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ([1,2,3,4,5,6,7,8,9] as $d)
                            <button type="button" @click="pin = (pin || '') + '{{ $d }}'" class="keypad-key h-12">{{ $d }}</button>
                        @endforeach
                        <button type="button" @click="pin = (pin || '').slice(0, -1)" class="keypad-key h-12 text-sm text-slate-500">Hapus</button>
                        <button type="button" @click="pin = (pin || '') + '0'" class="keypad-key h-12">0</button>
                        <button class="btn btn-primary h-12">OK</button>
                    </div>
                </div>
                <div class="border-t border-slate-200 px-5 py-3">
                    <button type="button" wire:click="cancelPin" class="btn btn-ghost w-full">Batal</button>
                </div>
            </form>
        </div>
    @endif
</div>
