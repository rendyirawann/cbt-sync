<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAllAsRead()
    {
        if (auth()->check()) {
            auth()->user()->notifications()->where('is_read', false)->update(['is_read' => true]);
            return response()->json([
                'status' => 'success',
                'message' => 'Semua notifikasi ditandai telah dibaca.'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized'
        ], 401);
    }
}
