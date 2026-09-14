import re

with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

fetch_users_logic = """
            async init() {
                this.modalInstance = new bootstrap.Modal(document.getElementById('complaintModal'));
                try {
                    const data = await this.api('/api/complaints');
                    this.assignableUsers = data.assignable_users || [];
                } catch(e) {}
            },
"""
content = content.replace("init() {\n                this.modalInstance = new bootstrap.Modal(document.getElementById('complaintModal'));\n            },", fetch_users_logic)

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)
