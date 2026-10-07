<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\URL;

class ReceiptController extends Controller
{
    public function show(Transaction $transaction)
    {
        $transaction->load(['items.toppings', 'outlet', 'cashier']);

        return view('pos.receipt', [
            'trx' => $transaction,
            'footer' => Setting::get('receipt_footer'),
            'digitalUrl' => URL::signedRoute('receipt.public', $transaction),
            'print' => request()->boolean('print'),
        ]);
    }
}
