@php($rp = fn ($v) => \App\Services\ReportService::rp($v))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $trx->number }}</title>
    <style>
        body { font-family: ui-monospace, Menlo, Consolas, monospace; background: #f5f3ff; margin: 0; padding: 16px; color: #111; }
        .r { width: 300px; max-width: 100%; margin: 0 auto; background: #fff; padding: 16px; font-size: 13px; }
        .c { text-align: center; } .row { display: flex; justify-content: space-between; gap: 8px; }
        hr { border: 0; border-top: 1px dashed #999; margin: 8px 0; } .b { font-weight: 700; } .s { font-size: 11px; color: #555; }
        .void { text-align:center; font-size: 22px; font-weight: 800; color: #c00; border: 2px solid #c00; margin: 6px 0; }
        .actions { text-align: center; margin-top: 12px; } .actions button { padding: 10px 18px; font-size: 15px; border-radius: 8px; border: 0; background: #7c3aed; color: #fff; }
        @media print { body { background: #fff; padding: 0; } .r { width: 58mm; padding: 0; } .actions { display: none; } }
    </style>
</head>
<body @if($print) onload="window.print()" @endif>
<div class="r">
    <div class="c b" style="font-size:16px">{{ $trx->outlet->receiptTitle() }}</div>
    @if ($trx->outlet->address)<div class="c s">{{ $trx->outlet->address }}</div>@endif
    @if ($trx->outlet->phone)<div class="c s">HP/WA: {{ $trx->outlet->phone }}</div>@endif
    <hr>
    <div class="row s"><span>{{ $trx->number }}</span><span>{{ $trx->created_at->format('d/m/Y H:i') }}</span></div>
    <div class="row s"><span>Kasir: {{ $trx->cashier->name }}</span><span>{{ \App\Models\Transaction::CHANNELS[$trx->channel] }}</span></div>
    @if ($trx->status !== 'paid') <div class="void">{{ strtoupper(\App\Models\Transaction::STATUSES[$trx->status]) }}</div> @endif
    <hr>
    @foreach ($trx->items as $i)
        <div>{{ $i->menu_name }}{{ $i->size ? ' ('.$i->size.')' : '' }}</div>
        @foreach ($i->toppings as $t) <div class="s">&nbsp;+ {{ $t->topping_name }}</div> @endforeach
        <div class="row"><span>&nbsp;{{ $i->qty }} x {{ number_format($i->price_each, 0, ',', '.') }}</span><span>{{ number_format($i->subtotal, 0, ',', '.') }}</span></div>
    @endforeach
    <hr>
    <div class="row"><span>Subtotal</span><span>{{ $rp($trx->subtotal) }}</span></div>
    @if ($trx->discount) <div class="row"><span>Diskon {{ $trx->discount_label }}</span><span>-{{ $rp($trx->discount) }}</span></div> @endif
    <div class="row b" style="font-size:15px"><span>TOTAL</span><span>{{ $rp($trx->total) }}</span></div>
    <div class="row"><span>{{ \App\Models\Transaction::METHODS[$trx->payment_method] }}</span><span>{{ $rp($trx->paid_amount) }}</span></div>
    @if ($trx->change_amount) <div class="row"><span>Kembali</span><span>{{ $rp($trx->change_amount) }}</span></div> @endif
    <hr>
    <div class="c s">{{ $footer }}</div>
</div>
<div class="actions"><button onclick="window.print()">Cetak</button></div>
</body>
</html>
