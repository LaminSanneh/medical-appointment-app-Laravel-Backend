<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('patient_id'); // For patient
            $table->unsignedBigInteger('doctor_id');
            $table->dateTime('appointment_date_time');
            $table->dateTime('end_time');
            $table->boolean('confirmed')->default(false);
            $table->timestamps();
            
            $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
