<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section heading="Pratinjau pesan">
            <div class="mb-3">
                <input type="date" wire:model.live="date" max="{{ today()->toDateString() }}" class="rounded-lg border-gray-300 dark:bg-white/5 dark:border-white/10">
            </div>
            <pre class="whitespace-pre-wrap rounded-xl bg-emerald-50 dark:bg-emerald-950/40 p-4 font-mono text-sm leading-relaxed">{{ $message }}</pre>
            <div class="mt-4 flex flex-wrap gap-2">
                @if ($wa)
                    <x-filament::button tag="a" href="{{ $wa }}" target="_blank" color="success" icon="heroicon-o-chat-bubble-left-right">Buka di WhatsApp</x-filament::button>
                @else
                    <p class="text-sm text-gray-500">Isi nomor WhatsApp owner di <a class="text-primary-600 underline" href="{{ \App\Filament\Pages\Settings::getUrl() }}">Pengaturan</a> untuk tombol kirim WhatsApp.</p>
                @endif
                <x-filament::button color="gray" x-on:click="navigator.clipboard.writeText(@js($message)); $tooltip('Tersalin')" icon="heroicon-o-clipboard">Salin</x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section heading="Pengiriman otomatis">
            <ul class="list-disc pl-5 space-y-2 text-sm">
                <li>Ringkasan harian dikirim otomatis setiap hari pukul <b>22:00</b> (perintah <code>report:daily</code> lewat scheduler).</li>
                <li>Ringkasan tutup shift dikirim otomatis setiap kali pegawai menutup shift.</li>
                <li>Peringatan stok kritis & selisih kas ikut di dalam ringkasan.</li>
                <li>Pengiriman memakai webhook gateway WhatsApp (mis. Fonnte) yang diatur di Pengaturan. Tanpa webhook, ringkasan tetap tercatat di log dan bisa dikirim manual dengan tombol WhatsApp.</li>
            </ul>
        </x-filament::section>
    </div>
</x-filament-panels::page>
