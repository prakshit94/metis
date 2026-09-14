import re

with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

# Wrap the modal with x-data
content = '<div x-data="complaintModalShared()" @open-complaint-modal.window="openSharedModal($event.detail.order_no, $event.detail.customer_id)">\n' + content

# Find the end of the modal div (before <script>)
script_idx = content.find('<script>')
content = content[:script_idx] + '</div>\n' + content[script_idx:]

# Replace Alpine.data('complaintsTable' with Alpine.data('complaintModalShared'
content = content.replace("Alpine.data('complaintsTable'", "Alpine.data('complaintModalShared'")

# Remove fetchComplaints and fetchStats calls from init
content = content.replace("this.fetchComplaints();", "")
content = content.replace("this.fetchStats();", "")

# Add openSharedModal method
open_shared_modal = """
            openSharedModal(orderNo, customerId) {
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
                this.modalInstance = new bootstrap.Modal(document.getElementById('complaintModal'));
                this.modalInstance.show();
                if (orderNo) {
                    this.searchOrders();
                }
            },
"""
content = content.replace("init() {", init_replacement := "init() {\n                this.modalInstance = new bootstrap.Modal(document.getElementById('complaintModal'));\n            },\n" + open_shared_modal)

# write back
with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)

