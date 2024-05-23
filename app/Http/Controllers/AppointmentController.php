<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        $userId = $user->id;
        if ($user->isDoctor()) {
            $appointments = Appointment::with(['patient', 'doctor'])->where('doctor_id', $userId)->get();
        } else {
            $appointments = Appointment::with(['patient', 'doctor'])->where('patient_id', $userId)->get();
        }

        return response()->json($appointments);
    }

    public function getAppointmentsForDoctorAndCurrentUser(int $doctorId)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $userId = $user->id;
        $appointments = Appointment::with(['patient', 'doctor'])
                ->where('doctor_id', $doctorId)
                ->orWhere('patient_id', $userId)
                ->get();
        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $userId = \Illuminate\Support\Facades\Auth::user()->id;
        $request->validate([
            'doctor_id' => ['required','exists:users,id', function ($attribute, $value, $fail) use ($request) {
                    if (User::where('id', $request->doctor_id)
                        ->where('doctor_id_number', '!=', '')
                        ->count() !== 1) {
                        $fail('Id provided is not a doctor');
                    }
            }],
            'appointment_date_time' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    $overlappingAppointments = Appointment::where('doctor_id', $request->doctor_id)
                        ->where('appointment_date_time', '<=', $value)
                        ->where('end_time', '>', $value)
                        ->exists();

                    if ($overlappingAppointments) {
                        $fail('The selected time slot is already booked for the doctor.');
                    }
                },
                function ($attribute, $value, $fail) use ($request) {
                    $now = \Carbon\Carbon::now();
                    $givenDateTime = \Carbon\Carbon::parse($value);

                    if ($givenDateTime->isBefore($now)) {
                        $fail('The selected time slot is in the past. This is not allowed.');
                    }
                },
            ],
        ]);

        // Check for appointment conflict
        if (Appointment::where('doctor_id', $request->doctor_id)
            ->where('appointment_date_time', $request->appointment_date_time)
            ->exists())
        {
            return response()->json(['error' => 'Appointment conflict'], 409);
        }

        $request->merge([
            'appointment_date_time' => \Carbon\Carbon::parse($request->appointment_date_time)->setTimezone('UTC'),
            'end_time' => \Carbon\Carbon::parse($request->appointment_date_time)->setTimezone('UTC')->addMinutes(60),
        ]);

        $data = $request->all();
        $data['patient_id'] = $userId;
        $appointment = Appointment::create($data);

        return response()->json($appointment, 201);
    }

    public function show(int $id)
    {
        $appointment = Appointment::with(['patient', 'doctor'])->findOrFail($id);
        return $appointment->toArray();
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'appointment_date_time' => 'required|date',
        ]);

        $appointment = Appointment::findOrFail($id);
        $appointment->update($request->all());
        return $appointment;
    }

    public function confirmAppointment(int $appointmentId)
    {
        $appointment = Appointment::with(['patient', 'doctor'])->findOrFail($appointmentId);
        $appointment->update(['confirmed' => true]);
        return $appointment;
    }

    public function unConfirmAppointment(int $appointmentId)
    {
        $appointment = Appointment::with(['patient', 'doctor'])->findOrFail($appointmentId);
        $appointment->update(['confirmed' => false]);
        return $appointment;
    }

    public function updateStatus(Request $request, $appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $request->validate([
            'status' => 'required|in:completed,pending',
        ]);

        $appointment->update(['status' => $request->status]);

        return response()->json(['message' => 'Appointment status updated successfully']);
    }

    public function getAppointmentsByStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:completed,pending',
        ]);

        $status = $request->status;

        $appointments = Appointment::where('status', $status)->get();

        return response()->json($appointments);
    }

    public function reschedule(Request $request, int $id)
    {
        $appointment = Appointment::findOrFail($id);
        $request->validate([
            'appointment_date_time' => 'required|date',
        ]);

        $appointment->update($request->only('appointment_date_time'));

        return response()->json(['message' => 'Appointment rescheduled successfully']);
    }

    public function search(Request $request)
    {
        $query = Appointment::query();

        if ($request->has('user_name')) {
            $query->whereHas('user', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->user_name . '%');
            });
        }

        if ($request->has('doctor_name')) {
            $query->whereHas('doctor', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->doctor_name . '%');
            });
        }

        if ($request->has('appointment_date')) {
            $query->whereDate('appointment_date_time', $request->appointment_date);
        }

        $appointments = $query->get();

        return response()->json($appointments);
    }

    public function filterAppointments(Request $request)
    {
        $query = Appointment::query();

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('appointment_date_time', [$request->start_date, $request->end_date]);
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $appointments = $query->get();

        return response()->json($appointments);
    }

    public function appointmentHistory($userId)
    {
        $user = User::findOrFail($userId);
        $pastAppointments = $user->appointments()->where('appointment_date_time', '<', now())->get();
        $futureAppointments = $user->appointments()->where('appointment_date_time', '>', now())->get();

        return response()->json([
            'past_appointments' => $pastAppointments,
            'future_appointments' => $futureAppointments,
        ]);
    }

    public function appointmentStatistics()
    {
        $appointmentsPerDay = Appointment::selectRaw('DATE(appointment_date_time) as date, COUNT(*) as count')
                                         ->groupBy('date')
                                         ->get();

        // TODO: Consider listing appointments for an individual user
        $appointmentsPerDoctor = Appointment::selectRaw('doctor_id, COUNT(*) as count')
                                            ->groupBy('doctor_id')
                                            ->with('doctor')
                                            ->get();

        return response()->json([
            'appointments_per_day' => $appointmentsPerDay,
            'appointments_per_doctor' => $appointmentsPerDoctor,
        ]);
    }

    public function destroy(int $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return response()->json(['message' => 'Appointment deleted']);
    }

    public function confirm(int $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->update(['confirmed' => true]);
        return response()->json(['message' => 'Appointment confirmed']);
    }

    public function cancel(int $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return response()->json(['message' => 'Appointment canceled successfully']);
    }

    public function sendReminder(int $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->user->notify(new AppointmentReminder($appointment));
        return response()->json(['message' => 'Reminder sent']);
    }

    public function getAppointmentsForUser(int $id)
    {
        $user = User::findOrFail($id);
        $appointments = $user->appointments;
        return response()->json($appointments);
    }
}
