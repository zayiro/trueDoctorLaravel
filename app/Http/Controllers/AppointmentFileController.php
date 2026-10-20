<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppointmentFileController extends Controller
{
    private function authorizeAccess(Appointment $appointment): void
    {
        $userId = auth()->id();

        abort_unless(
            $appointment->doctor?->user_id === $userId
            || $appointment->patient?->user_id === $userId,
            403
        );
    }

    public function index(Appointment $appointment)
    {
        $this->authorizeAccess($appointment);

        $files = $appointment->files()
            ->with('uploader:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn ($f) => [
                'id'            => $f->id,
                'name'          => $f->original_name,
                'is_image'      => str_starts_with($f->mime, 'image/'),
                'size'          => number_format($f->size / 1024, 0) . ' KB',
                'uploaded_by'   => $f->uploaded_by,
                'uploader_name' => $f->uploader?->name,
                'time'          => $f->created_at->diffForHumans(),
                'url'           => route('appointments.files.show', [$appointment, $f]),
                'download_url'  => route('appointments.files.show', [$appointment, $f, 'download' => 1]),
            ]);

        return response()->json(['files' => $files]);
    }

    public function store(Request $request, Appointment $appointment)
    {
        $this->authorizeAccess($appointment);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'file.mimes' => 'Solo se permiten PDF, JPG o PNG.',
            'file.max'   => 'El archivo no puede superar 10 MB.',
        ]);

        $file = $request->file('file');

        $record = $appointment->files()->create([
            'uploaded_by'   => auth()->id(),
            'original_name' => $file->getClientOriginalName(),
            'path'          => $file->store("appointment-files/{$appointment->id}"),
            'mime'          => $file->getMimeType(),
            'size'          => $file->getSize(),
        ]);

        return response()->json(['ok' => true, 'id' => $record->id], 201);
    }

    public function show(Request $request, Appointment $appointment, AppointmentFile $file)
    {
        $this->authorizeAccess($appointment);
        abort_unless($file->appointment_id === $appointment->id, 404);

        if ($request->boolean('download')) {
            return Storage::download($file->path, $file->original_name);
        }

        return Storage::response($file->path, $file->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}