import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';
import { createSearchComponent } from '../utils/search-component.js';

// ─── CSRF helper ─────────────────────────────────────────────────────────────
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
  const { headers, ...otherOptions } = options;
  const fetchHeaders = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': getCsrfToken(),
    ...(headers || {}),
  };

  if (otherOptions.body instanceof FormData) {
    delete fetchHeaders['Content-Type'];
  }

  const res = await fetch(url, {
    headers: fetchHeaders,
    ...otherOptions,
  });

  const text = await res.text();
  const data = text ? JSON.parse(text) : {};

  if (!res.ok) {
    const validation = data?.errors ? Object.values(data.errors).flat().join(' ') : '';
    const message = validation || data?.message || data?.error || 'Request failed';
    if (res.status === 403 || message.toLowerCase().includes("authoriz") || message.toLowerCase().includes("forbidden")) { window.location.href = "/"; return; }
    throw new Error(message);
  }

  return data;
}

function downloadBlob(filename, content, type) {
  const blob = new Blob([content], { type });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  URL.revokeObjectURL(link.href);
  document.body.removeChild(link);
}

function csvEscape(value) {
  return `"${String(value ?? '').replace(/"/g, '""')}"`;
}

function parseCsvLine(line) {
  const values = [];
  let current = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const next = line[i + 1];

    if (char === '"' && inQuotes && next === '"') {
      current += '"';
      i++;
    } else if (char === '"') {
      inQuotes = !inQuotes;
    } else if (char === ',' && !inQuotes) {
      values.push(current.trim());
      current = '';
    } else {
      current += char;
    }
  }

  values.push(current.trim());
  return values;
}

function formatRoleName(role) {
  if (!role) return 'User';
  return role.split(/\s+/).map(part => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase()).join(' ');
}

function splitFullName(name) {
  const parts = String(name ?? '').trim().split(/\s+/).filter(Boolean);
  return {
    first_name: parts[0] ?? '',
    middle_name: parts.length > 2 ? parts.slice(1, -1).join(' ') : '',
    last_name: parts.length > 1 ? parts[parts.length - 1] : '',
  };
}

function buildFullName(firstName, middleName, lastName) {
  return [firstName, middleName, lastName]
    .map(value => String(value ?? '').trim())
    .filter(Boolean)
    .join(' ');
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString() : 'N/A';
}

function formatDateTime(value) {
  if (!value) return 'N/A';

  return new Date(value).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

function formatActivityTimestamp(value) {
  return `Date & time: ${formatDateTime(value)}`;
}

function getModal(elementOrSelector) {
  const element = typeof elementOrSelector === 'string'
    ? document.querySelector(elementOrSelector)
    : elementOrSelector;

  return element ? Modal.getOrCreateInstance(element) : null;
}

// ─── Toast helper ─────────────────────────────────────────────────────────────
function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const id = 'toast-' + Date.now();
  const iconMap = {
    success: 'bi-check-circle-fill',
    danger: 'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info: 'bi-info-circle-fill',
  };

  const el = document.createElement('div');
  el.id = id;
  el.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
  el.setAttribute('role', 'alert');
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi ${iconMap[type] ?? 'bi-info-circle-fill'} me-2"></i><span></span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  el.querySelector('.toast-body span').textContent = message;

  container.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

async function confirmDelete({ title, text, confirmButtonText = 'Yes, delete it' }) {
  const result = await Swal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#dc3545',
    reverseButtons: true,
    focusCancel: true,
  });

  return result.isConfirmed;
}

// ─── Alpine components ────────────────────────────────────────────────────────
// ─── Permission Helpers ─────────────────────────────────────────────────────────────
function permissionActionLabel(name) {
  if (name === 'view_all_order') return 'Bulk View';

  const parts = String(name ?? '').split(/[-.]/);
  if (parts.length <= 1) return String(name ?? '');

  return parts.slice(1)
    .map(part => part.charAt(0).toUpperCase() + part.slice(1).replace(/_/g, ' '))
    .join(' ');
}

const permissionGroupMeta = {
  catalog_products: { label: 'Catalog & Products', icon: 'collection', order: 10 },
  inventory_warehousing: { label: 'Inventory & Warehousing', icon: 'boxes', order: 20 },
  sales_orders: { label: 'Sales & Orders', icon: 'cart', order: 30 },
  customers: { label: 'Customers', icon: 'people', order: 40 },
  marketing: { label: 'Marketing', icon: 'megaphone', order: 50 },
  core_system: { label: 'Core & System', icon: 'gear', order: 60 },
  utilities_tools: { label: 'Utilities & Tools', icon: 'tools', order: 70 },
  other: { label: 'Other', icon: 'grid', order: 99 },
};

function permissionGroupFor(name) {
  let prefix = String(name ?? '').split(/[-.]/)[0] || 'other';
  if (name === 'view_all_order') prefix = 'orders';
  if (prefix === 'bulkuser') prefix = 'user';
  if (prefix === 'audit-log') prefix = 'audit';

  const groups = {
    catalog_products: ['brand', 'catalog', 'category', 'productattribute', 'hsncode', 'taxrate', 'unitofmeasure', 'product'],
    inventory_warehousing: ['warehouse', 'inventoryadjustment', 'stockmanagement', 'stocktransfer'],
    sales_orders: ['orders', 'invoices', 'payments', 'refunds', 'returns'],
    customers: ['customer', 'customeraddress'],
    marketing: ['coupon', 'promotions'],
    utilities_tools: ['chat', 'messages', 'calendar', 'files', 'forms', 'security', 'help'],
    core_system: ['village', 'shipping', 'role', 'permission', 'user', 'dashboard', 'analytics', 'reports', 'settings', 'audit']
  };

  let groupKey = 'other';
  for (const [key, prefixes] of Object.entries(groups)) {
    if (prefixes.includes(prefix)) {
      groupKey = key;
      break;
    }
  }

  const defaultLabel = prefix.charAt(0).toUpperCase() + prefix.slice(1);
  const meta = permissionGroupMeta[groupKey] ?? { label: defaultLabel, icon: 'grid', order: 999 };

  return { key: groupKey, ...meta };
}

