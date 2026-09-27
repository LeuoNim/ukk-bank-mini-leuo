<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function nasabah()
    {
        return view('admin.nasabah');
    }

    public function petugas()
    {
        return view('admin.petugas');
    }

    public function audit()
    {
        return view('admin.audit');
    }
}
