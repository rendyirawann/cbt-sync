import os
import glob
import re

base_dir = 'c:/xampp/htdocs/myProject/lms-sync/resources/views/backend/master'
files = glob.glob(os.path.join(base_dir, '**/*.blade.php'), recursive=True)

for filepath in files:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Add drawer-modal to <div class="modal fade" ...>
    new_content = re.sub(r'class="modal fade"', r'class="modal fade drawer-modal"', content)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {os.path.basename(filepath)}")

# Also update the roles/index.blade.php edit modal and add modal
role_dir = 'c:/xampp/htdocs/myProject/lms-sync/resources/views/backend/user_management/role'
for filename in ['index.blade.php', 'edit.blade.php', 'show.blade.php']:
    filepath = os.path.join(role_dir, filename)
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # We only want to replace modal fade for non-delete modals
        # We can just replace all, and then revert Modal_Hapus_Data
        new_content = re.sub(r'class="modal fade([^"]*)"\s+id="Modal_Hapus_Data"', r'class="modal fade\1" id="Modal_Hapus_Data"', content)
        
        # Add drawer-modal to all modal fade
        new_content = re.sub(r'class="modal fade(?!\s+drawer-modal)', r'class="modal fade drawer-modal', new_content)
        
        # Remove drawer-modal from Modal_Hapus_Data if it got added
        new_content = re.sub(r'class="modal fade drawer-modal([^"]*)"\s+id="Modal_Hapus_Data"', r'class="modal fade\1" id="Modal_Hapus_Data"', new_content)

        if new_content != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f"Updated {filename}")