function getEntityLabel(name) {
  let prefix = String(name ?? '').split(/[-.]/)[0] || 'other';
  if (name === 'view_all_order') prefix = 'orders';
  if (prefix === 'bulkuser') prefix = 'user';
  if (prefix === 'audit-log') prefix = 'audit';
  
  const labels = {
    brand: 'Brands', catalog: 'Catalogs', category: 'Categories', productattribute: 'Product Attributes',
    hsncode: 'HSN Codes', taxrate: 'Tax Rates', unitofmeasure: 'Units of Measure', product: 'Products',
    warehouse: 'Warehouses', inventoryadjustment: 'Inventory Adjustments', stockmanagement: 'Stock Management',
    stocktransfer: 'Stock Transfers', orders: 'Orders', invoices: 'Invoices', payments: 'Payments',
    refunds: 'Refunds', returns: 'Returns', customer: 'Customers', customeraddress: 'Customer Addresses',
    coupon: 'Coupons', promotions: 'Promotions', village: 'Villages', shipping: 'Shipping', role: 'Roles',
    permission: 'Permissions', user: 'Users', audit: 'Audit Logs', dashboard: 'Dashboard', view_all_data: 'Global Data Visibility',
    chat: 'Team Chat', messages: 'Messages', calendar: 'Calendar', files: 'Files', forms: 'Forms', security: 'Security', help: 'Help & Support',
    analytics: 'Analytics', reports: 'Reports', settings: 'Settings'
  };
  return labels[prefix] || (prefix.charAt(0).toUpperCase() + prefix.slice(1));
}

function groupPermissions(permissions) {
  const groups = new Map();

  [...permissions]
    .sort((a, b) => String(a.name).localeCompare(String(b.name)))
    .forEach(permission => {
      const group = permissionGroupFor(permission.name);
      if (!groups.has(group.key)) {
        groups.set(group.key, {
          ...group,
          items: [],
          subGroups: [],
        });
      }
      
      const permObj = {
        ...permission,
        actionLabel: permissionActionLabel(permission.name),
      };
      
      const groupData = groups.get(group.key);
      groupData.items.push(permObj);
      
      const subLabel = getEntityLabel(permission.name);
      let subGroup = groupData.subGroups.find(s => s.label === subLabel);
      if (!subGroup) {
        subGroup = { label: subLabel, items: [] };
        groupData.subGroups.push(subGroup);
      }
      subGroup.items.push(permObj);
    });

  return [...groups.values()].sort((a, b) => a.order - b.order || a.label.localeCompare(b.label));
}

