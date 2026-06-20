<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $books = Book::latest()->get();

        if ($user->hasRole('Siswa')) {
            // Student portal view
            $student = $user->student;
            if (!$student) {
                return redirect()->route('student.dashboard')->with('error', 'Profil siswa tidak ditemukan.');
            }
            $myBorrowings = \App\Models\BookBorrowing::with(['book'])
                ->where('student_id', $student->id)
                ->latest()
                ->get();

            return view('frontend.library.index', compact('books', 'myBorrowings'));
        }

        // Admin/Teacher view
        $students = \App\Models\Student::with('user')->get();
        $borrowings = \App\Models\BookBorrowing::with(['student.user', 'book'])->latest()->get();

        return view('backend.master.books.index', compact('books', 'students', 'borrowings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            $data = $request->except('cover');
            $data['available_stock'] = $request->stock;

            if ($request->hasFile('cover')) {
                $file = $request->file('cover');
                $path = $file->store('books', 'public');
                $data['cover_image'] = $path;
            }

            Book::create($data);

            return redirect()->back()->with('success', 'Buku baru berhasil ditambahkan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan buku: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $book = Book::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'isbn' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'cover' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            $data = $request->except('cover');
            
            // Adjust available stock based on stock change
            $stockDiff = $request->stock - $book->stock;
            $data['available_stock'] = max(0, $book->available_stock + $stockDiff);

            if ($request->hasFile('cover')) {
                // Delete old cover if exists
                if ($book->cover_image) {
                    Storage::disk('public')->delete($book->cover_image);
                }
                $file = $request->file('cover');
                $path = $file->store('books', 'public');
                $data['cover_image'] = $path;
            }

            $book->update($data);

            return redirect()->back()->with('success', 'Buku berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui buku: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $book = Book::findOrFail($id);

            // Delete cover image
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }

            $book->delete();

            return redirect()->back()->with('success', 'Buku berhasil dihapus dari inventaris!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus buku: ' . $e->getMessage());
        }
    }
}
