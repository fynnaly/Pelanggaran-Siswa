<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Tandai semua notifikasi yang belum dibaca menjadi sudah dibaca. */
    public function markRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}