import re
with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

pattern = re.compile(r'this\.modalInstance\.show\(\);', re.DOTALL)
new_code = """window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal')).show();"""
content, count = pattern.subn(new_code, content)

pattern2 = re.compile(r'this\.modalInstance\.hide\(\);', re.DOTALL)
new_code2 = """window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal')).hide();"""
content, count2 = pattern2.subn(new_code2, content)

print(f"Replaced {count} shows, {count2} hides")

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)
