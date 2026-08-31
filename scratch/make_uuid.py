import os
import glob
import re

# 1. Update Migrations
migrations_dir = 'c:/xampp/htdocs/myProject/lms-sync/database/migrations'
migration_files = glob.glob(os.path.join(migrations_dir, '2026_05_07_*.php'))

for filepath in migration_files:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Replace $table->id(); with $table->uuid('id')->primary();
    new_content = re.sub(r'\$table->id\(\);', r"$table->uuid('id')->primary();", content)
    
    # Replace $table->foreignId(...) with $table->foreignUuid(...)
    new_content = re.sub(r'\$table->foreignId\(', r"$table->foreignUuid(", new_content)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print(f"Updated migration: {os.path.basename(filepath)}")


# 2. Update Models
models_dir = 'c:/xampp/htdocs/myProject/lms-sync/app/Models'
lms_models = ['AcademicYear.php', 'Assignment.php', 'AssignmentSubmission.php', 'Attendance.php', 
              'ClassRoom.php', 'ClassStudent.php', 'Discussion.php', 'Module.php', 'School.php', 
              'Student.php', 'Subject.php', 'Teacher.php', 'TeachingAssignment.php']

for model_name in lms_models:
    filepath = os.path.join(models_dir, model_name)
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Add import if missing
        if 'use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;' not in content:
            content = content.replace('use Illuminate\\Database\\Eloquent\\Model;', 
                                      'use Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;')
            
        # Add trait if missing
        if 'use HasUuids;' not in content:
            # We already have protected $guarded = []; from previous step
            # Let's insert use HasUuids; right after it
            content = content.replace('protected $guarded = [];', 'protected $guarded = [];\n    use HasUuids;')
            
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated model: {model_name}")

print("UUID conversion applied to migrations and models.")
