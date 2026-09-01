import os
import re

base_dir = 'c:/xampp/htdocs/myProject/lms-sync'

# Read the edit modal definitions from add_edit_views.py
with open(os.path.join(base_dir, 'scratch', 'add_edit_views.py'), 'r', encoding='utf-8') as f:
    script_content = f.read()

# Fix Students
sv_path = os.path.join(base_dir, 'resources', 'views', 'backend', 'master', 'students', 'index.blade.php')
with open(sv_path, 'r', encoding='utf-8') as f:
    sv_content = f.read()

# The modal starts with <!-- Edit Modal --> and ends with the third </div> after </form>
# A safer way to remove the wrongly placed modals:
# We know the modal string starts with "\n<!-- Edit Modal -->" and ends with "</div>\n</div>\n</div>\n"
modal_pattern = re.compile(r'\n<!-- Edit Modal -->.*?</div>\n    </div>\n</div>\n', re.DOTALL)
matches = modal_pattern.findall(sv_content)
if matches:
    edit_modal_content = matches[0]
    # Remove all occurrences
    sv_content = sv_content.replace(edit_modal_content, '')
    
    # Re-insert only after the row, before the FIRST @endforeach which belongs to the table
    # The table loop ends with:
    #                             </tr>
    #                             @endforeach
    # Let's target exactly that.
    target = "                            </tr>\n                            @endforeach"
    replacement = "                            </tr>\n" + edit_modal_content + "                            @endforeach"
    sv_content = sv_content.replace(target, replacement, 1) # Only replace the first occurrence!
    
    with open(sv_path, 'w', encoding='utf-8') as f:
        f.write(sv_content)

# Fix Teaching Assignments
tav_path = os.path.join(base_dir, 'resources', 'views', 'backend', 'master', 'teaching-assignments', 'index.blade.php')
with open(tav_path, 'r', encoding='utf-8') as f:
    tav_content = f.read()

matches = modal_pattern.findall(tav_content)
if matches:
    edit_modal_content = matches[0]
    # Remove all occurrences
    tav_content = tav_content.replace(edit_modal_content, '')
    
    # Re-insert only after the row, before the FIRST @endforeach
    target = "                            </tr>\n                            @endforeach"
    replacement = "                            </tr>\n" + edit_modal_content + "                            @endforeach"
    tav_content = tav_content.replace(target, replacement, 1)
    
    with open(tav_path, 'w', encoding='utf-8') as f:
        f.write(tav_content)

print("Fixed duplicate modals")
