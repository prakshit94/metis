import re
with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

content = content.replace("openCreateModal() {\n                this.isEditing = false;\n                this.isLookupLocked = !!orderNo;", "openCreateModal() {\n                this.isEditing = false;\n                this.isLookupLocked = false;")

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)
