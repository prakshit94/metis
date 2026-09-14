with open('resources/views/components/complaint-modal.blade.php', 'r') as f:
    content = f.read()

old_code = """window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal')).hide();
                    
                    
                } catch (e) {"""

new_code = """window.bootstrap.Modal.getOrCreateInstance(document.getElementById('complaintModal')).hide();
                    window.dispatchEvent(new CustomEvent('complaint-saved'));
                } catch (e) {"""

content = content.replace(old_code, new_code)

with open('resources/views/components/complaint-modal.blade.php', 'w') as f:
    f.write(content)

with open('resources/views/orders/create.blade.php', 'r') as f:
    content2 = f.read()

old_div = """<div x-data="createOrderApp(window.__INITIAL_ORDER_CUSTOMER__, window.__INITIAL_ORDER_TO_EDIT__)"
         @call-log-added.window="if(customerDetails) { clearCartCache(); isCallLoggedOrClosed = true; window.location.href = '{{ route('dashboard') }}'; }">"""

new_div = """<div x-data="createOrderApp(window.__INITIAL_ORDER_CUSTOMER__, window.__INITIAL_ORDER_TO_EDIT__)"
         @call-log-added.window="if(customerDetails) { clearCartCache(); isCallLoggedOrClosed = true; window.location.href = '{{ route('dashboard') }}'; }"
         @complaint-saved.window="loadAddresses()">"""

content2 = content2.replace(old_div, new_div)

with open('resources/views/orders/create.blade.php', 'w') as f:
    f.write(content2)

