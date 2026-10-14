<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeStayController extends Controller
{
    public function homeStay()
    {
        return view('homeStay.homeStay');
    }
}
