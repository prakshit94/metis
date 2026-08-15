// ==========================================================================
// Bootstrap Admin Template - Modern JavaScript Entry Point
// ES6+ Modules with Bootstrap 5
// ==========================================================================

// Import Bootstrap 5 JavaScript components (only those actively used)
import {
  Collapse,
  Dropdown,
  Modal,
  Tab,
  Toast,
  Tooltip,
} from 'bootstrap';

window.bootstrap = {
  Collapse,
  Dropdown,
  Modal,
  Tab,
  Toast,
  Tooltip,
};

// Import our custom modules
import { ThemeManager } from './utils/theme-manager.js';
import { DashboardManager } from './components/dashboard.js';
import { NotificationManager } from './utils/notifications.js';
import { SidebarManager } from './components/sidebar.js';
import { iconManager } from './utils/icon-manager.js';
import { createSearchComponent } from './utils/search-component.js';

// Import Alpine.js for reactive components
import Alpine from 'alpinejs';
window.Alpine = Alpine;

// Import SweetAlert2
import Swal from 'sweetalert2';
window.Swal = Swal;

// Import styles (Bootstrap Icons are included in SCSS)
import '../scss/main.scss';

// Application Class
class AdminApp {
  constructor() {
    this.components = new Map();
    this.isInitialized = false;
  }

  // Initialize the application
  async init() {
    if (this.isInitialized) return;

    try {
      // Wait for DOM to be ready
      if (document.readyState === 'loading') {
        await new Promise(resolve => {
          document.addEventListener('DOMContentLoaded', resolve);
        });
      }

      // Dismiss loading screen as soon as DOM is ready
      this._hideLoadingScreen();

      // Initialize core managers
      this.themeManager = new ThemeManager();
      this.notificationManager = new NotificationManager();
      this.sidebarManager = new SidebarManager();
      this.iconManager = iconManager;

      // Initialize Bootstrap components
      this.initBootstrapComponents();

      // Initialize page-specific components and wait for them to complete
      await this.initPageComponents();

      // Setup global event listeners
      this.setupEventListeners();

      // Localize shortcut hints in search placeholders (⌘K on Mac, Ctrl+K elsewhere)
      this.localizeShortcutHints();

      // Initialize navigation
      this.initNavigation();

      // Initialize tooltips and popovers globally
      this.initTooltipsAndPopovers();

      // Initialize Alpine.js
      this.initAlpine();

      this.isInitialized = true;
      console.log('🚀 Admin App initialized successfully');

    } catch (error) {
      console.error('❌ Failed to initialize Admin App:', error);
    }
  }

  // Dismiss the loading screen overlay
  _hideLoadingScreen() {
    const screen = document.getElementById('loading-screen');
    if (!screen) return;
    screen.classList.add('hidden');
    screen.addEventListener('transitionend', () => screen.remove(), { once: true });
  }

  // Initialize Bootstrap components
  initBootstrapComponents() {
    // Initialize dropdowns
    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(element => {
      new Dropdown(element);
    });

    // Initialize modals
    document.querySelectorAll('.modal').forEach(element => {
      new Modal(element);
    });

    // Initialize collapse elements (toggle:false — don't auto-open on construction)
    document.querySelectorAll('.collapse').forEach(element => {
      new Collapse(element, { toggle: false });
    });

    // Initialize tabs
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(element => {
      new Tab(element);
    });

    // Initialize toasts
    document.querySelectorAll('.toast').forEach(element => {
      new Toast(element);
    });
  }

