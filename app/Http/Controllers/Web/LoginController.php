<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('home');
        }
        if (Auth::guard('nasabah')->check()) {
            return redirect()->route('nasabah.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        $loginId = trim($request->username);
        $password = $request->password;

        // 1. Cek apakah akun adalah Staf (Administrator / Supervisor / Teller)
        if (Auth::guard('web')->attempt(['username' => $loginId, 'password' => $password])) {
            if (Auth::guard('web')->user()->status !== 'Aktif') {
                Auth::guard('web')->logout();

                return back()->withErrors([
                    'username' => 'Akun staf Anda telah dinonaktifkan.',
                ])->onlyInput('username');
            }
            $request->session()->regenerate();

            $request->session()->forget('url.intended');

            return redirect()->route('home');
        }

        // 2. Cek apakah akun adalah Siswa Nasabah (berdasarkan NIS atau Nomor Rekening)
        $customer = Customer::where('student_id', $loginId)
            ->orWhere('account_number', $loginId)
            ->first();

        if ($customer) {
            if ($customer->status !== 'Aktif') {
                return back()->withErrors([
                    'username' => 'Akun rekening nasabah telah dinonaktifkan.',
                ])->onlyInput('username');
            }

            $valid = false;
            if (! empty($customer->password) && Hash::check($password, $customer->password)) {
                $valid = true;
            } elseif (! empty($customer->pin) && Hash::check($password, $customer->pin)) {
                $valid = true;
            }

            if ($valid) {
                Auth::guard('nasabah')->login($customer);
                $request->session()->regenerate();
                $request->session()->put('password_hash_nasabah', $customer->getAuthPassword());

                $request->session()->forget('url.intended');

                return redirect()->route('nasabah.dashboard');
            }
        }

        return back()->withErrors([
            'username' => 'Identitas pengguna (Username/NIS) atau Password/PIN salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        if (Auth::guard('nasabah')->check()) {
            Auth::guard('nasabah')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showLoginNasabah()
    {
        return redirect()->route('login');
    }

    public function loginNasabah(Request $request)
    {
        $request->validate([
            'student_id' => ['required'],
        ]);

        $secret = $request->input('password') ?? $request->input('pin');
        if (! $secret) {
            return back()->withErrors(['student_id' => 'Password atau PIN wajib diisi.'])->onlyInput('student_id');
        }

        $customer = Customer::where('student_id', $request->student_id)
            ->orWhere('account_number', $request->student_id)
            ->first();

        if (! $customer) {
            return back()->withErrors(['student_id' => 'Data nasabah tidak ditemukan.'])->onlyInput('student_id');
        }

        if ($customer->status !== 'Aktif') {
            return back()->withErrors(['student_id' => 'Akun nasabah telah dinonaktifkan.'])->onlyInput('student_id');
        }

        // Cek autentikasi: periksa kecocokan Password ATAU PIN
        $valid = false;
        if (! empty($customer->password) && Hash::check($secret, $customer->password)) {
            $valid = true;
        } elseif (! empty($customer->pin) && Hash::check($secret, $customer->pin)) {
            $valid = true;
        }

        if ($valid) {
            Auth::guard('nasabah')->login($customer);
            $request->session()->regenerate();
            $request->session()->put('password_hash_nasabah', $customer->getAuthPassword());

            $request->session()->forget('url.intended');

            return redirect()->route('nasabah.dashboard');
        }

        return back()->withErrors([
            'student_id' => 'Kredensial login nasabah (Password / PIN) salah.',
        ])->onlyInput('student_id');
    }

    public function showForgotPasswordNasabah()
    {
        return view('auth.forgot_password_nasabah');
    }

    public function processForgotPasswordNasabah(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
            'account_number' => 'required|string',
            'reset_type' => 'required|in:password,pin',
            'new_password' => 'nullable|string|min:6|confirmed',
            'new_pin' => 'nullable|numeric|digits:6|confirmed',
        ], [
            'student_id.required' => 'NIS Siswa wajib diisi.',
            'account_number.required' => 'Nomor Rekening wajib diisi.',
            'new_password.min' => 'Password baru minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'new_pin.digits' => 'PIN baru harus tepat 6 digit angka.',
            'new_pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.',
        ]);

        $resetType = $request->input('reset_type');
        if ($resetType === 'password' && empty($request->input('new_password'))) {
            return back()->withErrors(['new_password' => 'Password baru wajib diisi.'])->withInput();
        }
        if ($resetType === 'pin' && empty($request->input('new_pin'))) {
            return back()->withErrors(['new_pin' => 'PIN transaksi baru wajib diisi.'])->withInput();
        }

        // Cari nasabah berdasarkan kombinasi NIS dan Nomor Rekening
        $customer = Customer::where('student_id', trim($request->input('student_id')))
            ->where('account_number', trim($request->input('account_number')))
            ->first();

        if (! $customer) {
            return back()->withErrors([
                'student_id' => 'Kombinasi NIS dan Nomor Rekening tidak cocok dengan data kami.',
            ])->withInput();
        }

        if ($customer->status !== 'Aktif') {
            return back()->withErrors([
                'student_id' => 'Akun nasabah belum aktif atau sedang dinonaktifkan. Silakan hubungi teller.',
            ])->withInput();
        }

        if ($resetType === 'password') {
            $customer->password = Hash::make($request->input('new_password'));
            \Illuminate\Support\Facades\Log::info("Nasabah {$customer->student_id} mereset password login secara mandiri.");
            $msg = 'Password login berhasil diperbarui! Silakan masuk dengan password baru Anda.';
        } else {
            $customer->pin = Hash::make($request->input('new_pin'));
            \Illuminate\Support\Facades\Log::info("Nasabah {$customer->student_id} mereset PIN transaksi secara mandiri.");
            $msg = 'PIN transaksi berhasil diperbarui!';
        }

        $customer->save();
        $customer->tokens()->delete();

        return redirect()->route('login')->with('success', $msg);
    }

    public function logoutNasabah(Request $request)
    {
        Auth::guard('nasabah')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/nasabah/login');
    }
}
