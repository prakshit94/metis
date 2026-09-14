with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

content = content.replace("this.modalInstance = new bootstrap.Modal(document.getElementById('complaintModal'));\n                this.modalInstance.show();", "this.modalInstance.show();")

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)
