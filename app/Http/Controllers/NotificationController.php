<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        // Traemos todas las notificaciones paginadas (15 por página)
        $notifications = $user->notifications()->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        auth()->user()->unreadNotifications->where('id', $id)->markAsRead();
        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
    }
}
