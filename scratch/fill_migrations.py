import os
import glob
import re

migrations_dir = 'c:/xampp/htdocs/myProject/lms-sync/database/migrations'
files = glob.glob(os.path.join(migrations_dir, '*_create_*.php'))

schemas = {
    'create_schools_table.php': '''
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();''',
    'create_academic_years_table.php': '''
            $table->id();
            $table->string('name'); // e.g., 2024/2025
            $table->string('semester'); // e.g., Ganjil, Genap
            $table->boolean('is_active')->default(false);
            $table->timestamps();''',
    'create_teachers_table.php': '''
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nip')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->enum('gender', ['L', 'P'])->nullable();
            $table->text('address')->nullable();
            $table->timestamps();''',
    'create_students_table.php': '''
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->string('nisn')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->enum('gender', ['L', 'P'])->nullable();
            $table->text('address')->nullable();
            $table->timestamps();''',
    'create_subjects_table.php': '''
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();''',
    'create_class_rooms_table.php': '''
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g., X-IPA-1
            $table->string('level'); // e.g., 10, 11, 12
            $table->timestamps();''',
    'create_class_students_table.php': '''
            $table->id();
            $table->foreignId('class_room_id')->constrained('class_rooms')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->timestamps();''',
    'create_teaching_assignments_table.php': '''
            $table->id();
            $table->foreignId('class_room_id')->constrained('class_rooms')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->timestamps();''',
    'create_modules_table.php': '''
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained('teaching_assignments')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();''',
    'create_assignments_table.php': '''
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained('teaching_assignments')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();''',
    'create_assignment_submissions_table.php': '''
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->text('student_note')->nullable();
            $table->integer('score')->nullable();
            $table->text('feedback')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();''',
    'create_attendances_table.php': '''
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained('teaching_assignments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa']);
            $table->timestamps();''',
    'create_discussions_table.php': '''
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained('teaching_assignments')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('module_id')->nullable()->constrained('modules')->onDelete('cascade');
            $table->text('message');
            $table->timestamps();'''
}

for file_path in files:
    filename = os.path.basename(file_path)
    for schema_key, schema_content in schemas.items():
        if file_path.endswith(schema_key):
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            new_content = re.sub(
                r'(Schema::create\(\'.*?\', function \(Blueprint \$table\) \{)(.*?)(\s+\}\);)',
                r'\1\n' + schema_content + r'\3',
                content,
                flags=re.DOTALL
            )
            
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filename}")
