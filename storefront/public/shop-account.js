const accountDropdown = document.querySelector('.account-dropdown');
if (accountDropdown) {
    document.addEventListener('click', event => {
        if (!accountDropdown.contains(event.target)) accountDropdown.open = false;
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && accountDropdown.open) {
            accountDropdown.open = false;
            accountDropdown.querySelector('summary').focus();
        }
    });
}

// Measure the complete sticky region so wrapped mobile headers never overlap content.
const stickyHeader = document.querySelector('[data-shop-sticky-header]');
if (stickyHeader) {
    const updateHeaderHeight = () => document.documentElement.style.setProperty('--shop-header-height', `${stickyHeader.getBoundingClientRect().height}px`);
    updateHeaderHeight();
    if ('ResizeObserver' in window) new ResizeObserver(updateHeaderHeight).observe(stickyHeader);
    else window.addEventListener('resize', updateHeaderHeight);
}
