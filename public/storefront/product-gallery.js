(() => {
    const links = Array.from(document.querySelectorAll('[data-product-gallery] [data-image-preview]'));
    const dialog = document.getElementById('product-image-preview');
    if (!links.length || !dialog || typeof dialog.showModal !== 'function') return;

    const image = dialog.querySelector('[data-preview-image]');
    const counter = dialog.querySelector('[data-preview-counter]');
    const previous = dialog.querySelector('[data-preview-previous]');
    const next = dialog.querySelector('[data-preview-next]');
    const error = dialog.querySelector('[data-preview-error]');
    let current = 0;
    let returnFocus = null;

    const show = index => {
        current = (index + links.length) % links.length;
        error.hidden = true;
        image.alt = links[current].querySelector('img')?.alt || `Product image ${current + 1}`;
        image.src = links[current].href;
        counter.textContent = `Image ${current + 1} of ${links.length}`;
        previous.hidden = next.hidden = links.length < 2;
    };

    links.forEach((link, index) => link.addEventListener('click', event => {
        // Preserve normal new-tab/window gestures and the image-link fallback.
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        returnFocus = link;
        show(index);
        if (!dialog.open) dialog.showModal();
        document.documentElement.classList.add('product-image-preview-open');
    }));

    dialog.querySelector('[data-preview-close]').addEventListener('click', () => dialog.close());
    previous.addEventListener('click', () => show(current - 1));
    next.addEventListener('click', () => show(current + 1));
    dialog.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            show(current + (event.key === 'ArrowRight' ? 1 : -1));
        }
    });
    dialog.addEventListener('click', event => {
        const rect = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => {
        document.documentElement.classList.remove('product-image-preview-open');
        image.removeAttribute('src');
        if (returnFocus?.isConnected) returnFocus.focus();
    });
    image.addEventListener('error', () => {
        error.hidden = false;
        if (!image.src.endsWith('/assets/images/product-placeholder.svg')) image.src = '/assets/images/product-placeholder.svg';
    });
})();
