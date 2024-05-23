<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    // TODO: Add proper role middlewares where appropariate
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::get('appointments/{id}', [AppointmentController::class, 'show']);
    Route::post('appointments', [AppointmentController::class, 'store'])->middleware('role:patient');
    Route::put('appointments/confirmAppointment/{appointmentId}', [AppointmentController::class, 'confirmAppointment'])->middleware('role:doctor');
    Route::put('appointments/unConfirmAppointment/{appointmentId}', [AppointmentController::class, 'unConfirmAppointment'])->middleware('role:doctor');
    Route::get('doctors', [DoctorController::class, 'getDoctorsList']);
    Route::get('appointments/getForDoctorAndCurrentUser/{doctorId}', [AppointmentController::class, 'getAppointmentsForDoctorAndCurrentUser']);
    Route::get('getUser', [UserProfileController::class, 'user']);
    Route::post('updateProfile', [UserProfileController::class, 'updateProfile']);
});
