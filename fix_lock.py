with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

# Add isLookupLocked to state
content = content.replace("isEditing: false,", "isEditing: false,\n            isLookupLocked: false,")

# Add isLookupLocked to openSharedModal
content = content.replace("this.isEditing = false;", "this.isEditing = false;\n                this.isLookupLocked = !!orderNo;")

# Update input and button
old_input = """<input type="text" class="form-control border-0 bg-body px-3" x-model="searchQueryOrder" placeholder="Order ID or Mobile..." @keydown.enter.prevent="searchOrders">"""
new_input = """<input type="text" class="form-control border-0 bg-body px-3" x-model="searchQueryOrder" placeholder="Order ID or Mobile..." @keydown.enter.prevent="if(!isLookupLocked) searchOrders()" :disabled="isLookupLocked">"""
content = content.replace(old_input, new_input)

old_btn = """<button class="btn btn-primary px-3 fw-semibold" type="button" @click="searchOrders" :disabled="isSearchingOrders">"""
new_btn = """<button class="btn btn-primary px-3 fw-semibold" type="button" @click="searchOrders" :disabled="isSearchingOrders || isLookupLocked">"""
content = content.replace(old_btn, new_btn)

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)
