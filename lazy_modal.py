with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

# Remove from init()
content = content.replace("this.modalInstance = new bootstrap.Modal(document.getElementById('complaintModal'));", "")

# Add to openSharedModal()
open_shared_modal_lazy = """openSharedModal(orderNo, customerId) {
                if (!this.modalInstance) {
                    this.modalInstance = new bootstrap.Modal(document.getElementById('complaintModal'));
                }
"""
content = content.replace("openSharedModal(orderNo, customerId) {", open_shared_modal_lazy)

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)
