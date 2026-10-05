<x-filament-panels::page>
    <x-filament::section>{{ $this->form }}</x-filament::section>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="Penjualan per outlet (terpisah & gabungan)">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th>Outlet</th><th class="text-right">Trx</th>@foreach ($channelLabels as $l)<th class="text-right">{{ $l }}</th>@endforeach<th class="text-right">Total</th></tr></thead>
                <tbody class="divide-y dark:divide-white/10">
                @foreach ($outlets as $r)
                    <tr><td class="py-2 font-semibold">{{ $r['outlet']->name }}</td><td class="text-right">{{ $r['count'] }}</td>
                        @foreach ($channelLabels as $k => $l)<td class="text-right">{{ $rp($r['by_channel'][$k] ?? 0) }}</td>@endforeach
                        <td class="text-right font-bold">{{ $rp($r['total']) }}</td></tr>
                @endforeach
                <tr class="font-bold"><td class="py-2">Gabungan</td><td class="text-right">{{ array_sum(array_column($outlets, 'count')) }}</td>
                    @foreach ($channelLabels as $k => $l)<td class="text-right">{{ $rp(array_sum(array_map(fn ($r) => $r['by_channel'][$k] ?? 0, $outlets))) }}</td>@endforeach
                    <td class="text-right text-primary-600">{{ $rp(array_sum(array_column($outlets, 'total'))) }}</td></tr>
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Produk terlaris (lintas kanal)">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th>#</th><th>Menu</th><th class="text-right">Terjual</th><th class="text-right">Omzet</th></tr></thead>
                <tbody class="divide-y dark:divide-white/10">
                @forelse ($top as $i => $r)
                    <tr><td class="py-1.5">{{ $i + 1 }}</td><td class="font-semibold">{{ $r->menu_name }}</td><td class="text-right">{{ $r->qty }}</td><td class="text-right">{{ $rp($r->omzet) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-400">Belum ada penjualan</td></tr>
                @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Performa & selisih kas per pegawai" class="lg:col-span-2">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th>Pegawai</th><th>Outlet</th><th class="text-right">Transaksi</th><th class="text-right">Penjualan</th><th class="text-right">Void/Refund</th><th class="text-right">Diskon diberi</th><th class="text-right">Shift</th><th class="text-right">Selisih kas</th></tr></thead>
                <tbody class="divide-y dark:divide-white/10">
                @foreach ($employees as $r)
                    <tr><td class="py-2 font-semibold">{{ $r['user']->name }}</td><td>{{ $r['user']->outlet?->name ?? '—' }}</td>
                        <td class="text-right">{{ $r['count'] }}</td><td class="text-right">{{ $rp($r['sales']) }}</td>
                        <td class="text-right {{ $r['void'] ? 'text-danger-600 font-bold' : '' }}">{{ $r['void'] }}</td>
                        <td class="text-right">{{ $rp($r['discount']) }}</td><td class="text-right">{{ $r['shifts'] }}</td>
                        <td class="text-right font-bold {{ $r['cash_diff'] < 0 ? 'text-danger-600' : ($r['cash_diff'] > 0 ? 'text-warning-600' : 'text-success-600') }}">{{ $rp($r['cash_diff']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Deteksi waste & selisih bahan" description="Pemakaian menurut resep vs pengurangan di luar penjualan (waste & koreksi stok fisik).">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th>Bahan</th><th class="text-right">Pakai (resep)</th><th class="text-right">Waste/selisih</th><th class="text-right">%</th></tr></thead>
                <tbody class="divide-y dark:divide-white/10">
                @forelse ($waste as $r)
                    @php($pct = $r['recipe_use'] > 0 ? round($r['waste'] / $r['recipe_use'] * 100, 1) : null)
                    <tr><td class="py-1.5">{{ $r['ingredient']->name }} <span class="text-xs text-gray-400">{{ $r['ingredient']->outlet->name }}</span></td>
                        <td class="text-right">{{ round($r['recipe_use'], 2) }} {{ $r['ingredient']->unit }}</td>
                        <td class="text-right {{ $r['waste'] > 0 ? 'text-danger-600 font-bold' : '' }}">{{ round($r['waste'], 2) }}</td>
                        <td class="text-right">{{ $pct !== null ? $pct.'%' : '—' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-400">Belum ada data</td></tr>
                @endforelse
                </tbody>
            </table>
        </x-filament::section>

        <x-filament::section heading="Prediksi kebutuhan stok (3 hari)" description="Berdasarkan rata-rata pemakaian 7 hari terakhir.">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-gray-500"><th>Bahan</th><th class="text-right">Pakai/hari</th><th class="text-right">Cukup</th><th class="text-right">Saran restock</th></tr></thead>
                <tbody class="divide-y dark:divide-white/10">
                @foreach ($forecast->take(20) as $r)
                    <tr><td class="py-1.5">{{ $r['ingredient']->name }} <span class="text-xs text-gray-400">{{ $r['ingredient']->outlet->name }}</span></td>
                        <td class="text-right">{{ $r['daily'] }} {{ $r['ingredient']->unit }}</td>
                        <td class="text-right {{ $r['days_left'] !== null && $r['days_left'] < 3 ? 'text-danger-600 font-bold' : '' }}">{{ $r['days_left'] !== null ? $r['days_left'].' hari' : '—' }}</td>
                        <td class="text-right font-semibold">{{ $r['restock'] > 0 ? '+'.$r['restock'].' '.$r['ingredient']->unit : '—' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
