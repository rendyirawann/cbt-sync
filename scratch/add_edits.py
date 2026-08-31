import os
import re

base_dir = 'c:/xampp/htdocs/myProject/lms-sync'

# --- 1. TEACHER CONTROLLER ---
tc_path = os.path.join(base_dir, 'app', 'Http', 'Controllers', 'Backend', 'Master', 'TeacherController.php')
with open(tc_path, 'r', encoding='utf-8') as f:
    tc_content = f.read()

if 'public function update' not in tc_content:
    tc_update = """
    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $teacher->user_id,
            'nip' => 'required|unique:teachers,nip,' . $teacher->id,
        ]);

        try {
            \DB::beginTransaction();
            
            $user = $teacher->user;
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = \Hash::make($request->password);
            }
            $user->save();

            $teacher->update([
                'nip' => $request->nip,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'address' => $request->address,
            ]);
            
            \DB::commit();
            return redirect()->back()->with('success', 'Data Guru berhasil diperbarui');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
"""
    # Insert before destroy
    tc_content = tc_content.replace('public function destroy', tc_update + '\n    public function destroy')
    with open(tc_path, 'w', encoding='utf-8') as f:
        f.write(tc_content)

# --- 2. STUDENT CONTROLLER ---
sc_path = os.path.join(base_dir, 'app', 'Http', 'Controllers', 'Backend', 'Master', 'StudentController.php')
with open(sc_path, 'r', encoding='utf-8') as f:
    sc_content = f.read()

if 'public function update' not in sc_content:
    sc_update = """
    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $student->user_id,
            'nisn' => 'required|unique:students,nisn,' . $student->id,
            'school_id' => 'required'
        ]);

        try {
            \DB::beginTransaction();
            
            $user = $student->user;
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = \Hash::make($request->password);
            }
            $user->save();

            $student->update([
                'school_id' => $request->school_id,
                'nisn' => $request->nisn,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'address' => $request->address,
            ]);
            
            \DB::commit();
            return redirect()->back()->with('success', 'Data Siswa berhasil diperbarui');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
"""
    sc_content = sc_content.replace('public function destroy', sc_update + '\n    public function destroy')
    with open(sc_path, 'w', encoding='utf-8') as f:
        f.write(sc_content)

# --- 3. TEACHING ASSIGNMENT CONTROLLER ---
tac_path = os.path.join(base_dir, 'app', 'Http', 'Controllers', 'Backend', 'Master', 'TeachingAssignmentController.php')
with open(tac_path, 'r', encoding='utf-8') as f:
    tac_content = f.read()

if 'public function update' not in tac_content:
    tac_update = """
    public function update(Request $request, $id)
    {
        $assignment = TeachingAssignment::findOrFail($id);
        
        $request->validate([
            'class_room_id' => 'required',
            'subject_id' => 'required',
            'teacher_id' => 'required',
            'academic_year_id' => 'required',
        ]);

        // Cek duplikasi
        $exists = TeachingAssignment::where([
            'class_room_id' => $request->class_room_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'academic_year_id' => $request->academic_year_id,
        ])->where('id', '!=', $id)->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Penugasan ini sudah ada sebelumnya!');
        }

        $assignment->update($request->all());
        return redirect()->back()->with('success', 'Penugasan guru berhasil diperbarui');
    }
"""
    tac_content = tac_content.replace('public function destroy', tac_update + '\n    public function destroy')
    with open(tac_path, 'w', encoding='utf-8') as f:
        f.write(tac_content)

print("Controllers updated with Edit logic")
