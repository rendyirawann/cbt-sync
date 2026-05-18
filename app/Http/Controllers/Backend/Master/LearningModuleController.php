<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\LearningModule;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LearningModuleController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = LearningModule::with(['teachingAssignment.teacher.user', 'teachingAssignment.subject', 'teachingAssignment.classRoom']);

        if ($user->hasRole('Guru')) {
            // Guru hanya bisa melihat modul yang terikat dengan penugasannya
            if (!$user->teacher) {
                return redirect()->route('dashboard')->with('error', 'Profil guru tidak ditemukan.');
            }
            
            $teacherId = $user->teacher->id;
            $query->whereHas('teachingAssignment', function($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            });
            
            // Guru hanya bisa memilih dari penugasan miliknya saat tambah modul
            $assignments = TeachingAssignment::with(['subject', 'classRoom'])
                ->where('teacher_id', $teacherId)
                ->get();
        } elseif ($user->hasRole('Siswa')) {
            // Siswa hanya bisa melihat modul yang aktif di kelasnya
            if (!$user->student) {
                return redirect()->route('student.dashboard')->with('error', 'Profil siswa tidak ditemukan.');
            }
            
            $studentId = $user->student->id;
            $classId = \App\Models\ClassStudent::where('student_id', $studentId)
                ->whereHas('academicYear', function($q) { $q->where('is_active', 1); })
                ->value('class_room_id');

            $query->whereHas('teachingAssignment', function($q) use ($classId) {
                $q->where('class_room_id', $classId);
            })->where('is_published', true);
            
            $assignments = []; // Siswa tidak menambah modul
        } else {
            // Superadmin bisa melihat dan menambah modul untuk siapa saja
            $assignments = TeachingAssignment::with(['teacher.user', 'subject', 'classRoom'])->get();
        }

        $items = $query->latest()->get();
        
        return view('backend.master.learning-modules.index', compact('items', 'assignments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'teaching_assignment_id' => 'required',
            'title' => 'required|string|max:255',
            'zoom_link' => 'nullable|url',
            'file' => 'required|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480', // Max 20MB
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $size = $file->getSize();
            
            // Simpan file ke storage public/modules
            $path = $file->store('modules', 'public');

            $module = LearningModule::create([
                'teaching_assignment_id' => $request->teaching_assignment_id,
                'title' => $request->title,
                'description' => $request->description,
                'zoom_link' => $request->zoom_link,
                'file_path' => $path,
                'file_name' => $originalName,
                'file_type' => $extension,
                'file_size' => $size,
                'is_published' => $request->has('is_published') ? true : false,
            ]);

            \App\Services\NotificationService::sendAnnouncementNotification($module);

            return redirect()->back()->with('success', 'Modul berhasil diunggah!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengunggah modul: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $module = LearningModule::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'zoom_link' => 'nullable|url',
            'file' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
        ]);

        try {
            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'zoom_link' => $request->zoom_link,
                'is_published' => $request->has('is_published') ? true : false,
            ];
            
            if ($request->has('teaching_assignment_id')) {
                $data['teaching_assignment_id'] = $request->teaching_assignment_id;
            }

            if ($request->hasFile('file')) {
                // Hapus file lama
                Storage::disk('public')->delete($module->file_path);
                
                // Upload file baru
                $file = $request->file('file');
                $data['file_path'] = $file->store('modules', 'public');
                $data['file_name'] = $file->getClientOriginalName();
                $data['file_type'] = $file->getClientOriginalExtension();
                $data['file_size'] = $file->getSize();
            }

            $oldPublished = $module->is_published;
            $module->update($data);

            if ($module->is_published && !$oldPublished) {
                \App\Services\NotificationService::sendAnnouncementNotification($module);
            }

            return redirect()->back()->with('success', 'Modul berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui modul: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $module = LearningModule::findOrFail($id);
            
            // Hapus file fisik
            Storage::disk('public')->delete($module->file_path);
            
            // Hapus record database
            $module->delete();

            return redirect()->back()->with('success', 'Modul berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus modul: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        $module = LearningModule::findOrFail($id);
        
        if (!Storage::disk('public')->exists($module->file_path)) {
            return redirect()->back()->with('error', 'Mohon maaf, file fisik modul tidak ditemukan di server. Jika ini adalah data dummy, silakan edit modul dan unggah file PDF yang asli.');
        }

        return Storage::disk('public')->download($module->file_path, $module->file_name);
    }

    public function show($id)
    {
        $item = LearningModule::with([
            'teachingAssignment.teacher.user', 
            'teachingAssignment.subject', 
            'comments.user', 
            'comments.replies.user'
        ])->findOrFail($id);

        // Log access for student
        if (auth()->user()->hasRole('Siswa') && auth()->user()->student) {
            \App\Models\ModuleView::create([
                'student_id' => auth()->user()->student->id,
                'learning_module_id' => $id
            ]);
        }

        return view('backend.master.learning-modules.show', compact('item'));
    }
}
