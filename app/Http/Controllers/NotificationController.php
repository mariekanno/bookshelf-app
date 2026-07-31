<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    public function read(DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->notifiable_id === auth()->id(),
            403
        );

        $notification->markAsRead();

        return redirect()
            ->route('notifications.index');
    }
}
