import re

with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

# The floating code starts with "this.modalInstance = new bootstrap.Modal(document.getElementById('complaintModal'));"
# and ends right before "// ── Complaints API"
pattern = re.compile(r'this\.modalInstance = new bootstrap\.Modal\(document\.getElementById\(\'complaintModal\'\)\);\s*// Auto-open modal from URL params.*?window\.history\.replaceState\(\{\}, \'\', window\.location\.pathname\);\s*\}\);\s*\}\s*\},', re.DOTALL)

content, count = pattern.subn('', content)
print(f"Replaced {count} instances.")

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)
