const shippingPanel = document.querySelector('.checkout-address, [data-profile-addresses]');
if (shippingPanel) {
    const checkout = shippingPanel.closest('form');
    const savedList = shippingPanel.querySelector('[data-saved-addresses]');
    const notice = shippingPanel.querySelector('[data-address-notice]');
    const billingSame = shippingPanel.querySelector('[data-billing-same]');
    const billingPanel = shippingPanel.querySelector('[data-billing-addresses]');
    const syncBilling = () => {
        if (!billingPanel || !billingSame) return;
        billingPanel.hidden = billingSame.checked;
        billingPanel.querySelectorAll('[data-billing-choice]').forEach(choice => {
            choice.disabled = billingSame.checked;
            choice.required = !billingSame.checked;
        });
    };
    billingSame?.addEventListener('change', syncBilling);
    syncBilling();
    const fields = ['label', 'address_line_1', 'address_line_2', 'village_name', 'post_office', 'taluka', 'district', 'city', 'state', 'pincode'];
    const setDelivery = values => fields.forEach(name => {
        const input = checkout.elements.namedItem(name);
        const value = values[name] || '';
        input.value = value;
    });
    const selectAddress = radio => {
        const values = JSON.parse(radio.dataset.savedAddress);
        setDelivery(values);
        notice.textContent = values.label + ' selected for delivery.';
    };
    savedList?.addEventListener('change', event => {
        if (event.target.matches('input[name="delivery_address_choice"]')) selectAddress(event.target);
    });
    checkout?.addEventListener('submit', event => {
        if (!billingSame.checked && !billingPanel?.querySelector('[data-billing-choice]:checked')) {
            event.preventDefault();
            notice.textContent = 'Select a saved billing address or use the shipping address.';
            return;
        }
        if (!savedList.querySelector('input[name="delivery_address_choice"]:checked')) {
            event.preventDefault();
            notice.textContent = 'Add and select a saved delivery address before placing your order.';
            shippingPanel.querySelector('[data-address-open]').click();
        }
    });

    const dialog = document.getElementById('shop-address-dialog');
    const addressForm = dialog?.querySelector('[data-address-form]');
    const openButton = shippingPanel.querySelector('[data-address-open]');
    let savingAddress = false;
    const createUrl = addressForm.action;
    let returnFocus = openButton;
    const openEditor = (button, values = null) => {
        returnFocus = button;
        addressForm.action = values ? button.dataset.addressUrl : createUrl;
        addressForm.reset();
        addressForm.querySelector('[data-address-error]').hidden = true;
        addressForm.querySelector('[data-village-lookup]').dispatchEvent(new Event('village-reset'));
        if (values) Object.entries(values).forEach(([name, value]) => {
            const input = addressForm.elements.namedItem(name);
            if (input) { if (input.type === 'checkbox') input.checked = Boolean(value); else {
                if (input.tagName === 'SELECT' && value && !Array.from(input.options).some(option => option.value === value)) input.add(new Option(value, value));
                input.value = value ?? '';
            } }
        });
        document.getElementById('shop-address-title').textContent = values ? 'Edit address details' : 'Add a delivery address';
        dialog.showModal();
        addressForm.elements.namedItem('label').focus();
    };
    openButton.addEventListener('click', () => openEditor(openButton));
    shippingPanel.addEventListener('click', event => {
        const button = event.target.closest('[data-address-edit]');
        if (button) openEditor(button, JSON.parse(button.dataset.addressEdit));
    });
    dialog.addEventListener('cancel', event => { if (savingAddress) event.preventDefault(); });
    dialog.querySelectorAll('[data-address-close]').forEach(button => button.addEventListener('click', () => { if (!savingAddress) dialog.close(); }));
    dialog.addEventListener('close', () => { addressForm.querySelector('[data-village-lookup]').dispatchEvent(new Event('village-reset')); (returnFocus?.isConnected ? returnFocus : openButton).focus(); });
    addressForm.addEventListener('submit', async event => {
        if (!window.fetch) return;
        event.preventDefault();
        if (savingAddress) return;
        const error = addressForm.querySelector('[data-address-error]');
        const save = addressForm.querySelector('[data-address-save]');
        const fieldset = addressForm.querySelector('fieldset');
        const payload = new FormData(addressForm);
        error.hidden = true;
        savingAddress = true;
        fieldset.disabled = true;
        dialog.querySelectorAll('button').forEach(button => button.disabled = true);
        addressForm.setAttribute('aria-busy', 'true');
        save.textContent = 'Saving address…';
        try {
            const response = await fetch(addressForm.action, {method: 'POST', body: payload, credentials: 'same-origin', headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || (response.status === 419 ? 'Your session expired. Refresh the page and try again.' : data.message || 'Unable to save the address. Please try again.'));
            if (!data.address?.id || typeof data.html !== 'string') throw new Error('Unable to confirm the saved address. Refresh the page to check your addresses before trying again.');
            const previousShipping = savedList?.querySelector('input:checked')?.value;
            const editing = addressForm.action !== createUrl;
            if (savedList) {
                savedList.innerHTML = data.html;
                if (editing && previousShipping) savedList.querySelectorAll('input[name=delivery_address_choice]').forEach(choice => choice.checked = choice.value === previousShipping);
            }
            const book = shippingPanel.querySelector('[data-address-book]');
            if (book && typeof data.book_html === 'string') book.innerHTML = data.book_html;
            const previousBilling = billingPanel?.querySelector('[data-billing-choice]:checked')?.value;
            if (billingPanel && typeof data.billing_html === 'string') {
                billingPanel.innerHTML = data.billing_html;
                billingPanel.querySelectorAll('[data-billing-choice]').forEach(choice => choice.checked = choice.value === previousBilling);
                syncBilling();
            }
            const selected = savedList?.querySelector('input:checked');
            if (selected) selectAddress(selected);
            if (notice) notice.textContent = 'Address saved. Review your delivery selection before placing the order.';
            savingAddress = false;
            dialog.close();
        } catch (problem) {
            error.textContent = problem.message === 'Failed to fetch' ? 'Connection lost. Check your saved addresses before trying again.' : problem.message;
            error.hidden = false;
            error.focus();
        } finally {
            savingAddress = false;
            fieldset.disabled = false;
            dialog.querySelectorAll('button').forEach(button => button.disabled = false);
            save.textContent = 'Save and use address →';
            addressForm.removeAttribute('aria-busy');
        }
    });
}

// The checkout editor and add-address modal share the same directory lookup.
document.querySelectorAll('[data-village-lookup]').forEach(scope => {
    const form = scope.closest('form');
    const search = scope.querySelector('[data-village-search]');
    const results = scope.querySelector('[data-village-results]');
    const status = scope.querySelector('[data-village-status]');
    let timer;
    let controller;
    let version = 0;
    const closeResults = () => {
        clearTimeout(timer); controller?.abort(); version++;
        results.replaceChildren(); results.hidden = true;
    };
    scope.addEventListener('village-reset', () => {
        closeResults(); search.value = '';
        status.textContent = 'Choose a village to fill the address details, or enter them below.';
    });
    search.addEventListener('input', () => {
        closeResults();
        const query = search.value.trim();
        if (query.length < 3) { status.textContent = 'Enter at least 3 characters to search, or enter the address manually.'; return; }
        const current = version;
        status.textContent = 'Searching…';
        timer = setTimeout(async () => {
            controller = new AbortController();
            try {
                const url = new URL(scope.dataset.villageUrl, window.location.href);
                url.searchParams.set('search', query);
                const response = await fetch(url, {credentials: 'same-origin', headers: {Accept: 'application/json'}, signal: controller.signal});
                if (!response.ok) throw new Error('Unable to search the directory. Enter the address manually or try again.');
                const data = await response.json();
                if (current !== version) return;
                results.replaceChildren();
                data.villages.forEach(village => {
                    const button = document.createElement('button'); button.type = 'button';
                    button.textContent = [village.village_name, village.post_so_name, village.taluka_name, village.district_name, village.state_name, village.pincode].filter(Boolean).join(' · ');
                    button.addEventListener('click', () => {
                        const values = {village_id: village.id, village_name: village.village_name, post_office: village.post_so_name, taluka: village.taluka_name, district: village.district_name, state: village.state_name, pincode: village.pincode};
                        Object.entries(values).forEach(([name, value]) => { const input = form.elements.namedItem(name); if (input) input.value = value || ''; });
                        search.value = village.village_name; closeResults();
                        status.textContent = 'Village details filled. Check the address before saving or placing your order.';
                        scope.dispatchEvent(new CustomEvent('shop-village-selected', {bubbles: true}));
                        form.elements.namedItem('address_line_1').focus();
                    });
                    results.append(button);
                });
                results.hidden = !data.villages.length;
                status.textContent = data.villages.length ? 'Choose a village from the results.' : 'No matches. Try another search or enter the address manually.';
            } catch (error) { if (current === version && error.name !== 'AbortError') status.textContent = error.message; }
        }, 300);
    });
    ['village_name', 'post_office', 'taluka', 'district', 'state', 'pincode'].forEach(name => form.elements.namedItem(name)?.addEventListener('input', () => { const id = form.elements.namedItem('village_id'); if (id) id.value = ''; }));
    scope.querySelector('[data-village-clear]').addEventListener('click', () => {
        closeResults(); search.value = ''; status.textContent = 'Search cleared. Address details are unchanged.'; search.focus();
    });
    scope.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !results.hidden) { event.preventDefault(); event.stopPropagation(); closeResults(); }
    });
    document.addEventListener('click', event => { if (!scope.contains(event.target)) closeResults(); });
});