document.addEventListener('alpine:init', () => {

  // ─── userTable ──────────────────────────────────────────────────────────────
  Alpine.data('userTable', () => ({
    users: [],
    selectedUsers: [],
    searchQuery: '',
    statusFilter: '',
    roleFilter: '',
    sortField: 'name',
    sortDirection: 'asc',
    isLoading: false,
    availableRoles: [],
    growthPeriod: 7,
    realActivities: [],
    _activityInterval: null,

    // Server-side pagination meta
    currentPage: 1,
    totalPages: 1,
    totalUsers: 0,
    itemsPerPage: 10,

    // Chart instances
    charts: {},
    _resizeHandler: null,
    _themeObserver: null,

    // ── Lifecycle ────────────────────────────────────────────────────────────
    async init() {
      await this.loadRoles();
      await this.loadUsers();
      this.$nextTick(() => {
        this.initCharts();
        this.initResizeHandler();
      });
      this.fetchRealActivities();
      this._activityInterval = setInterval(() => {
        this.fetchRealActivities();
      }, 3000);
      window.addEventListener('pagehide', () => this.destroy(), { once: true });

      this._themeObserver = new MutationObserver(() => {
        this.$nextTick(() => {
          this.clearExistingCharts();
          this.initCharts();
        });
      });
      this._themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme'],
      });
    },

    destroy() {
      if (this._resizeHandler) {
        window.removeEventListener('resize', this._resizeHandler);
        this._resizeHandler = null;
      }
      if (this._themeObserver) {
        this._themeObserver.disconnect();
        this._themeObserver = null;
      }
      if (this._activityInterval) {
        clearInterval(this._activityInterval);
        this._activityInterval = null;
      }
      this.clearExistingCharts();
    },

    clearExistingCharts() {
      Object.values(this.charts).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') chart.destroy();
      });
      this.charts = {};
    },

    async loadRoles() {
      try {
        this.availableRoles = await apiFetch(`/api/roles/options?_t=${Date.now()}`);
        this.availableRoles = this.availableRoles.data ?? this.availableRoles;
      } catch {
        this.availableRoles = [
          { id: 'admin', name: 'Admin' },
          { id: 'manager', name: 'Manager' },
          { id: 'user', name: 'User' },
        ];
      }
    },

    initResizeHandler() {
      this._resizeHandler = () => {
        Object.values(this.charts).forEach(chart => {
          if (chart && typeof chart.updateOptions === 'function') {
            chart.updateOptions({ chart: { width: '100%' } }, false, true);
          }
        });
      };
      window.addEventListener('resize', this._resizeHandler);
    },

    // ── API: Load users (server-side filtering / sorting / pagination) ────────
    async loadUsers() {
      this.isLoading = true;
      try {
        const params = new URLSearchParams({
          page: this.currentPage,
          per_page: this.itemsPerPage,
          sort_by: this.sortField,
          sort_dir: this.sortDirection,
        });
        if (this.searchQuery) params.set('search', this.searchQuery);
        if (this.statusFilter === 'active') params.set('is_active', '1');
        if (this.statusFilter === 'inactive') params.set('is_active', '0');
        if (this.statusFilter === 'deleted') params.set('deleted', 'only');
        if (this.roleFilter) params.set('role', this.roleFilter);

        const data = await apiFetch(`/api/users?${params}`);

        // Map API response to the shape the template expects
        this.users = (data.data ?? []).map(u => this._mapUser(u));
        this.totalUsers = data.total ?? this.users.length;
        this.totalPages = data.last_page ?? 1;
        this.currentPage = data.current_page ?? 1;

        // Rebuild charts with fresh data
        this.$nextTick(() => {
          this.clearExistingCharts();
          this.initCharts();
        });
      } catch (err) {
        const msg = err.message.toLowerCase();
        if (msg.includes('right permissions') || msg.includes('authoriz') || msg.includes('unauthorized') || msg.includes('forbidden')) {
          window.location.href = '/';
          return;
        }
        showToast('Failed to load users: ' + err.message, 'danger');
      } finally {
        this.isLoading = false;
      }
    },

    // Normalise an API user object → template-friendly shape
    _mapUser(u) {
      const roleLabel = u.roles?.[0]?.name ?? 'User';
      const roleName = roleLabel.toLowerCase();
      const fallbackNameParts = splitFullName(u.name);
      const firstName = u.first_name ?? fallbackNameParts.first_name;
      const middleName = u.middle_name ?? fallbackNameParts.middle_name;
      const lastName = u.last_name ?? fallbackNameParts.last_name;
      const displayName = buildFullName(firstName, middleName, lastName) || u.name;
      return {
        id: u.id,
        name: displayName,
        first_name: firstName,
        middle_name: middleName,
        last_name: lastName,
        email: u.email,
        phone: u.phone ?? '',
        department: u.department ? u.department.name : '',
        department_id: u.department_id ?? '',
        manager_id: u.manager_id ?? '',
        manager: u.manager ? u.manager.name : '',
        employment_type: u.employment_type ?? 'Full-time',
        employee_id: u.employee_id ?? '',
        photo: u.photo ?? null,
        joining_date: u.joining_date ?? '',
        role: roleName,
        roleLabel,
        roleClass: this.roleBadgeClass(roleName),
        roles: u.roles ?? [],
        status: u.deleted_at ? 'deleted' : (u.is_active ? 'active' : 'inactive'),
        is_active: u.is_active,
        isDeleted: Boolean(u.deleted_at),
        deleted_at: u.deleted_at ?? null,
        lastActive: formatDate(u.updated_at),
        lastActiveDateTime: formatDateTime(u.updated_at),
        joinDate: formatDate(u.created_at),
        created_at: u.created_at,
        is_online: Boolean(u.is_online),
        last_login_at: u.last_login_at ? new Date(u.last_login_at).toLocaleString() : 'Never',
        device_type: u.device_type || 'Unknown',
        address_line_1: u.address_line_1,
        address_line_2: u.address_line_2,
        village_id: u.village_id,
        village_name: u.village_name,
        post_office: u.post_office,
        taluka: u.taluka,
        district: u.district,
        city: u.city,
        state: u.state,
        pincode: u.pincode,
        date_of_birth: u.date_of_birth,
        gender: u.gender,
        blood_group: u.blood_group,
        designation: u.designation,
        emergency_contact_name: u.emergency_contact_name,
        emergency_contact_phone: u.emergency_contact_phone,
        avatar: '/assets/images/default_avatar.jpeg',
      };
    },

    roleBadgeClass(role) {
      const normalized = String(role ?? '').toLowerCase();
      if (normalized === 'super admin') return 'bg-danger';
      if (normalized === 'admin') return 'bg-dark';
      if (normalized === 'manager') return 'bg-warning text-dark';
      return 'bg-primary';
    },

    // ── Filtering helpers (trigger server reload) ────────────────────────────
    filterUsers() {
      this.currentPage = 1;
      
      this.loadUsers();
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.loadUsers();
    },

    // ── Pagination ────────────────────────────────────────────────────────────
    get paginatedUsers() {
      return this.users; // already a single page from the server
    },

    get filteredUsers() {
      return this.users; // alias for compatibility with template bindings
    },

    get selectedRows() {
      return this.users.filter(user => this.selectedUsers.includes(String(user.id)));
    },

    get hasSelectedDeletedUsers() {
      return this.selectedRows.some(user => user.isDeleted);
    },

    get hasSelectedActiveUsers() {
      return this.selectedRows.some(user => !user.isDeleted);
    },

    get canBulkActivate() {
      return this.selectedRows.some(user => !user.isDeleted && !user.is_active);
    },

    get canBulkDeactivate() {
      return this.selectedRows.some(user => !user.isDeleted && user.is_active);
    },

    get visiblePages() {
      const delta = 2;
      const range = [];
      for (let i = Math.max(2, this.currentPage - delta);
        i <= Math.min(this.totalPages - 1, this.currentPage + delta); i++) {
        range.push(i);
      }
      const result = [];
      if (this.currentPage - delta > 2) result.push(1, '...');
      else result.push(1);
      result.push(...range);
      if (this.currentPage + delta < this.totalPages - 1) result.push('...', this.totalPages);
      else if (this.totalPages > 1) result.push(this.totalPages);
      return result.filter((v, i, a) => a.indexOf(v) === i && (typeof v === 'string' || v <= this.totalPages));
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadUsers();
      }
    },

    // ── Pagination display text ───────────────────────────────────────────────
    get pageFrom() {
      if (this.totalUsers === 0) return 0;
      return (this.currentPage - 1) * this.itemsPerPage + 1;
    },
    get pageTo() {
      return Math.min(this.currentPage * this.itemsPerPage, this.totalUsers);
    },

    // ── Selection management ──────────────────────────────────────────────────
    toggleAll(checked) {
      if (checked) {
        this.users.forEach(item => {
          if (!this.selectedUsers.includes(String(item.id))) {
            this.selectedUsers.push(String(item.id));
          }
        });
      } else {
        const currentIds = this.users.map(item => String(item.id));
        this.selectedUsers = this.selectedUsers.filter(id => !currentIds.includes(id));
      }
    },

    toggleUser(userId) {
      if (this.selectedUsers.includes(userId)) {
        this.selectedUsers = this.selectedUsers.filter(id => id !== userId);
      } else {
        this.selectedUsers = [...this.selectedUsers, userId];
      }
    },

    // ── CRUD Operations ───────────────────────────────────────────────────────
    editUser(user, isView = false) {
      if (user.isDeleted) {
        showToast('Restore this user before editing.', 'warning');
        return;
      }

      const form = Alpine.$data(document.querySelector('[x-data="userForm"]'));
      if (!form) return;
      form.isViewMode = isView;
      form.editingUserId = user.id;
      form.villageSearchQuery = user.village_name ?? '';
      form.form.first_name = user.first_name ?? '';
      form.form.middle_name = user.middle_name ?? '';
      form.form.last_name = user.last_name ?? '';
      form.form.email = user.email;
      form.form.phone = user.phone ?? '';
      form.form.department_id = user.department_id ?? '';
      form.form.manager_id = user.manager_id ?? '';
      form.form.employment_type = user.employment_type ?? 'Full-time';
      form.form.employee_id = user.employee_id ?? '';
      form.form.photo = user.photo ?? '';
      form.form.joining_date = user.joining_date ? String(user.joining_date).split('T')[0] : '';
      form.form.role = user.roles && user.roles.length ? user.roles[0].name : 'User';
      form.form.permissions = (user.permissions ?? []).map(p => p.name);
      form.form.is_active = user.is_active ?? true;
      form.form.address_line_1 = user.address_line_1 ?? '';
      form.form.address_line_2 = user.address_line_2 ?? '';
      form.form.village_id = user.village_id ?? '';
      form.form.village_name = user.village_name ?? '';
      form.form.post_office = user.post_office ?? '';
      form.form.taluka = user.taluka ?? '';
      form.form.district = user.district ?? '';
      form.form.city = user.city ?? '';
      form.form.state = user.state ?? '';
      form.form.pincode = user.pincode ?? '';
      form.form.date_of_birth = user.date_of_birth ? String(user.date_of_birth).split('T')[0] : '';
      form.form.gender = user.gender ?? '';
      form.form.blood_group = user.blood_group ?? '';
      form.form.designation = user.designation ?? '';
      form.form.emergency_contact_name = user.emergency_contact_name ?? '';
      form.form.emergency_contact_phone = user.emergency_contact_phone ?? '';
      form.form.password = '';
      form.form.password_confirmation = '';

      // Update modal title
      const title = document.querySelector('#userModal .modal-title');
      if (title) title.textContent = 'Edit User';

      getModal('#userModal')?.show();
    },

    openCreateUser() {
      const form = Alpine.$data(document.querySelector('[x-data="userForm"]'));
      if (form) form.resetForm();
      const title = document.querySelector('#userModal .modal-title');
      if (title) title.textContent = 'Add New User';
      getModal('#userModal')?.show();
    },

    async viewUser(user) {
      const form = Alpine.$data(document.querySelector('[x-data="userForm"]'));
      if (!form) return;

      form.isViewMode = true;
      form.editingUserId = user.id;

      try {
        const response = await apiFetch(`/api/users/${user.id}`);
        const fullUser = response.data ?? response;
        
        // Merge fullUser with mapped user to get all fields
        const completeUser = { ...user, ...fullUser };
        
        // Call editUser but flag as view mode
        this.editUser(completeUser, true);
        
        // Ensure the modal is shown
        getModal('#userModal')?.show();
      } catch (error) {
        showToast('Could not load user profile details.', 'danger');
        form.isViewMode = false;
      }
    },

    async toggleActive(user) {
      if (user.isDeleted) {
        showToast('Restore this user before changing account status.', 'warning');
        return;
      }

      try {
        const res = await apiFetch(`/api/users/${user.id}/toggle-active`, { method: 'PATCH' });
        showToast(res.message);
        await this.loadUsers();

        const profile = Alpine.$data(document.querySelector('[x-data="userProfile"]'));
        if (profile?.user?.id === user.id) {
          const refreshed = this.users.find(u => u.id === user.id);
          if (refreshed) profile.user = refreshed;
        }
      } catch (err) {
        showToast(err.message, 'danger');
      }
    },

    async deleteUser(user) {
      const confirmed = await confirmDelete({
        title: 'Temporarily delete user?',
        text: `Do you want to move ${user.name} to deleted users? You can restore this user later.`,
        confirmButtonText: 'Yes, delete user',
      });
      if (!confirmed) return;

      try {
        const res = await apiFetch(`/api/users/${user.id}`, { method: 'DELETE' });
        showToast(res.message || `${user.name} deleted successfully.`, 'success');
        await this.loadUsers();
      } catch (err) {
        showToast(`Failed to delete ${user.name}: ${err.message}`, 'danger');
      }
    },

    async restoreUser(user) {
      try {
        const res = await apiFetch(`/api/users/${user.id}/restore`, { method: 'PATCH' });
        showToast(res.message || `${user.name} restored successfully.`, 'success');
        await this.loadUsers();
      } catch (err) {
        showToast(`Failed to restore ${user.name}: ${err.message}`, 'danger');
      }
    },

    async forceDeleteUser(user) {
      const confirmed = await confirmDelete({
        title: 'Permanently delete user?',
        text: `This will permanently delete ${user.name}. This action cannot be undone.`,
        confirmButtonText: 'Yes, permanently delete',
      });
      if (!confirmed) return;

      try {
        const res = await apiFetch(`/api/users/${user.id}/force`, { method: 'DELETE' });
        showToast(res.message || `${user.name} permanently deleted successfully.`, 'success');
        await this.loadUsers();
      } catch (err) {
        showToast(`Failed to permanently delete ${user.name}: ${err.message}`, 'danger');
      }
    },

    // ── Bulk Operations ───────────────────────────────────────────────────────
    async bulkAction(action) {
      if (this.selectedUsers.length === 0) {
        showToast('Please select users first.', 'warning');
        return;
      }
      if (action === 'delete') {
        const confirmed = await confirmDelete({
          title: 'Temporarily delete selected users?',
          text: `Do you want to move ${this.selectedUsers.length} selected user(s) to deleted users? You can restore them later.`,
          confirmButtonText: 'Yes, delete users',
        });
        if (!confirmed) return;
      }
      if (action === 'force-delete') {
        const confirmed = await confirmDelete({
          title: 'Permanently delete selected users?',
          text: `This will permanently delete ${this.selectedUsers.length} selected user(s). This action cannot be undone.`,
          confirmButtonText: 'Yes, permanently delete',
        });
        if (!confirmed) return;
      }

      try {
        const res = await apiFetch('/api/users/bulk-action', {
          method: 'POST',
          body: JSON.stringify({ action, ids: this.selectedUsers }),
        });
        showToast(res.message, 'success');
        this.selectedUsers = [];
        await this.loadUsers();
      } catch (err) {
        showToast(`Bulk action failed: ${err.message}`, 'danger');
      }
    },

    _queryParams(overrides = {}) {
      const params = new URLSearchParams({
        page: overrides.page ?? this.currentPage,
        per_page: overrides.per_page ?? this.itemsPerPage,
        sort_by: this.sortField,
        sort_dir: this.sortDirection,
      });
      if (this.searchQuery) params.set('search', this.searchQuery);
      if (this.statusFilter === 'active') params.set('is_active', '1');
      if (this.statusFilter === 'inactive') params.set('is_active', '0');
      if (this.statusFilter === 'deleted') params.set('deleted', 'only');
      if (this.roleFilter) params.set('role', this.roleFilter);
      return params;
    },

    async fetchAllFilteredUsers() {
      const first = await apiFetch(`/api/users?${this._queryParams({ page: 1, per_page: 100 })}`);
      const mapped = (first.data ?? []).map(u => this._mapUser(u));
      const lastPage = first.last_page ?? 1;

      for (let page = 2; page <= lastPage; page++) {
        const data = await apiFetch(`/api/users?${this._queryParams({ page, per_page: 100 })}`);
        mapped.push(...(data.data ?? []).map(u => this._mapUser(u)));
      }

      return mapped;
    },

    // ── Export ────────────────────────────────────────────────────────────────
    async exportUsers() {
      try {
        const users = await this.fetchAllFilteredUsers();
        const headers = [
          'ID', 'First Name', 'Middle Name', 'Last Name', 'Email', 'Phone',
          'Employee ID', 'Employment Type', 'Designation', 'Department', 'Manager',
          'Role', 'Status', 'Date of Birth', 'Gender', 'Blood Group',
          'Emergency Contact Name', 'Emergency Contact Phone',
          'Address Line 1', 'Address Line 2', 'Village Name', 'Post Office',
          'Taluka', 'District', 'City', 'State', 'Pincode'
        ];
        const rows = users.map(u => [
          u.id, u.first_name, u.middle_name, u.last_name, u.email, u.phone,
          u.employee_id, u.employment_type, u.designation, u.department, u.manager,
          u.roleLabel, u.status, u.date_of_birth, u.gender, u.blood_group,
          u.emergency_contact_name, u.emergency_contact_phone,
          u.address_line_1, u.address_line_2, u.village_name, u.post_office,
          u.taluka, u.district, u.city, u.state, u.pincode
        ]);
        const csv = [headers, ...rows].map(r => r.map(csvEscape).join(',')).join('\n');
        downloadBlob('users-export.csv', csv, 'text/csv;charset=utf-8;');
        showToast(`Exported ${users.length} user(s).`);
      } catch (err) {
        showToast('Export failed: ' + err.message, 'danger');
      }
    },

    sendBulkInvites() {
      if (this.selectedUsers.length === 0) {
        showToast('Please select users to send invites to.', 'warning');
        return;
      }
      const selected = this.users.filter(user => this.selectedUsers.includes(user.id));
      const emails = selected.map(user => user.email).filter(Boolean);
      if (emails.length === 0) {
        showToast('Selected users do not have email addresses.', 'warning');
        return;
      }
      const subject = encodeURIComponent('Invitation to Metis Admin');
      const body = encodeURIComponent('Hi,\n\nYou are invited to access Metis Admin.\n\nThanks.');
      window.location.href = `mailto:${emails.join(',')}?subject=${subject}&body=${body}`;
      showToast(`Prepared invite email for ${emails.length} user(s).`);
      this.selectedUsers = [];
    },

    async generateReport() {
      try {
        const users = await this.fetchAllFilteredUsers();
        const report = {
          generatedAt: new Date().toISOString(),
          filters: {
            search: this.searchQuery,
            status: this.statusFilter || 'all',
            role: this.roleFilter || 'all',
          },
          totalUsers: users.length,
          stats: this.stats,
          users,
        };
        downloadBlob('user-report.json', JSON.stringify(report, null, 2), 'application/json');
        showToast(`Generated report for ${users.length} user(s).`);
      } catch (err) {
        showToast('Report failed: ' + err.message, 'danger');
      }
    },

    // ── Computed stats (derived from current page + totals) ───────────────────
    get stats() {
      const active = this.users.filter(u => u.status === 'active').length;
      const inactive = this.users.filter(u => u.status === 'inactive').length;
      const now = new Date();
      const newThisMonth = this.users.filter(u => {
        const d = new Date(u.joinDate);
        return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
      }).length;

      return {
        total: this.totalUsers,
        active,
        inactive,
        newThisMonth,
        activePercentage: this.users.length > 0 ? Math.round((active / this.users.length) * 100) : 0,
      };
    },

    get departmentStats() {
      const counts = this.users.reduce((acc, u) => {
        const dept = u.department || 'Unknown';
        acc[dept] = (acc[dept] || 0) + 1;
        return acc;
      }, {});
      const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
      return Object.entries(counts).map(([name, count], i) => ({
        name, count,
        percentage: this.users.length > 0 ? Math.round((count / this.users.length) * 100) : 0,
        color: colors[i % colors.length],
      }));
    },

    async fetchRealActivities() {
      try {
        const res = await apiFetch('/api/activities/recent');
        if (res && res.activities) {
          this.realActivities = res.activities;
        }
      } catch (err) {
        console.error('Failed to fetch activities', err);
      }
    },

    get recentActivities() {
      if (!this.realActivities) return [];
      return this.realActivities.slice(0, 10).map((a, i) => ({
        id: a.id || i,
        user: a.causer_name || 'System',
        action: `${a.description} a ${a.subject_type}`,
        time: a.time_ago || '',
        type: 'info',
        icon: 'info-circle',
        details: 'Activity logged',
      }));
    },

    get systemAlerts() {
      const alerts = [];
      if (this.totalUsers === 0) {
        alerts.push({
          id: 2,
          title: 'No Users',
          message: 'No users found in the system. Add your first user.',
          type: 'info',
          time: 'Just now',
        });
      }
      return alerts;
    },

    // ── Charts ────────────────────────────────────────────────────────────────
    initCharts() {
      this._initSparkline();
      this._initGrowthChart();
      this._initRoleChart();
    },

    setGrowthPeriod(days) {
      this.growthPeriod = Number(days);
      this.$nextTick(() => {
        if (this.charts.userGrowth) {
          this.charts.userGrowth.destroy();
          delete this.charts.userGrowth;
        }
        document.querySelector('#userGrowthChart')?.removeAttribute('data-chart-initialized');
        this._initGrowthChart();
      });
    },

    _initSparkline() {
      const el = document.querySelector('#activeUserChart');
      if (!el || el.hasAttribute('data-chart-initialized')) return;
      el.setAttribute('data-chart-initialized', 'true');
      this.charts.activeUsers = new ApexCharts(el, {
        series: [{ name: 'Active', data: [65, 70, 80, 85, 90, 95, 88] }],
        chart: { type: 'line', height: 50, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 2 },
        colors: ['#10b981'],
      });
      this.charts.activeUsers.render();
    },

    _initGrowthChart() {
      const el = document.querySelector('#userGrowthChart');
      if (!el || el.hasAttribute('data-chart-initialized')) return;
      el.setAttribute('data-chart-initialized', 'true');

      const period = Number(this.growthPeriod) || 7;
      const dayCounts = new Array(period).fill(0);
      const dayLabels = [];
      const now = new Date();
      for (let i = period - 1; i >= 0; i--) {
        const d = new Date(now);
        d.setDate(d.getDate() - i);
        dayLabels.push(d.toLocaleDateString('en', period <= 7 ? { weekday: 'short' } : { month: 'short', day: 'numeric' }));
        dayCounts[period - 1 - i] = this.users.filter(u => {
          const j = new Date(u.created_at || new Date());
          return j.toDateString() === d.toDateString();
        }).length;
      }

      this.charts.userGrowth = new ApexCharts(el, {
        series: [{ name: 'New Users', data: dayCounts }],
        chart: { type: 'bar', height: 250, width: '100%', toolbar: { show: false }, zoom: { enabled: false } },
        colors: ['#6366f1'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        xaxis: {
          categories: dayLabels,
          axisBorder: { show: false },
          axisTicks: { show: false },
          labels: { style: { fontSize: '12px', colors: '#64748b' } },
        },
        yaxis: { show: false },
        grid: { show: false },
        dataLabels: { enabled: false },
        tooltip: { theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light' },
      });
      this.charts.userGrowth.render();
    },

    _initRoleChart() {
      const el = document.querySelector('#roleDistributionChart');
      if (!el || el.hasAttribute('data-chart-initialized')) return;
      el.setAttribute('data-chart-initialized', 'true');

      const roleCounts = this.users.reduce((acc, u) => {
        acc[u.roleLabel] = (acc[u.roleLabel] || 0) + 1;
        return acc;
      }, {});

      if (Object.keys(roleCounts).length === 0) return;

      this.charts.roleDistribution = new ApexCharts(el, {
        series: Object.values(roleCounts),
        chart: { type: 'donut', height: 140 },
        labels: Object.keys(roleCounts),
        colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '70%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light' },
      });
      this.charts.roleDistribution.render();
    },
  }));

  // ─── userForm ───────────────────────────────────────────────────────────────
  Alpine.data('userForm', () => ({
    form: {
      first_name: '',
      middle_name: '',
      last_name: '',
      email: '',
      phone: '',
      department_id: '',
      manager_id: '',
      employment_type: 'Full-time',
      employee_id: '',
      photo: '',
      photoFile: null,
      joining_date: '',
      role: 'User',
      is_active: true,
      address_line_1: '',
      address_line_2: '',
      village_id: '',
      village_name: '',
      post_office: '',
      taluka: '',
      district: '',
      city: '',
      state: '',
      pincode: '',
      date_of_birth: '',
      gender: '',
      blood_group: '',
      designation: '',
      emergency_contact_name: '',
      emergency_contact_phone: '',
      password: '',
      password_confirmation: '',
      permissions: [],
    },
    availablePermissions: [],
    departments: [],
    managers: [],
    designations: [],
    employmentTypes: [],
    
    get groupedAvailablePermissions() {
      return groupPermissions(this.availablePermissions);
    },
    
    villageSearchQuery: '',
    villageResults: [],
    editingUserId: null,
    saving: false,
    roles: [],
    rolesLoading: false,
    rolesError: '',
    isViewMode: false,

    async searchVillages() {
      if (!this.villageSearchQuery || this.villageSearchQuery.length < 3) {
        this.villageResults = [];
        return;
      }
      try {
        const res = await fetch(`/api/villages/search?q=${encodeURIComponent(this.villageSearchQuery)}`, {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        this.villageResults = data.data || [];
      } catch (e) {
        console.error('Village search failed:', e);
      }
    },

    selectVillage(v) {
      this.form.village_id = v.id;
      this.form.village_name = v.village_name || v.name || '';
      this.form.post_office = v.post_so_name || v.post_office || '';
      this.form.taluka = v.taluka_name || v.taluka || '';
      this.form.district = v.district_name || v.district || '';
      this.form.city = v.district_name || v.district || v.city || '';
      this.form.state = v.state_name || v.state || '';
      this.form.pincode = v.pincode || '';

      this.villageSearchQuery = '';
      this.villageResults = [];
    },

    async init() {
      this.resetForm();
      await this.loadRoles();

      // Reset form when modal is hidden
      document.getElementById('userModal')?.addEventListener('hidden.bs.modal', () => {
        this.resetForm();
        const title = document.querySelector('#userModal .modal-title');
        if (title) title.textContent = 'Add New User';
      });
      
      this.loadHrData();
    },

    async loadHrData() {
      try {
        const dData = await apiFetch('/api/departments?per_page=1000');
        this.departments = dData.data ?? dData;
      } catch (e) {
        this.departments = [];
      }
      try {
        const mData = await apiFetch('/api/users?per_page=1000');
        this.managers = mData.data ?? mData;
      } catch (e) {
        this.managers = [];
      }
      try {
        const desData = await apiFetch('/api/hr-settings/designations');
        this.designations = desData.data ?? desData;
      } catch (e) {
        this.designations = [];
      }
      try {
        const empData = await apiFetch('/api/hr-settings/employment_types');
        this.employmentTypes = empData.data ?? empData;
      } catch (e) {
        this.employmentTypes = [];
      }
    },

    async loadRoles() {
      this.rolesLoading = true;
      this.rolesError = '';
      try {
        const data = await apiFetch(`/api/roles/options?_t=${Date.now()}`);
        this.roles = data.data ?? data;
      } catch (err) {
        try {
          const data = await apiFetch('/api/roles?per_page=100&deleted=without&sort_by=name&sort_dir=asc');
          this.roles = data.data ?? data;
        } catch (fallbackErr) {
          this.roles = [];
          this.rolesError = fallbackErr.message || err.message;
        }
      } finally {
        this.form.is_active = true;
        this.form.permissions = [];
        if (this.roles.length > 0 && !this.roles.some(role => role.name === this.form.role)) {
          this.form.role = this.roles[0].name;
        }
        this.rolesLoading = false;
      }
      
      try {
        const pData = await apiFetch(`/api/permissions/options?_t=${Date.now()}`);
        this.availablePermissions = pData.data ?? pData;
      } catch (err) {
        this.availablePermissions = [];
      }
    },

    resetForm() {
      this.form = {
        first_name: '',
        middle_name: '',
        last_name: '',
        email: '',
        phone: '',
        department_id: '',
        manager_id: '',
        employment_type: 'Full-time',
        employee_id: '',
        photoFile: null,
        joining_date: new Date().toISOString().split('T')[0],
        role: 'User',
        is_active: true,
        address_line_1: '',
        address_line_2: '',
        village_id: '',
        village_name: '',
        post_office: '',
        taluka: '',
        district: '',
        city: '',
        state: '',
        pincode: '',
        date_of_birth: '',
        gender: '',
        blood_group: '',
        designation: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        password: '',
        password_confirmation: '',
      };
      this.villageSearchQuery = '';
      this.villageResults = [];
      this.editingUserId = null;
      this.saving = false;
      this.isViewMode = false;
    },

    generateEmployeeId() {
      const timestamp = Date.now().toString().slice(-6);
      this.form.employee_id = `EMP-${timestamp}`;
    },

    handlePhotoUpload(event) {
      const file = event?.target?.files?.[0];
      if (!file) return;

      if (file.size > 2 * 1024 * 1024) {
        showToast('The selected image exceeds the maximum size limit of 2MB.', 'warning');
        event.target.value = '';
        return;
      }

      this.form.photo = URL.createObjectURL(file);
      this.form.photoFile = file;
    },

    async saveUser() {
      if (!this.form.first_name || !this.form.email) {
        showToast('First name and email are required.', 'warning');
        return;
      }
      if (!this.editingUserId && !this.form.password) {
        showToast('Password is required when creating a user.', 'warning');
        return;
      }

      this.saving = true;
      try {
        const name = buildFullName(this.form.first_name, this.form.middle_name, this.form.last_name);

        let formattedPhone = null;
        if (this.form.phone) {
          formattedPhone = String(this.form.phone).replace(/\D/g, '');
          if (formattedPhone.length !== 10) {
            showToast('Phone number must be exactly 10 digits.', 'warning');
            this.saving = false;
            return;
          }
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('first_name', this.form.first_name);
        formData.append('middle_name', this.form.middle_name ?? '');
        formData.append('last_name', this.form.last_name ?? '');
        formData.append('email', this.form.email);
        formData.append('phone', formattedPhone ?? '');
        formData.append('department_id', this.form.department_id ?? '');
        formData.append('manager_id', this.form.manager_id ?? '');
        formData.append('employment_type', this.form.employment_type ?? '');
        formData.append('employee_id', this.form.employee_id ?? '');
        formData.append('joining_date', this.form.joining_date ?? '');
        formData.append('is_active', this.form.is_active ? '1' : '0');
        formData.append('roles[]', this.form.role);
        
        if (this.form.permissions && this.form.permissions.length > 0) {
          this.form.permissions.forEach(p => formData.append('permissions[]', p));
        }

        const addressFields = ['address_line_1', 'address_line_2', 'village_id', 'village_name', 'post_office', 'taluka', 'district', 'city', 'state', 'pincode'];
        for (const field of addressFields) {
          formData.append(field, this.form[field] ?? '');
        }

        const advancedFields = ['date_of_birth', 'gender', 'blood_group', 'designation', 'emergency_contact_name', 'emergency_contact_phone'];
        for (const field of advancedFields) {
          formData.append(field, this.form[field] ?? '');
        }

        if (this.form.password) {
          formData.append('password', this.form.password);
          formData.append('password_confirmation', this.form.password_confirmation);
        }

        if (this.form.photoFile) {
          formData.append('photo_file', this.form.photoFile);
        }

        if (this.editingUserId) {
          formData.append('_method', 'PUT');
          const res = await apiFetch(`/api/users/${this.editingUserId}`, {
            method: 'POST', // Laravel uses POST + _method=PUT for FormData
            body: formData,
          });
          showToast(res.message || 'User updated successfully.', 'success');
        } else {
          const res = await apiFetch('/api/users', {
            method: 'POST',
            body: formData,
          });
          showToast(res.message || 'User created successfully.', 'success');
        }

        // Close modal
        getModal('#userModal')?.hide();

        // Reload table
        const table = Alpine.$data(document.querySelector('[x-data="userTable"]'));
        if (table) await table.loadUsers();

      } catch (err) {
        showToast(`${this.editingUserId ? 'Failed to update user' : 'Failed to create user'}: ${err.message}`, 'danger');
      } finally {
        this.saving = false;
      }
    },
  }));



  // ─── importForm ─────────────────────────────────────────────────────────────
  Alpine.data('importForm', () => ({
    file: null,
    importing: false,
    result: null,
    parsedHeaders: [],
    parsedRows: [],
    previewRows: [],
    headers: ['First Name', 'Middle Name', 'Last Name', 'Email', 'Phone', 'password'],

    init() {
      // Reset form when modal is hidden
      document.getElementById('importModal')?.addEventListener('hidden.bs.modal', () => {
        this.reset();
      });
    },

    reset() {
      this.file = null;
      const fileInput = document.getElementById('importFileInput');
      if (fileInput) fileInput.value = '';
      this.result = null;
      this.parsedHeaders = [];
      this.parsedRows = [];
      this.previewRows = [];
    },

    downloadTemplate() {
      const csv = this.headers.map(csvEscape).join(',');
      downloadBlob(`users-import-template.csv`, csv, 'text/csv;charset=utf-8;');
      showToast(`Downloaded template.`);
    },

    async handleFile(event) {
      this.file = event.target.files[0] ?? null;
      this.result = null;
      this.parsedHeaders = [];
      this.parsedRows = [];
      this.previewRows = [];

      if (!this.file) return;

      const text = await this.file.text();
      const lines = text.trim().split('\n').filter(l => l.trim());
      if (lines.length < 2) {
        showToast('CSV file is empty or has no data rows.', 'warning');
        return;
      }

      this.parsedHeaders = parseCsvLine(lines[0]);

      for (let i = 1; i < lines.length; i++) {
        this.parsedRows.push(parseCsvLine(lines[i]));
      }
      this.previewRows = this.parsedRows.slice(0, 3);
    },

    async importUsers() {
      if (!this.file || this.parsedRows.length === 0) {
        showToast('Please select a valid CSV file with data.', 'warning');
        return;
      }
      this.importing = true;
      this.result = null;

      let created = 0;
      let errors = [];

      // We need to resolve departments and managers to IDs
      const formComp = Alpine.$data(document.querySelector('[x-data="userForm"]'));
      const deps = formComp ? formComp.departments : [];
      const mgrs = formComp ? formComp.managers : [];

      for (let i = 0; i < this.parsedRows.length; i++) {
        const row = this.parsedRows[i];
        
        let [firstName, middleName, lastName, email, phone, password] = row;

        if (!firstName || !email) { errors.push(`Row ${i + 2}: missing first name or email`); continue; }

        const name = buildFullName(firstName, middleName, lastName);

        try {
          await apiFetch('/api/users', {
            method: 'POST',
            body: JSON.stringify({
              name,
              first_name: firstName,
              middle_name: middleName || null,
              last_name: lastName || null,
              email,
              phone: phone || null,
              password: password || 'Default@123',
              password_confirmation: password || 'Default@123',
              is_active: true,
              roles: ['User'],
            }),
          });
          created++;
        } catch (err) {
          errors.push(`Row ${i + 2} (${email}): ${err.message}`);
        }
      }

      this.result = { created, errors };
      this.importing = false;

      if (created > 0) {
        showToast(`Imported ${created} user(s) successfully.`);
        const table = Alpine.$data(document.querySelector('[x-data="userTable"]'));
        if (table) await table.loadUsers();
      }
      if (errors.length > 0) {
        showToast(`${errors.length} row(s) failed. See import result.`, 'warning');
      }
    },
  }));

  // ─── searchComponent ─────────────────────────────────────────────────────────
  Alpine.data('searchComponent', createSearchComponent({
    delayMs: 300,
    getResults(query) {
      const table = Alpine.$data(document.querySelector('[x-data="userTable"]'));
      if (table) {
        table.searchQuery = query;
        table.filterUsers();
      }
      const q = query.toLowerCase();
      return [
        { title: 'Dashboard', url: '/', type: 'page' },
        { title: 'Users', url: '/users', type: 'page' },
        { title: 'Settings', url: '/settings', type: 'page' },
        { title: 'Analytics', url: '/analytics', type: 'page' },
        { title: 'Security', url: '/security', type: 'page' },
        { title: 'Help', url: '/help', type: 'page' },
      ].filter(item => item.title.toLowerCase().includes(q));
    },
  }));

  // ─── themeSwitch ─────────────────────────────────────────────────────────────
  Alpine.data('themeSwitch', () => ({
    currentTheme: 'light',
    init() {
      this.currentTheme = localStorage.getItem('theme') || 'light';
      this.applyTheme();
    },
    toggle() {
      this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
      this.applyTheme();
      localStorage.setItem('theme', this.currentTheme);
    },
    applyTheme() {
      document.documentElement.setAttribute('data-bs-theme', this.currentTheme);
    },
  }));
});
