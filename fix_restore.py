import re
with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

pattern = re.compile(r'openSharedModal\(orderNo, customerId\) \{.*?if \(orderNo\)', re.DOTALL)
new_code = """openSharedModal(orderNo, customerId) {
                this.isEditing = false;
                this.form = {
                    id: null,
                    order_no: orderNo,
                    customer_id: customerId || '',
                    category: 'other',
                    priority: 'medium',
                    subject: '',
                    description: '',
                    status: 'open',
                    resolution_notes: '',
                    product_ids: [],
                    is_entire_order: true
                };
                this.searchQueryOrder = orderNo;
                this.modalInstance = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal'));
                this.modalInstance.show();
                if (orderNo)"""

content, count = pattern.subn(new_code, content)
print(f"Restored {count}")

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)
