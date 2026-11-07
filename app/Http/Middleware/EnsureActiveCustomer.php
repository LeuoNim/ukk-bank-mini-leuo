<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user();
        if (! $customer instanceof Customer || $customer->status !== 'Aktif') {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Rekening belum aktif atau diblokir. Hubungi teller.'], 403);
            }
            auth('nasabah')->logout();

            return redirect()->route('login')->withErrors(['username' => 'Rekening belum aktif atau diblokir. Hubungi teller.']);
        }

        return $next($request);
    }
}
