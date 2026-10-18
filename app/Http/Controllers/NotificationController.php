<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
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
        // 1. Buscamos la notificación específica dentro de todas las del usuario
        $notification = auth()->user()->notifications()->findOrFail($id);

        // 2. Si no está leída, la marcamos como leída
        if ($notification->unread()) {
            $notification->markAsRead();
        }

        // 3. Extraemos la URL de la cita médica guardada en el JSON
        $destinationUrl = $notification->data['action_url'] ?? null;

        // 4. Si existe la URL, redirigimos al usuario a la cita; si no, volvemos atrás
        if ($destinationUrl) {
            return redirect($destinationUrl);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
    }

    /**
     * Elimina una notificación específica del usuario autenticado.
     *
     * @param Request $request
     * @param string $id
     * @return RedirectResponse
     */
    public function destroyNotification(Request $request, string $id): RedirectResponse
    {
        try {
            // Buscar la notificación directamente en las notificaciones del usuario logueado
            $notification = $request->user()->notifications()->findOrFail($id);

            // Eliminar físicamente el registro de la tabla 'notifications'
            $notification->delete();

            return redirect()->back()->with('success', 'La notificación ha sido eliminada.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo eliminar la notificación.');
        }
    }
}
