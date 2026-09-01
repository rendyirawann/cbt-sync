import os

models_dir = 'c:/xampp/htdocs/myProject/lms-sync/app/Models'

# ClassRoom.php
with open(os.path.join(models_dir, 'ClassRoom.php'), 'r') as f:
    content = f.read()
if 'public function school()' not in content:
    content = content.replace('}', '    public function school()\n    {\n        return $this->belongsTo(School::class);\n    }\n}')
    with open(os.path.join(models_dir, 'ClassRoom.php'), 'w') as f:
        f.write(content)

# Student.php
with open(os.path.join(models_dir, 'Student.php'), 'r') as f:
    content = f.read()
if 'public function school()' not in content:
    content = content.replace('}', '    public function school()\n    {\n        return $this->belongsTo(School::class);\n    }\n\n    public function user()\n    {\n        return $this->belongsTo(User::class);\n    }\n}')
    with open(os.path.join(models_dir, 'Student.php'), 'w') as f:
        f.write(content)
        
# Teacher.php
with open(os.path.join(models_dir, 'Teacher.php'), 'r') as f:
    content = f.read()
if 'public function user()' not in content:
    content = content.replace('}', '    public function user()\n    {\n        return $this->belongsTo(User::class);\n    }\n}')
    with open(os.path.join(models_dir, 'Teacher.php'), 'w') as f:
        f.write(content)

print("Added relationships to models")
