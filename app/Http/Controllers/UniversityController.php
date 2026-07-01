<?php

namespace App\Http\Controllers;

use App\Models\University;
use Illuminate\Http\Request;

class UniversityController extends Controller
{
    public function index()
    {
        $universities = University::with('programs')->get();
        return  response()->json($universities, 200);
    }
}
