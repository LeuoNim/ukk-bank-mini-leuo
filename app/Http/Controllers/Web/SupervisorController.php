<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function dashboard()
    {
        return view('supervisor.dashboard');
    }

    public function approval()
    {
        return view('supervisor.approval');
    }

    public function audit()
    {
        return view('supervisor.audit');
    }
}
