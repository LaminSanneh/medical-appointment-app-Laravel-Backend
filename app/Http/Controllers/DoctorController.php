<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function getDoctorsList()
    {
        return User::where('doctor_id_number', '!=', '')->get();
    }
}
