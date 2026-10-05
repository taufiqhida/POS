<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class PosAuthController extends Controller
{
    public function show()
    {
        if (Auth::check() && session('pos_outlet_id')) {
            return redirect()->route('pos');
        }

        return view('pos.login', [
            'outlets' => Outlet::orderBy('name')->get(),
            'users' => User::where('is_active', true)->whereNotNull('pin_hash')->orderBy('name')->get(['id', 'name', 'role', 'outlet_id']),
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'user_id' => 'required|exists:users,id',
            'pin' => 'required|digits_between:4,6',
        ]);

        $key = 'pin-login:'.$data['user_id'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['pin' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.'])->withInput();
        }

        $user = User::where('is_active', true)->find($data['user_id']);
        if (! $user || ! $user->checkPin($data['pin'])) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['pin' => 'PIN salah.'])->withInput($request->except('pin'));
        }
        if ($user->role === 'kasir' && $user->outlet_id && $user->outlet_id !== $data['outlet_id']) {
            return back()->withErrors(['outlet_id' => 'Akun ini terdaftar di outlet '.$user->outlet->name.'.'])->withInput($request->except('pin'));
        }

        RateLimiter::clear($key);
        Auth::login($user);
        $request->session()->regenerate();
        session(['pos_outlet_id' => $data['outlet_id']]);
        AuditLog::record('login', $user->id, 'outlet', $data['outlet_id']);

        return redirect()->route('pos');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pos.login');
    }
}
