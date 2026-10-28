// ==========================================================================
// Theme Manager - Handle dark/light mode switching
// ==========================================================================

export class ThemeManager {
  constructor() {
    this.explicitTheme = this.getStoredTheme();
    this.currentTheme = this.explicitTheme || this.getPreferredTheme();
    this.themeToggleButtons = [];
    this.init();
  }

  init() {
    // Set initial theme
    this.setTheme(this.currentTheme, false);

    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (!this.explicitTheme) {
        this.setTheme(e.matches ? 'dark' : 'light', false);
      }
    });
  }

  getStoredTheme() {
    try {
      const theme = localStorage.getItem('theme');
      return ['light', 'dark'].includes(theme) ? theme : null;
    } catch (_) {
      return null;
    }
  }

  getPreferredTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  setTheme(theme, persist = true) {
    theme = ['light', 'dark'].includes(theme) ? theme : this.getPreferredTheme();
    document.documentElement.setAttribute('data-bs-theme', theme);
    if (persist) {
      this.explicitTheme = theme;
      try { localStorage.setItem('theme', theme); } catch (_) {}
    }
    this.currentTheme = theme;
    this.updateThemeIcons();

    // Dispatch event so active components can update existing charts
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
  }

  toggleTheme() {
    const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
    this.setTheme(newTheme);
  }

  updateThemeIcons() {
    const lightIcons = document.querySelectorAll('.theme-icon-light');
    const darkIcons = document.querySelectorAll('.theme-icon-dark');

    if (this.currentTheme === 'dark') {
      lightIcons.forEach((icon) => icon.classList.add('d-none'));
      darkIcons.forEach((icon) => icon.classList.remove('d-none'));
    } else {
      lightIcons.forEach((icon) => icon.classList.remove('d-none'));
      darkIcons.forEach((icon) => icon.classList.add('d-none'));
    }
  }

  getCurrentTheme() {
    return this.currentTheme;
  }
}
