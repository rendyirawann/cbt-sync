<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ModuleComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleCommentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'learning_module_id' => 'required|exists:learning_modules,id',
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:module_comments,id'
        ]);

        $comment = ModuleComment::create([
            'learning_module_id' => $request->learning_module_id,
            'user_id' => Auth::id(),
            'parent_id' => $request->parent_id,
            'comment' => $request->comment
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Komentar berhasil dikirim!',
            'data' => [
                'id'          => $comment->id,
                'user_name'   => Auth::user()->name,
                'avatar_url'  => Auth::user()->avatar_url,
                'comment'     => $comment->comment,
                'created_at'  => $comment->created_at->diffForHumans()
            ]
        ]);
    }
}