  // Initialize tooltips
  initTooltipsAndPopovers() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
      new Tooltip(element);
    });
  }

  // Initialize page-specific components
  async initPageComponents() {
    const currentPage = document.body.dataset.page;

    switch (currentPage) {
      case 'dashboard':
        this.components.set('dashboard', new DashboardManager());
        break;
      case 'users':
        await this.initUsersPage();
        break;
      case 'attendances':
        await this.initAttendancesPage();
        break;
      case 'customers':
        await this.initCustomersPage();
        break;
      case 'villages':
        await this.initVillagesPage();
        break;
      case 'roles-permissions':
        await this.initRolesPermissionsPage();
        break;
      case 'analytics':
        await this.initAnalyticsPage();
        break;
      case 'order.reasons':
        await this.initOrderReasonsPage();
        break;
      case 'forms':
        await this.initFormsPage();
        break;
      case 'catalog-brands': await this.initCatalogBrands(); break;
      case 'catalog-categories': await this.initCatalogCategories(); break;
      case 'catalog-uom': await this.initCatalogUom(); break;
      case 'catalog-tax-rates': await this.initCatalogTaxRates(); break;
      case 'catalog-hsn-codes': await this.initCatalogHsnCodes(); break;
      case 'catalog-warehouses': await this.initCatalogWarehouses(); break;
      case 'catalog-attributes': await this.initCatalogAttributes(); break;
      case 'inventory-stock-management': await this.initInventoryStockManagement(); break;
      case 'inventory-stock-transfers': await this.initInventoryStockTransfers(); break;
      case 'inventory-adjustments': await this.initInventoryAdjustments(); break;
      case 'catalog-products':
        await this.initProductsPage();
        break;
      case 'orders':
        await this.initOrdersPage();
        break;
      case 'returns':
        await this.initReturnsPage();
        break;
      case 'invoices':
        await this.initInvoicesPage();
        break;
      case 'payments':
        await this.initPaymentsPage();
        break;
      case 'refunds':
        await this.initRefundsPage();
        break;
      case 'reports':
        await this.initReportsPage();
        break;
      case 'messages':
        await this.initMessagesPage();
        break;
      case 'calendar':
        await this.initCalendarPage();
        break;
      case 'settings':
        await this.initSettingsPage();
        break;
      case 'security':
        await this.initSecurityPage();
        break;
      case 'files':
        await this.initFilesPage();
        break;
      case 'shipping-shipments':
        await this.initShippingShipmentsPage();
        break;
      case 'shipping-services':
        await this.initShippingServicesPage();
        break;
      case 'help':
        await this.initHelpPage();
        break;
      case 'elements':
        await this.initElementsPage();
        break;
      default:
        // No page-specific component needed
        break;
    }
  }

  // Initialize forms page
  async initFormsPage() {
    try {
      await import('./components/forms.js');
      console.log('📝 Forms page script loaded successfully');
    } catch (error) {
      console.warn('Forms components not available:', error);
    }
  }

  async initUsersPage() {
    try {
      await import('./components/users.js');
      console.log('👥 Users page script loaded successfully');
    } catch (error) {
      console.error('Failed to load users page script:', error);
    }
  }

  async initAttendancesPage() {
    try {
      await import('./components/attendances.js');
      console.log('🕒 Attendances page script loaded successfully');
    } catch (error) {
      console.error('Failed to load attendances page script:', error);
    }
  }

  async initCustomersPage() {
    try {
      await import('./components/customers.js');
      console.log('👥 Customers page script loaded successfully');
    } catch (error) {
      console.error('Failed to load customers page script:', error);
    }
  }

  async initVillagesPage() {
    try {
      await import('./components/villages.js');
      console.log('🏡 Villages page script loaded successfully');
    } catch (error) {
      console.error('Failed to load villages page script:', error);
    }
  }

  async initRolesPermissionsPage() {
    try {
      await import('./components/roles-permissions.js');
      console.log('🛡️ Roles & Permissions page script loaded successfully');
    } catch (error) {
      console.error('Failed to load roles & permissions page script:', error);
    }
  }

  async initAnalyticsPage() {
    try {
      await import('./components/analytics.js');
      console.log('📊 Analytics page script loaded successfully');
    } catch (error) {
      console.error('Failed to load analytics page script:', error);
    }
  }

  async initOrderReasonsPage() {
    try {
      await import('./components/order-reasons.js');
      console.log('📋 Order Reasons page script loaded successfully');
    } catch (error) {
      console.error('Failed to load order reasons page script:', error);
    }
  }


  async initCatalogBrands() { try { const m = await import('./components/catalog/brands.js'); window.Alpine.data('brandsTable', m.default); console.log('Loaded brands'); } catch(e) { console.error(e); } }
  async initCatalogCategories() { try { const m = await import('./components/catalog/categories.js'); window.Alpine.data('categoriesTable', m.default); console.log('Loaded categories'); } catch(e) { console.error(e); } }
  async initCatalogUom() { try { const m = await import('./components/catalog/uom.js'); window.Alpine.data('uomTable', m.default); console.log('Loaded uom'); } catch(e) { console.error(e); } }
  async initCatalogTaxRates() { try { const m = await import('./components/catalog/tax-rates.js'); window.Alpine.data('taxRatesTable', m.default); console.log('Loaded tax rates'); } catch(e) { console.error(e); } }
  async initCatalogHsnCodes() { try { const m = await import('./components/catalog/hsn-codes.js'); window.Alpine.data('hsnCodesTable', m.default); console.log('Loaded hsn codes'); } catch(e) { console.error(e); } }
  async initCatalogWarehouses() { try { const m = await import('./components/catalog/warehouses.js'); window.Alpine.data('warehousesTable', m.default); console.log('Loaded warehouses'); } catch(e) { console.error(e); } }
  async initCatalogAttributes() { try { const m = await import('./components/catalog/attributes.js'); window.Alpine.data('attributesTable', m.default); console.log('Loaded attributes'); } catch(e) { console.error(e); } }
  async initInventoryStockManagement() { try { const m = await import('./components/inventory/stock-management.js'); window.Alpine.data('stockManagement', m.default); console.log('Loaded stock management'); } catch(e) { console.error(e); } }
  async initInventoryStockTransfers() { try { const m = await import('./components/inventory/stock-transfers.js'); window.Alpine.data('stockTransfers', m.default); console.log('Loaded stock transfers'); } catch(e) { console.error(e); } }
  async initInventoryAdjustments() { try { const m = await import('./components/inventory/adjustments.js'); window.Alpine.data('inventoryAdjustments', m.default); console.log('Loaded inventory adjustments'); } catch(e) { console.error(e); } }

  async initProductsPage() {
    try {
      await import('./components/products.js');
      console.log('📦 Products page script loaded successfully');
    } catch (error) {
      console.error('Failed to load products page script:', error);
    }
  }

  async initOrdersPage() {
    try {
      await import('./components/orders.js');
      console.log('🛒 Orders page script loaded successfully');
    } catch (error) {
      console.error('Failed to load orders page script:', error);
    }
  }

  async initReturnsPage() {
    try {
      await import('./components/returns.js');
      console.log('↩️ Returns page script loaded successfully');
    } catch (error) {
      console.error('Failed to load returns page script:', error);
    }
  }

  async initInvoicesPage() {
    try {
      await import('./components/invoices.js');
      console.log('🧾 Invoices page script loaded successfully');
    } catch (error) {
      console.error('Failed to load invoices page script:', error);
    }
  }

  async initPaymentsPage() {
    try {
      await import('./components/payments.js');
      console.log('💳 Payments page script loaded successfully');
    } catch (error) {
      console.error('Failed to load payments page script:', error);
    }
  }

  async initRefundsPage() {
    try {
      await import('./components/refunds.js');
      console.log('💵 Refunds page script loaded successfully');
    } catch (error) {
      console.error('Failed to load refunds page script:', error);
    }
  }

  async initReportsPage() {
    try {
      await import('./components/reports.js');
      console.log('📊 Reports page script loaded successfully');
    } catch (error) {
      console.error('Failed to load reports page script:', error);
    }
  }

  async initMessagesPage() {
    try {
      await import('./components/messages.js');
      console.log('💬 Messages page script loaded successfully');
    } catch (error) {
      console.error('Failed to load messages page script:', error);
    }
  }

  async initCalendarPage() {
    try {
      await import('./components/calendar.js');
      console.log('📅 Calendar page script loaded successfully');
    } catch (error) {
      console.error('Failed to load calendar page script:', error);
    }
  }

  async initSettingsPage() {
    try {
      await import('./components/settings.js');
      console.log('⚙️ Settings page script loaded successfully');
    } catch (error) {
      console.error('Failed to load settings page script:', error);
    }
  }

  async initSecurityPage() {
    try {
      await import('./components/security.js');
      console.log('🔒 Security page script loaded successfully');
    } catch (error) {
      console.error('Failed to load security page script:', error);
    }
  }

  async initFilesPage() {
    try {
      await import('./components/files.js');
      console.log('📁 Files page script loaded successfully');
    } catch (error) {
      console.error('Failed to load files page script:', error);
    }
  }

  async initHelpPage() {
    try {
      await import('./components/help.js');
      console.log('❓ Help page script loaded successfully');
    } catch (error) {
      console.error('Failed to load help page script:', error);
    }
  }

  async initElementsPage() {
    try {
      await import('./components/elements.js');
      console.log('🧩 Elements page script loaded successfully');
    } catch (error) {
      console.error('Failed to load elements page script:', error);
    }
  }

  async initShippingShipmentsPage() {
    try {
      const m = await import('./components/shipping/shipments.js');
      window.Alpine.data('shipmentsTable', m.default);
      console.log('🚚 Shipments page script loaded successfully');
    } catch (error) {
      console.error('Failed to load shipments page script:', error);
    }
  }

  async initShippingServicesPage() {
    try {
      const m = await import('./components/shipping/services.js');
      window.Alpine.data('shippingServices', m.default);
      console.log('⚙️ Shipping Services page script loaded successfully');
    } catch (error) {
      console.error('Failed to load shipping services page script:', error);
    }
  }

  // Setup global event listeners
  setupEventListeners() {
    // Theme toggle
    document.addEventListener('click', (e) => {
      if (e.target.matches('[data-theme-toggle]')) {
        this.themeManager.toggleTheme();
      }
    });

    // Full screen toggle
    document.addEventListener('click', (e) => {
      const fullscreenButton = e.target.closest('[data-fullscreen-toggle]');
      if (fullscreenButton) {
        e.preventDefault();
        this.toggleFullscreen();
      }
    });

    // Global keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      this.handleKeyboardShortcuts(e);
    });
  }

  // Handle keyboard shortcuts
  handleKeyboardShortcuts(event) {
    // Ctrl/Cmd + K for search
    const isSearchShortcut =
      (event.ctrlKey || event.metaKey) &&
      !event.altKey &&
      !event.shiftKey &&
      (event.code === 'KeyK' || event.key === 'k' || event.key === 'K');

    if (isSearchShortcut) {
      event.preventDefault();
      const searchInput = document.querySelector('[data-search-input]');
      if (searchInput) searchInput.focus();
    }
  }

  // Replace the literal "Ctrl+K" placeholder hint with the platform-correct one.
  localizeShortcutHints() {
    const isMac = /Mac|iPhone|iPad|iPod/i.test(
      (navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform || ''
    );
    if (!isMac) return;

    document.querySelectorAll('[data-search-input]').forEach((el) => {
      if (el.placeholder && el.placeholder.includes('Ctrl+K')) {
        el.placeholder = el.placeholder.replace('Ctrl+K', '⌘K');
      }
    });
  }

  // Toggle fullscreen
  async toggleFullscreen() {
    try {
      if (!document.fullscreenElement) {
        await document.documentElement.requestFullscreen();
      } else {
        await document.exitFullscreen();
      }
    } catch (error) {
      console.error('Fullscreen toggle failed:', error);
    }
  }

  // Get component instance
  getComponent(name) {
    return this.components.get(name);
  }

  // Initialize navigation functionality
  initNavigation() {
    const currentPage = window.location.pathname;
    const elementsPages = [
      '/elements', '/elements/alerts', '/elements/badges',
      '/elements/buttons', '/elements/cards', '/elements/modals',
      '/elements/forms', '/elements/tables'
    ];

    const isElementsPage = elementsPages.some(page => currentPage.includes(page));

    if (isElementsPage) {
      const elementsSubmenu = document.getElementById('elementsSubmenu');
      const elementsToggle = document.querySelector('[data-bs-target="#elementsSubmenu"]');

      if (elementsSubmenu && elementsToggle) {
        elementsSubmenu.classList.add('show');
        elementsToggle.setAttribute('aria-expanded', 'true');

        const activeSubmenuLink = document.querySelector(`.nav-submenu a[href="${currentPage}"]`);
        if (activeSubmenuLink) {
          activeSubmenuLink.classList.add('active');
        }
      }
    }

    

    
  }

  // Initialize Alpine.js
  initAlpine() {
    // Shared navbar search — uses the factory from search-component.js
    const navbarPages = [
      { title: 'Dashboard',             url: '/',                               type: 'page' },
      { title: 'Analytics',             url: '/analytics',                      type: 'page' },
      { title: 'Reports',               url: '/reports',                        type: 'page' },
      { title: 'Orders',                url: '/orders',                         type: 'page' },
      { title: 'Coupon Codes',          url: '/promotions/coupons',             type: 'page' },
      { title: 'Offers & Deals',        url: '/promotions/offers',              type: 'page' },
      { title: 'Invoices',              url: '/invoices',                       type: 'page' },
      { title: 'Payments',              url: '/payments',                       type: 'page' },
      { title: 'Refunds',               url: '/refunds',                        type: 'page' },
      { title: 'Returns',               url: '/returns',                        type: 'page' },
      { title: 'Shipments & Tracking',  url: '/shipping/shipments',             type: 'page' },
      { title: 'Shipping Services',     url: '/shipping/services',              type: 'page' },
      { title: 'Warehouses',            url: '/catalog/warehouses',             type: 'page' },
      { title: 'Stock Levels',          url: '/inventory/stock-management',     type: 'page' },
      { title: 'Stock Transfers',       url: '/inventory/stock-transfers',      type: 'page' },
      { title: 'Adjustments',           url: '/inventory/adjustments',          type: 'page' },
      { title: 'Products',              url: '/catalog/products',               type: 'page' },
      { title: 'Categories',            url: '/catalog/categories',             type: 'page' },
      { title: 'Brands',                url: '/catalog/brands',                 type: 'page' },
      { title: 'Attributes',            url: '/catalog/attributes',             type: 'page' },
      { title: 'Units of Measure',      url: '/catalog/uom',                    type: 'page' },
      { title: 'Tax Rates',             url: '/catalog/tax-rates',              type: 'page' },
      { title: 'HSN Codes',             url: '/catalog/hsn-codes',              type: 'page' },
      { title: 'Users',                 url: '/users',                          type: 'page' },
      { title: 'Roles & Permissions',   url: '/roles-permissions',              type: 'page' },
      { title: 'Customers',             url: '/customers',                      type: 'page' },
      { title: 'Villages',              url: '/villages',                       type: 'page' },
      { title: 'Order Reasons',         url: '/order-reasons',                  type: 'page' },
      { title: 'Team Chat',             url: '/chat',                           type: 'page' },
      { title: 'Messages',              url: '/messages',                       type: 'page' },
      { title: 'Calendar',              url: '/calendar',                       type: 'page' },
      { title: 'Files',                 url: '/files',                          type: 'page' },
      { title: 'Forms',                 url: '/forms',                          type: 'page' },
      { title: 'UI Elements',           url: '/elements',                       type: 'page' },
      { title: 'Settings',              url: '/settings',                       type: 'page' },
      { title: 'Security',              url: '/security',                       type: 'page' },
      { title: 'Help & Support',        url: '/help',                           type: 'page' },
    ];

    Alpine.data('searchComponent', createSearchComponent({
      getResults: (query) =>
        navbarPages.filter(p => p.title.toLowerCase().includes(query.toLowerCase())),
    }));

    // Stats counter — animates from 0 to target on load
    Alpine.data('statsCounter', (targetValue = 0) => ({
      value: 0,
      
      init() {
        const duration = 1000;
        const steps = 30;
        const stepValue = targetValue / steps;
        let currentStep = 0;
        
        const timer = setInterval(() => {
          this.value = Math.floor(this.value + stepValue);
          currentStep++;
          
          if (currentStep >= steps) {
            this.value = targetValue;
            clearInterval(timer);
          }
        }, duration / steps);
      },
    }));

    Alpine.data('themeSwitch', () => ({
      currentTheme: 'light',

      init() {
        this.currentTheme = localStorage.getItem('theme') || 'light';
      },

      toggle() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-bs-theme', this.currentTheme);
        localStorage.setItem('theme', this.currentTheme);
      }
    }));

    Alpine.data('iconDemo', () => ({
      currentProvider: 'bootstrap',

      switchProvider(provider) {
        this.currentProvider = provider;
        iconManager.switchProvider(provider);
      },

      getIcon(iconName) {
        return iconManager.get(iconName);
      }
    }));

    // Quick Add Form for Dashboard
    Alpine.data('quickAddForm', () => ({
      itemType: 'task',
      title: '',
      description: '',
      priority: 'medium',
      dateTime: '',
      assignee: '',

      init() {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        this.dateTime = now.toISOString().slice(0, 16);
      },

      resetForm() {
        this.itemType = 'task';
        this.title = '';
        this.description = '';
        this.priority = 'medium';
        this.assignee = '';
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        this.dateTime = now.toISOString().slice(0, 16);
      },

      saveItem() {
        if (!this.title.trim()) {
          window.AdminApp?.notificationManager?.warning('Please enter a title');
          return;
        }

        const typeLabels = { task: 'Task', note: 'Note', event: 'Event', reminder: 'Reminder' };

        window.AdminApp?.notificationManager?.success(
          `${typeLabels[this.itemType]} "${this.title}" created successfully!`
        );

        this.resetForm();
      }
    }));

    // Expose Alpine globally BEFORE starting it so alpine:init listeners can use it
    window.Alpine = Alpine;
    Alpine.start();
  }

  // Cleanup method
  destroy() {
    this.components.forEach(component => {
      if (component.destroy) {
        component.destroy();
      }
    });
    this.components.clear();
    this.isInitialized = false;
  }
}

// Create global app instance
const app = new AdminApp();

// Initialize app when module loads
app.init();

// Tear down on page hide so listeners/intervals don't leak across SPA-style nav
window.addEventListener('pagehide', () => app.destroy(), { once: true });

// Export for global access
window.AdminApp = app;
window.IconManager = iconManager;

// Export the app instance for module imports
export default app;
