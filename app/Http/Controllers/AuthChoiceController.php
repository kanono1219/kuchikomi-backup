<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthChoiceController extends Controller
{
    public function show()
    {
        return view('auth.choice');
    }
}