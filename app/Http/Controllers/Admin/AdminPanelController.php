<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminPanelController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.app');
    }
}
