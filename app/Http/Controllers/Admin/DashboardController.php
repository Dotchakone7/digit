<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Figures are computed (and refreshed) by App\Livewire\Admin\Dashboard.
        return view('admin.dashboard');
    }
}
