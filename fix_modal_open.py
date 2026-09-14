import re
with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

pattern = re.compile(r'if \(!this\.modalInstance\) \{.*?this\.modalInstance\.show\(\);', re.DOTALL)
new_code = """this.modalInstance = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal'));
                this.modalInstance.show();"""

content, count = pattern.subn(new_code, content)
print(f"Replaced {count}")

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)
