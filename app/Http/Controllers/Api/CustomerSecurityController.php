<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerSecurityController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'current_pin' => 'required|digits_between:4,6',
            'new_password' => 'nullable|required_without:new_pin|string|min:6|confirmed',
            'new_pin' => 'nullable|required_without:new_password|digits:6|confirmed',
        ]);
        DB::transaction(function () use ($request, $data): void {
            $customer = Customer::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($customer->status !== 'Aktif' || ! Hash::check($data['current_password'], $customer->getAuthPassword())
                || ! Hash::check($data['current_pin'], $customer->pin)) {
                throw ValidationException::withMessages(['current_password' => 'Password/PIN saat ini salah atau rekening tidak aktif.']);
            }
            if (! empty($data['new_password'])) {
                $customer->password = Hash::make($data['new_password']);
            }
            if (! empty($data['new_pin'])) {
                $customer->pin = Hash::make($data['new_pin']);
            }
            $customer->save();
            $customer->tokens()->delete();
        });

        return response()->json(['success' => true, 'message' => 'Perubahan berhasil disimpan. Silakan login kembali.']);
    }
}
