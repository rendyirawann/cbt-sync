<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookBorrowing;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'book_id' => 'required|uuid|exists:books,id',
            'borrowed_at' => 'required|date',
            'due_date' => 'required|date|after_or_equal:borrowed_at',
        ]);

        try {
            $book = Book::findOrFail($request->book_id);

            // Check stock availability
            if ($book->available_stock <= 0) {
                return redirect()->back()->with('error', 'Gagal meminjam: Stok buku "' . $book->title . '" saat ini sedang kosong/habis dipinjam.');
            }

            // Create borrowing
            BookBorrowing::create([
                'student_id' => $request->student_id,
                'book_id' => $request->book_id,
                'borrowed_at' => $request->borrowed_at,
                'due_date' => $request->due_date,
                'status' => 'borrowed',
            ]);

            // Decrement available stock
            $book->decrement('available_stock');

            return redirect()->back()->with('success', 'Transaksi peminjaman buku berhasil didaftarkan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses peminjaman: ' . $e->getMessage());
        }
    }

    public function returnBook($id)
    {
        try {
            $borrowing = BookBorrowing::findOrFail($id);
            if ($borrowing->returned_at) {
                return redirect()->back()->with('error', 'Buku ini sudah dikembalikan sebelumnya.');
            }

            $returnedDate = Carbon::now()->toDateString();
            $status = 'returned';

            // Check if returned past due date
            if (Carbon::now()->gt(Carbon::parse($borrowing->due_date))) {
                $status = 'overdue'; // Flag it as overdue return or keep returned with status adjustments
            }

            $borrowing->update([
                'returned_at' => $returnedDate,
                'status' => 'returned' // standard returned flag
            ]);

            // Increment available stock
            $borrowing->book->increment('available_stock');

            return redirect()->back()->with('success', 'Buku "' . $borrowing->book->title . '" berhasil dikembalikan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pengembalian buku: ' . $e->getMessage());
        }
    }
}
