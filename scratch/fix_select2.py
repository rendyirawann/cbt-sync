import re
import os

files_to_process = [
    'c:/xampp/htdocs/myProject/lms-sync/resources/views/backend/master/students/index.blade.php',
    'c:/xampp/htdocs/myProject/lms-sync/resources/views/backend/master/teachers/index.blade.php',
    'c:/xampp/htdocs/myProject/lms-sync/resources/views/backend/master/academic-years/index.blade.php'
]

for filepath in files_to_process:
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # Add Select2 to addModal
        # We find <div class="modal fade drawer-modal" id="addModal" ...> up to </div>
        # And inside we replace class="form-control form-control-solid" (for select) to form-select
        
        # Replace <select name="..." class="form-control form-control-solid" ...>
        # with <select name="..." class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" ...> inside addModal
        
        # First fix form-control to form-select for all selects
        content = re.sub(r'<select([^>]*)class="form-control form-control-solid"([^>]*)>', r'<select\1class="form-select form-select-solid"\2>', content)
        
        # We need a robust way. Since we only have addModal and editModal, let's use a regex to inject data-control="select2" if not present
        
        lines = content.split('\n')
        current_modal = None
        for i, line in enumerate(lines):
            # Track modal context
            modal_match = re.search(r'id="(addModal|editModal\{\{ \$item->id \}\})"', line)
            if modal_match:
                current_modal = modal_match.group(1)
                
            if '<select' in line and current_modal:
                if 'data-control="select2"' not in line:
                    line = line.replace('class="form-select form-select-solid"', f'class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#{current_modal}"')
                    lines[i] = line
                    
        new_content = '\n'.join(lines)
        
        if new_content != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            print(f'Updated {filepath}')
