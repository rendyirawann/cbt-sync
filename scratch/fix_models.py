import os
import glob

models_dir = 'c:/xampp/htdocs/myProject/lms-sync/app/Models'
for filepath in glob.glob(os.path.join(models_dir, '*.php')):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if 'protected $guarded = [];' not in content:
        if '    //' in content:
            new_content = content.replace('    //', '    protected $guarded = [];')
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {os.path.basename(filepath)}")
