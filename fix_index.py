import re

with open("resources/views/orders/index.blade.php", "r") as f:
    content = f.read()

target = """            confirmOrder(order) {
                if (!order) return;
                this.confirmModalOrder = order;
                this.confirmAction = 'now';
                this.scheduleReason = '';
                this.scheduledConfirmDate = '';
                this.confirmNotes = '';
                bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmOrderModal')).show();
            },"""

replacement = """            confirmOrder(order) {
                if (!order) return;
                const query = new URLSearchParams();
                query.set('order_id', order.id);
                if (order.party_id) query.set('customer_id', order.party_id);
                query.set('step', 'confirm');
                window.location.href = `/orders/create?${query.toString()}`;
            },"""

new_content = content.replace(target, replacement)

with open("resources/views/orders/index.blade.php", "w") as f:
    f.write(new_content)
