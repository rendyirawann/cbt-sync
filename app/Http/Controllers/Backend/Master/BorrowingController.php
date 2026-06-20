<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookBorrowing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            // Bungkus dalam transaksi + kunci baris (lockForUpdate) agar dua
            // peminjaman bersamaan tidak menyebabkan stok minus (race condition).
            DB::transaction(function () use ($request) {
                $book = Book::lockForUpdate()->findOrFail($request->book_id);

                // Check stock availability
                if ($book->available_stock <= 0) {
                    throw new \RuntimeException('Gagal meminjam: Stok buku "' . $book->title . '" saat ini sedang kosong/habis dipinjam.');
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
            });

            return redirect()->back()->with('success', 'Transaksi peminjaman buku berhasil didaftarkan!');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses peminjaman: ' . $e->getMessage());
        }
    }

    public function returnBook($id)
    {
        try {
            $borrowing = BookBorrowing::with('book')->findOrFail($id);
            if ($borrowing->returned_at) {
                return redirect()->back()->with('error', 'Buku ini sudah dikembalikan sebelumnya.');
            }

            DB::transaction(function () use ($borrowing) {
                $borrowing->update([
                    'returned_at' => Carbon::now()->toDateString(),
                    'status' => 'returned',
                ]);

                // Kembalikan stok secara atomik (jika data buku masih ada).
                if ($borrowing->book) {
                    Book::where('id', $borrowing->book_id)->increment('available_stock');
                }
            });

            $bookTitle = $borrowing->book?->title ?? 'tersebut';
            return redirect()->back()->with('success', 'Buku "' . $bookTitle . '" berhasil dikembalikan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pengembalian buku: ' . $e->getMessage());
        }
    }
}
