<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function pengaturan()
    {
        return view('admin.pengaturan');
    }

    public function nasabah(Request $request)
    {
        $query = \App\Models\Customer::query();
        if ($request->search) {
            $query->where('student_name', 'like', "%{$request->search}%")
                  ->orWhere('student_id', 'like', "%{$request->search}%")
                  ->orWhere('account_number', 'like', "%{$request->search}%");
        }
        $customers = $query->orderBy('id', 'desc')->get();
        return view('admin.nasabah', compact('customers'));
    }

    public function tambahNasabah()
    {
        return view('admin.nasabah_form');
    }

    public function simpanNasabah(Request $request)
    {
        $request->validate([
            'student_id' => 'required|unique:customers',
            'student_name' => 'required|string',
            'class' => 'required|string',
            'pin' => 'required|digits:6',
        ]);

        \App\Models\Customer::create([
            'account_number' => 'REK-' . time(),
            'student_id' => $request->student_id,
            'student_name' => $request->student_name,
            'class' => $request->class,
            'balance' => 0,
            'pin' => bcrypt($request->pin)
        ]);

        return redirect()->route('admin.nasabah')->with('success', 'Nasabah berhasil ditambahkan.');
    }

    public function editNasabah($id)
    {
        $customer = \App\Models\Customer::findOrFail($id);
        return view('admin.nasabah_form', compact('customer'));
    }

    public function updateNasabah(Request $request, $id)
    {
        $customer = \App\Models\Customer::findOrFail($id);
        
        $request->validate([
            'student_id' => 'required|unique:customers,student_id,'.$id,
            'student_name' => 'required|string',
            'class' => 'required|string',
            'pin' => 'nullable|digits:6',
        ]);

        $customer->update([
            'student_id' => $request->student_id,
            'student_name' => $request->student_name,
            'class' => $request->class,
        ]);

        if ($request->filled('pin')) {
            $customer->update(['pin' => bcrypt($request->pin)]);
        }

        return redirect()->route('admin.nasabah')->with('success', 'Data Nasabah berhasil diupdate.');
    }

    public function hapusNasabah($id)
    {
        \App\Models\Customer::findOrFail($id)->delete();
        return redirect()->route('admin.nasabah')->with('success', 'Nasabah berhasil dihapus.');
    }

    public function cetakQrNasabah($id)
    {
        $customer = \App\Models\Customer::findOrFail($id);
        return view('admin.cetak_qr', compact('customer'));
    }

    public function petugas()
    {
        $users = \App\Models\User::orderBy('role')->orderBy('staff_name')->get();
        return view('admin.petugas', compact('users'));
    }

    public function tambahPetugas()
    {
        return view('admin.petugas_form');
    }

    public function simpanPetugas(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'staff_name' => 'required|string',
            'role' => 'required|in:Administrator,Supervisor,Teller',
            'password' => 'required|min:6',
        ]);

        \App\Models\User::create([
            'username' => $request->username,
            'staff_name' => $request->staff_name,
            'role' => $request->role,
            'password' => bcrypt($request->password)
        ]);

        return redirect()->route('admin.petugas')->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function editPetugas($id)
    {
        $user = \App\Models\User::findOrFail($id);
        return view('admin.petugas_form', compact('user'));
    }

    public function updatePetugas(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);
        
        $request->validate([
            'username' => 'required|unique:users,username,'.$id,
            'staff_name' => 'required|string',
            'role' => 'required|in:Administrator,Supervisor,Teller',
            'password' => 'nullable|min:6',
        ]);

        $user->update([
            'username' => $request->username,
            'staff_name' => $request->staff_name,
            'role' => $request->role,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($request->password)]);
        }

        return redirect()->route('admin.petugas')->with('success', 'Data Petugas berhasil diupdate.');
    }

    public function hapusPetugas($id)
    {
        $user = \App\Models\User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.petugas')->with('error', 'Tidak dapat menghapus akun Anda sendiri.');
        }
        $user->delete();
        return redirect()->route('admin.petugas')->with('success', 'Petugas berhasil dihapus.');
    }

    public function audit()
    {
        $journals = \App\Models\Accounting_Journal::with(['transaction.customer', 'transaction.user'])
                    ->orderBy('id', 'desc')
                    ->get();
        $total_debet = \App\Models\Accounting_Journal::where('position', 'Debet')->sum('amount');
        $total_kredit = \App\Models\Accounting_Journal::where('position', 'Kredit')->sum('amount');
        
        return view('admin.audit', compact('journals', 'total_debet', 'total_kredit'));
    }
}
