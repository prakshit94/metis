// Serialize cart writes so simultaneous product clicks cannot overwrite the session cart.
let cartUpdates = Promise.resolve();
const cartMoney = cents => '₹' + (cents / 100).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
let pendingCartUpdates = 0;
document.querySelectorAll('.quick-cart, .bag-cart').forEach(form => {
    form.addEventListener('submit', async event => {
        if (!event.submitter || !window.fetch) return;
        event.preventDefault();
        if (form.dataset.busy) return;
        const button = event.submitter;
        const payload = new FormData(form);
        payload.set('quantity', button.value);
        const error = form.querySelector('.cart-error');
        error.hidden = true;
        form.dataset.busy = 'true';
        const buttons = [...form.querySelectorAll('button')];
        const disabled = buttons.map(item => item.disabled);
        buttons.forEach(item => item.disabled = true);
        form.setAttribute('aria-busy', 'true');
        pendingCartUpdates++;
        const shipping = document.querySelector('.shipping-form');
        const checkoutButton = shipping?.querySelector('[data-checkout-submit]');
        if (checkoutButton) checkoutButton.disabled = true;
        try {
            const update = cartUpdates.then(async () => {
                const response = await fetch(form.action, {method: 'POST', body: payload, credentials: 'same-origin', headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
                const data = await response.json().catch(() => ({message: response.status === 419 ? 'Your session expired. Refresh the page and try again.' : 'Unable to update cart. Please try again.'}));
                return {response, data};
            });
            cartUpdates = update.catch(() => {});
            const {response, data} = await update;
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to update cart. Please try again.');
            const qty = Number(data.quantity);
            const min = Number(form.dataset.min);
            const max = Number(form.dataset.max);
            form.dataset.quantity = qty;
            const add = form.querySelector('.add-cart');
            if (add) {
                add.hidden = qty > 0;
                form.querySelector('.cart-stepper').hidden = qty === 0;
            }
            form.querySelector('output').textContent = qty;
            form.querySelector('[data-step="minus"]').value = qty <= min ? 0 : qty - 1;
            form.querySelector('[data-step="plus"]').value = Math.min(max, qty + 1);
            buttons.forEach((item, index) => disabled[index] = item.matches('[data-step="plus"]') && qty >= max);
            const line = form.closest('.cart-item');
            if (line) {
                line.querySelector('[data-line-total]').textContent = cartMoney(data.line_total);
                if (!qty) {
                    const next = line.nextElementSibling || line.previousElementSibling;
                    line.remove();
                    next?.querySelector('button')?.focus();
                }
            }
            document.querySelectorAll('[data-cart-total]').forEach(item => item.textContent = cartMoney(data.total));
            if (data.summary) {
                for (const field of ['subtotal', 'discount', 'rounding']) {
                    document.querySelectorAll('[data-cart-' + field + ']').forEach(item => {
                        const value = Number(data.summary[field] || 0) + (field === 'subtotal' ? Number(data.summary.tax || 0) : 0);
                        item.textContent = (field === 'discount' ? '−' : field === 'rounding' ? (value < 0 ? '−' : '+') : '') + cartMoney(Math.abs(value));
                    });
                }
            }
            const bagCount = document.querySelector('[data-bag-count]');
            if (bagCount) bagCount.textContent = data.product_count + (data.product_count === 1 ? ' product' : ' products');
            const empty = document.querySelector('[data-cart-empty]');
            if (empty) {
                empty.hidden = data.product_count > 0;
                document.querySelector('.checkout-grid')?.toggleAttribute('hidden', data.product_count === 0);
                if (!data.product_count) empty.querySelector('a').focus();
            }
            document.querySelectorAll('input[name="checkout_key"]').forEach(item => item.value = data.checkout_key);
            document.querySelectorAll('[data-cart-count]').forEach(item => item.textContent = data.count);
            const feedback = document.querySelector('.cart-feedback');
            feedback.textContent = qty ? 'Cart updated · ' + data.count + ' items in your bag' : 'Product removed from your cart';
            feedback.hidden = false;
            clearTimeout(window.shopCartNotice);
            window.shopCartNotice = setTimeout(() => feedback.hidden = true, 3500);
            if (add && (button.hidden || button.closest('[hidden]'))) form.querySelector(qty ? '[data-step="minus"]' : '.add-cart').focus();
        } catch (problem) {
            error.textContent = problem.message === 'Failed to fetch' ? 'Connection lost. Please try again.' : problem.message;
            error.hidden = false;
        } finally {
            buttons.forEach((item, index) => item.disabled = disabled[index]);
            pendingCartUpdates--;
            if (checkoutButton) checkoutButton.disabled = pendingCartUpdates > 0;
            delete form.dataset.busy;
            form.removeAttribute('aria-busy');
        }
    });
});

// Also guard submission via Enter while the checkout key and totals are changing.
document.querySelector('form.shipping-form')?.addEventListener('submit', event => {
    if (pendingCartUpdates) event.preventDefault();
});
