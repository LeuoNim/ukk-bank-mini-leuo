<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerOnboarding;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // menampilkan daftar data nasabah
    public function index(Request $request)
    {
        $query = Customer::query();

        // filter berdasarkan kelas jika ada
        if ($request->has('class')) {
            $query->where('class', $request->class);
        }

        $customers = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar data nasabah berhasil diambil',
            'data' => $customers,
        ], 200);
    }

    // membuat rekening nasabah baru
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|string|max:50|unique:customers',
            'student_name' => 'required|string|max:150',
            'class' => 'required|string|max:50',
            'initial_deposit' => 'nullable|integer|in:0',
        ]);
        $customer = app(CustomerOnboarding::class)->register($data['student_id'], $data['student_name'], $data['class']);

        return response()->json(['success' => true, 'message' => 'Nasabah belum aktif. Aktivasi dan setoran awal melalui teller.', 'data' => $customer], 201);
    }

    // menampilkan detail satu data nasabah
    public function show($id)
    {
        $customer = Customer::with(['transactions' => function ($q) {
            $q->orderBy('id', 'desc')->take(10);
        }])->find($id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data nasabah tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail data nasabah berhasil diambil',
            'data' => $customer,
        ], 200);
    }

    // mengupdate data nasabah
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data nasabah tidak ditemukan',
            ], 404);
        }

        // 1. Validasi input
        $request->validate([
            'student_id' => 'required|string|unique:customers,student_id,'.$id,
            'student_name' => 'required|string|max:150',
            'class' => 'required|string|max:50',
            'pin' => 'prohibited',
        ]);

        // 2. Data yang akan diupdate
        $data = [
            'student_id' => $request->student_id,
            'student_name' => $request->student_name,
            'class' => $request->class,
        ];

        $customer->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data nasabah berhasil diperbarui',
            'data' => $customer,
        ], 200);
    }

    // menghapus data rekening nasabah
    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Data nasabah tidak ditemukan',
            ], 404);
        }

        // cek jika nasabah masih ada saldo
        if ($customer->balance > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus rekening nasabah yang masih memiliki saldo',
            ], 400);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data nasabah berhasil dihapus',
        ], 200);
    }

    // cari nasabah berdasarkan nama, NIS, atau no rekening (untuk loket teller)
    public function search(Request $request)
    {
        $keyword = $request->query('q');

        if (! $keyword) {
            return response()->json([
                'success' => false,
                'message' => 'Kata kunci pencarian tidak boleh kosong',
            ], 400);
        }

        $customers = Customer::where('student_name', 'like', "%{$keyword}%")
            ->orWhere('student_id', 'like', "%{$keyword}%")
            ->orWhere('account_number', 'like', "%{$keyword}%")
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Hasil pencarian nasabah berhasil diambil',
            'data' => $customers,
        ], 200);
    }

    // cari nasabah dari no rekening (scan qr code)
    public function findByAccountNumber($account_number)
    {
        $customer = Customer::where('account_number', $account_number)->first();

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'Rekening nasabah tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data rekening nasabah berhasil ditemukan',
            'data' => $customer,
        ], 200);
    }

    // helper generate nomor rekening otomatis
    public static function generateAccountNumber(string $class): string
    {
        $upperClass = strtoupper($class);
        $prefix = 'BM';

        if (str_contains($upperClass, 'AKL') || str_contains($upperClass, 'AK')) {
            $prefix = 'AKL';
        } elseif (str_contains($upperClass, 'LPS') || str_contains($upperClass, 'PS')) {
            $prefix = 'LPS';
        } elseif (str_contains($upperClass, 'RPL')) {
            $prefix = 'RPL';
        } elseif (str_contains($upperClass, 'TKJ')) {
            $prefix = 'TKJ';
        } elseif (str_contains($upperClass, 'DKV')) {
            $prefix = 'DKV';
        } elseif (str_contains($upperClass, 'OTKP') || str_contains($upperClass, 'MPLB') || str_contains($upperClass, 'MP')) {
            $prefix = 'MP';
        } elseif (str_contains($upperClass, 'BDP') || str_contains($upperClass, 'RITEL') || str_contains($upperClass, 'BR')) {
            $prefix = 'BR';
        } elseif (str_contains($upperClass, 'DIGITAL') || str_contains($upperClass, 'BD')) {
            $prefix = 'BD';
        }

        $count = Customer::where('account_number', 'like', "{$prefix}-%")->count() + 1;
        $accountNumber = sprintf('%s-%04d', $prefix, $count);

        while (Customer::where('account_number', $accountNumber)->exists()) {
            $count++;
            $accountNumber = sprintf('%s-%04d', $prefix, $count);
        }

        return $accountNumber;
    }
}
