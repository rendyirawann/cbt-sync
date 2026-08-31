import re
with open('c:/xampp/htdocs/myProject/lms-sync/resources/views/backend/user_management/user/index.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

new_content = re.sub(r'class="modal fade(?!\s+drawer-modal)', r'class="modal fade drawer-modal', content)
new_content = re.sub(r'class="modal fade drawer-modal([^"]*)"\s+id="Modal_Hapus_Data"', r'class="modal fade\1" id="Modal_Hapus_Data"', new_content)

if new_content != content:
    with open('c:/xampp/htdocs/myProject/lms-sync/resources/views/backend/user_management/user/index.blade.php', 'w', encoding='utf-8') as f:
        f.write(new_content)
    print('Updated user/index.blade.php')
