import { createPopper } from '@popperjs/core';

// A body-level menu escapes table scrolling and transformed/overflow-hidden cards.
export function createUserActionMenu() {
  let popper = null;
  let menuObserver = null;
  let opening = false;
  let settleFrame = null;
  let onScroll = null;
  let onResize = null;
  return {
    open: false,
    init() {
      onScroll = event => {
        if (!this.open || this.$refs.menu?.contains(event.target)) return;
        // Keyboard focus can scroll the trigger into view during the opening frame.
        if (opening) { popper?.update(); return; }
        const trigger = this.$refs.trigger;
        const rect = trigger.getBoundingClientRect();
        const region = trigger.closest('.directory-table-region')?.getBoundingClientRect();
        if (rect.right <= Math.max(0, region?.left ?? 0) || rect.left >= Math.min(window.innerWidth, region?.right ?? window.innerWidth)
          || rect.bottom <= Math.max(0, region?.top ?? 0) || rect.top >= Math.min(window.innerHeight, region?.bottom ?? window.innerHeight)) {
          this.close();
        } else popper?.update();
      };
      onResize = () => this.close();
      document.addEventListener('scroll', onScroll, true);
      window.addEventListener('resize', onResize);
    },
    toggle() {
      if (this.open) this.close();
      else this.show();
    },
    show(focusLast = null) {
      this.open = true;
      opening = true;
      this.$nextTick(() => {
        if (!this.open || !this.$refs.trigger?.isConnected || !this.$refs.menu?.isConnected) return;
        let focusPending = focusLast;
        // Alpine reveals x-show on an animation frame. Measure only after it is visible.
        // Reposition again if menu content or its scrollable height changes.
        menuObserver = new ResizeObserver(() => {
          if (!this.open || !this.$refs.menu.getClientRects().length) return;
          if (!popper) popper = createPopper(this.$refs.trigger, this.$refs.menu, {
            placement: 'bottom-end',
            strategy: 'fixed',
            modifiers: [
              { name: 'offset', options: { offset: [0, 6] } },
              { name: 'flip', options: { boundary: 'viewport', padding: 8, fallbackPlacements: ['top-end', 'bottom-start', 'top-start'] } },
              { name: 'preventOverflow', options: { boundary: 'viewport', padding: 8, altAxis: true, tether: false } },
            ],
          });
          popper.update().then(() => {
            if (!this.open) return;
            if (focusPending !== null) {
              const items = this.items();
              (focusPending ? items.at(-1) : items[0])?.focus();
              focusPending = null;
            }
            settleFrame = requestAnimationFrame(() => { opening = false; settleFrame = null; });
          });
        });
        menuObserver.observe(this.$refs.menu);
      });
    },
    close(restoreFocus = false) {
      const wasOpen = this.open;
      menuObserver?.disconnect();
      menuObserver = null;
      opening = false;
      if (settleFrame !== null) cancelAnimationFrame(settleFrame);
      settleFrame = null;
      this.open = false;
      popper?.destroy();
      popper = null;
      if (wasOpen && restoreFocus) this.$refs.trigger?.focus();
    },
    outside(event) {
      if (!this.$refs.trigger?.contains(event.target)) this.close();
    },
    items() {
      return [...this.$refs.menu.querySelectorAll('.dropdown-item')]
        .filter(item => item.getClientRects().length && !item.classList.contains('disabled') && item.getAttribute('aria-disabled') !== 'true');
    },
    keydown(event) {
      if (event.key === 'Escape') {
        if (this.open) { event.preventDefault(); event.stopPropagation(); this.close(true); }
        return;
      }
      if (event.key === 'Tab') { this.close(true); return; }
      if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      event.stopPropagation();
      if (!this.open) { this.show(event.key === 'ArrowUp' || event.key === 'End'); return; }
      const items = this.items();
      const index = items.indexOf(document.activeElement);
      const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
        : event.key === 'ArrowDown' ? (index + 1) % items.length
          : (index <= 0 ? items.length : index) - 1;
      items[next]?.focus();
    },
    destroy() {
      this.close();
      document.removeEventListener('scroll', onScroll, true);
      window.removeEventListener('resize', onResize);
    },
  };
}
