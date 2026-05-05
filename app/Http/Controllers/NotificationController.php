<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    public function index()
    {
        // Retorna todas las no leídas (usado por la campana)
        return response()->json(
            auth()->user()->unreadNotifications()->latest()->take(4)->get()
        );
    }

    public function markAsRead(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back();
    }

    public function updatePreferences()
    {
        $data = request()->validate([
            'sound' => 'boolean',
            'browser' => 'boolean',
        ]);

        $user = auth()->user();
        $preferences = $user->preferences ?? [];

        if (isset($data['sound'])) {
            $preferences['sound'] = $data['sound'];
        }
        if (isset($data['browser'])) {
            $preferences['browser'] = $data['browser'];
        }

        $user->preferences = $preferences;
        $user->save();

        return back();
    }
}
