import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
  const { headers, ...otherOptions } = options;
  const res = await fetch(url, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
      ...(headers || {}),
    },
    ...otherOptions,
  });

  const text = await res.text();
  const data = text ? JSON.parse(text) : {};

  if (!res.ok) {
    const validation = data?.errors ? Object.values(data.errors).flat().join(' ') : '';
    throw new Error(validation || data?.message || data?.error || 'Request failed');
  }

  return data;
}

function getModal(selector) {
  const element = document.querySelector(selector);
  return element ? Modal.getOrCreateInstance(element) : null;
}

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const iconMap = {
    success: 'bi-check-circle-fill',
    danger:  'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info:    'bi-info-circle-fill',
  };

  const el = document.createElement('div');
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

function csvEscape(value) {
  return `"${String(value ?? '').replace(/"/g, '""')}"`;
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

function endpointFor(type) {
  return type === 'roles' ? '/api/roles' : '/api/permissions';
}

const permissionGroupMeta = {
  user:       { label: 'Users',       icon: 'people',       order: 10 },
  role:       { label: 'Roles',       icon: 'shield-lock',  order: 20 },
  permission: { label: 'Permissions', icon: 'key',          order: 30 },
  audit:      { label: 'Audit',       icon: 'journal-text', order: 40 },
};

function permissionGroupFor(name) {
  const prefix = String(name ?? '').split('-')[0] || 'other';
  const meta = permissionGroupMeta[prefix] ?? { label: 'Other', icon: 'grid', order: 999 };

  return { key: prefix, ...meta };
}

function permissionActionLabel(name) {
  const parts = String(name ?? '').split('-');
  if (parts.length <= 1) return String(name ?? '');

  return parts.slice(1)
    .map(part => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
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
        });
      }
      groups.get(group.key).items.push({
        ...permission,
        actionLabel: permissionActionLabel(permission.name),
      });
    });

  return [...groups.values()].sort((a, b) => a.order - b.order || a.label.localeCompare(b.label));
}

document.addEventListener('alpine:init', () => {
  Alpine.data('rolesPermissionsTable', () => ({
    activeTab: 'roles',
    roles: [],
    permissions: [],
    selectedItems: [],
    searchQuery: '',
    statusFilter: '',
    guardFilter: '',
    sortField: 'name',
    sortDirection: 'asc',
    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
    itemsPerPage: 10,
    isLoading: false,
    activityPeriod: 7,
    charts: {},
    _resizeHandler: null,
    _themeObserver: null,

    async init() {
      await Promise.all([this.loadRoles(), this.loadPermissions()]);
      this.$nextTick(() => {
        this.initCharts();
        this.initResizeHandler();
      });
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
      this.clearExistingCharts();
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

    clearExistingCharts() {
      Object.values(this.charts).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') chart.destroy();
      });
      this.charts = {};
      document.querySelector('#accessCoverageChart')?.removeAttribute('data-chart-initialized');
      document.querySelector('#guardDistributionChart')?.removeAttribute('data-chart-initialized');
    },

    params(overrides = {}) {
      const params = new URLSearchParams({
        page: overrides.page ?? this.currentPage,
        per_page: overrides.per_page ?? this.itemsPerPage,
        sort_by: this.sortField,
        sort_dir: this.sortDirection,
      });
      if (this.searchQuery) params.set('search', this.searchQuery);
      if (this.guardFilter) params.set('guard_name', this.guardFilter);
      if (!this.statusFilter) params.set('deleted', 'with');
      if (this.statusFilter === 'deleted') params.set('deleted', 'only');
      if (this.statusFilter === 'active') params.set('deleted', 'without');
      return params;
    },

    async loadRoles() {
      if (this.activeTab === 'roles') this.isLoading = true;
      try {
        const data = await apiFetch(`/api/roles?${this.params()}`);
        this.roles = (data.data ?? []).map(item => this.mapItem(item));
        if (this.activeTab === 'roles') this.applyPagination(data);
      } catch (err) {
        showToast(`Failed to load roles: ${err.message}`, 'danger');
      } finally {
        this.isLoading = false;
      }
    },

    async loadPermissions() {
      if (this.activeTab === 'permissions') this.isLoading = true;
      try {
        const data = await apiFetch(`/api/permissions?${this.params()}`);
        this.permissions = (data.data ?? []).map(item => this.mapItem(item));
        if (this.activeTab === 'permissions') this.applyPagination(data);
      } catch (err) {
        showToast(`Failed to load permissions: ${err.message}`, 'danger');
      } finally {
        this.isLoading = false;
      }
    },

    async loadCurrent() {
      await (this.activeTab === 'roles' ? this.loadRoles() : this.loadPermissions());
      this.$nextTick(() => {
        this.clearExistingCharts();
        this.initCharts();
      });
    },

    applyPagination(data) {
      this.totalItems = data.total ?? this.currentItems.length;
      this.totalPages = data.last_page ?? 1;
      this.currentPage = data.current_page ?? 1;
    },

    mapItem(item) {
      return {
        ...item,
        isDeleted: Boolean(item.deleted_at),
        updatedAt: formatDate(item.updated_at),
        updatedAtDateTime: formatDateTime(item.updated_at),
        createdAt: formatDate(item.created_at),
        createdAtDateTime: formatDateTime(item.created_at),
        permissions: item.permissions ?? [],
        roles: item.roles ?? [],
        permissions_count: item.permissions_count ?? item.permissions?.length ?? 0,
        roles_count: item.roles_count ?? item.roles?.length ?? 0,
        permissionGroups: groupPermissions(item.permissions ?? []),
        permissionGroup: permissionGroupFor(item.name),
        permissionActionLabel: permissionActionLabel(item.name),
      };
    },

    switchTab(tab) {
      this.activeTab = tab;
      this.currentPage = 1;
      this.selectedItems = [];
      this.loadCurrent();
    },

    filterItems() {
      this.currentPage = 1;
      this.selectedItems = [];
      this.loadCurrent();
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.loadCurrent();
    },

    get currentItems() {
      return this.activeTab === 'roles' ? this.roles : this.permissions;
    },

    get selectedRows() {
      return this.currentItems.filter(item => this.selectedItems.includes(item.id));
    },

    get hasSelectedDeletedItems() {
      return this.selectedRows.some(item => item.isDeleted);
    },

    get hasSelectedActiveItems() {
      return this.selectedRows.some(item => !item.isDeleted);
    },

    get pageFrom() {
      if (this.totalItems === 0) return 0;
      return (this.currentPage - 1) * this.itemsPerPage + 1;
    },

    get pageTo() {
      return Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
    },

    get visiblePages() {
      const delta = 2;
      const range = [];
      for (let i = Math.max(2, this.currentPage - delta); i <= Math.min(this.totalPages - 1, this.currentPage + delta); i++) {
        range.push(i);
      }
      const result = [];
      if (this.currentPage - delta > 2) result.push(1, '...');
      else result.push(1);
      result.push(...range);
      if (this.currentPage + delta < this.totalPages - 1) result.push('...', this.totalPages);
      else if (this.totalPages > 1) result.push(this.totalPages);
      return result.filter((value, index, all) => all.indexOf(value) === index && (typeof value === 'string' || value <= this.totalPages));
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadCurrent();
      }
    },

    toggleAll(checked) {
      this.selectedItems = checked ? this.currentItems.map(item => item.id) : [];
    },

    toggleItem(itemId) {
      if (this.selectedItems.includes(itemId)) {
        this.selectedItems = this.selectedItems.filter(id => id !== itemId);
      } else {
        this.selectedItems = [...this.selectedItems, itemId];
      }
    },

    openCreate() {
      const form = Alpine.$data(document.querySelector('[x-data="accessForm"]'));
      if (!form) return;
      form.resetForm(this.activeTab);
      getModal('#accessModal')?.show();
    },

    editItem(item) {
      if (item.isDeleted) {
        showToast('Restore this item before editing.', 'warning');
        return;
      }
      const form = Alpine.$data(document.querySelector('[x-data="accessForm"]'));
      if (!form) return;
      form.editItem(this.activeTab, item);
      getModal('#accessModal')?.show();
    },

    async viewItem(item) {
      const profile = Alpine.$data(document.querySelector('[x-data="accessProfile"]'));
      if (!profile) return;
      profile.loading = true;
      profile.type = this.activeTab;
      profile.item = item;
      getModal('#accessViewModal')?.show();
      try {
        const data = await apiFetch(`${endpointFor(this.activeTab)}/${item.id}`);
        profile.item = this.mapItem(data.data ?? data);
      } catch (err) {
        showToast(`Could not load details: ${err.message}`, 'warning');
      } finally {
        profile.loading = false;
      }
    },

    async deleteItem(item) {
      const confirmed = await confirmDelete({
        title: `Temporarily delete ${this.singularLabel}?`,
        text: `Do you want to move ${item.name} to deleted ${this.activeTab}? You can restore it later.`,
        confirmButtonText: 'Yes, delete it',
      });
      if (!confirmed) return;

      try {
        const res = await apiFetch(`${endpointFor(this.activeTab)}/${item.id}`, { method: 'DELETE' });
        showToast(res.message || `${item.name} deleted successfully.`);
        await this.loadCurrent();
      } catch (err) {
        showToast(`Failed to delete ${item.name}: ${err.message}`, 'danger');
      }
    },

    async restoreItem(item) {
      try {
        const res = await apiFetch(`${endpointFor(this.activeTab)}/${item.id}/restore`, { method: 'PATCH' });
        showToast(res.message || `${item.name} restored successfully.`);
        await this.loadCurrent();
      } catch (err) {
        showToast(`Failed to restore ${item.name}: ${err.message}`, 'danger');
      }
    },

    async forceDeleteItem(item) {
      const confirmed = await confirmDelete({
        title: `Permanently delete ${this.singularLabel}?`,
        text: `This will permanently delete ${item.name}. This action cannot be undone.`,
        confirmButtonText: 'Yes, permanently delete',
      });
      if (!confirmed) return;

      try {
        const res = await apiFetch(`${endpointFor(this.activeTab)}/${item.id}/force`, { method: 'DELETE' });
        showToast(res.message || `${item.name} permanently deleted successfully.`);
        await this.loadCurrent();
      } catch (err) {
        showToast(`Failed to permanently delete ${item.name}: ${err.message}`, 'danger');
      }
    },

    async bulkAction(action) {
      if (this.selectedItems.length === 0) {
        showToast('Please select items first.', 'warning');
        return;
      }

      if (action === 'delete' || action === 'force-delete') {
        const confirmed = await confirmDelete({
          title: action === 'delete' ? 'Temporarily delete selected items?' : 'Permanently delete selected items?',
          text: action === 'delete'
            ? `Do you want to move ${this.selectedItems.length} selected item(s) to deleted records?`
            : `This will permanently delete ${this.selectedItems.length} selected item(s). This action cannot be undone.`,
          confirmButtonText: action === 'delete' ? 'Yes, delete items' : 'Yes, permanently delete',
        });
        if (!confirmed) return;
      }

      const selected = [...this.selectedRows];
      let completed = 0;
      let failed = 0;

      for (const item of selected) {
        try {
          if (action === 'delete') await apiFetch(`${endpointFor(this.activeTab)}/${item.id}`, { method: 'DELETE' });
          if (action === 'restore') await apiFetch(`${endpointFor(this.activeTab)}/${item.id}/restore`, { method: 'PATCH' });
          if (action === 'force-delete') await apiFetch(`${endpointFor(this.activeTab)}/${item.id}/force`, { method: 'DELETE' });
          completed++;
        } catch {
          failed++;
        }
      }

      this.selectedItems = [];
      await this.loadCurrent();
      showToast(`${completed} item(s) processed${failed ? `, ${failed} failed` : ''}.`, failed ? 'warning' : 'success');
    },

    async exportItems() {
      try {
        const first = await apiFetch(`${endpointFor(this.activeTab)}?${this.params({ page: 1, per_page: 100 })}`);
        const rows = (first.data ?? []).map(item => this.mapItem(item));
        for (let page = 2; page <= (first.last_page ?? 1); page++) {
          const data = await apiFetch(`${endpointFor(this.activeTab)}?${this.params({ page, per_page: 100 })}`);
          rows.push(...(data.data ?? []).map(item => this.mapItem(item)));
        }

        const headers = this.activeTab === 'roles'
          ? ['ID', 'Name', 'Guard', 'Permissions', 'Status', 'Created', 'Updated']
          : ['ID', 'Name', 'Guard', 'Roles', 'Status', 'Created', 'Updated'];
        const csvRows = rows.map(item => [
          item.id,
          item.name,
          item.guard_name,
          this.activeTab === 'roles' ? item.permissions.map(p => p.name).join('|') : item.roles.map(r => r.name).join('|'),
          item.isDeleted ? 'deleted' : 'active',
          item.createdAtDateTime,
          item.updatedAtDateTime,
        ]);
        const csv = [headers, ...csvRows].map(row => row.map(csvEscape).join(',')).join('\n');
        downloadBlob(`${this.activeTab}-export.csv`, csv, 'text/csv;charset=utf-8;');
        showToast(`Exported ${rows.length} ${this.activeTab}.`);
      } catch (err) {
        showToast(`Export failed: ${err.message}`, 'danger');
      }
    },

    get singularLabel() {
      return this.activeTab === 'roles' ? 'role' : 'permission';
    },

    get roleStats() {
      return {
        total: this.activeTab === 'roles' ? this.totalItems : this.roles.length,
      };
    },

    get permissionStats() {
      return {
        total: this.activeTab === 'permissions' ? this.totalItems : this.permissions.length,
      };
    },

    get assignmentCount() {
      return this.roles.reduce((total, role) => total + Number(role.permissions_count || 0), 0);
    },

    get deletedCount() {
      return this.currentItems.filter(item => item.isDeleted).length;
    },

    get topRoles() {
      return [...this.roles]
        .sort((a, b) => Number(b.permissions_count || 0) - Number(a.permissions_count || 0))
        .slice(0, 5);
    },

    get recentActivities() {
      return this.currentItems.slice(0, 5).map((item, index) => ({
        id: `${this.activeTab}-${item.id}-${index}`,
        name: item.name,
        action: item.isDeleted ? 'was deleted' : 'was updated',
        time: `Date & time: ${item.updatedAtDateTime}`,
        type: item.isDeleted ? 'deleted' : 'updated',
        icon: item.isDeleted ? 'trash' : (this.activeTab === 'roles' ? 'shield-check' : 'key'),
        details: `${item.guard_name} guard · ${this.activeTab === 'roles' ? `${item.permissions_count || 0} permissions` : `${item.roles_count || 0} roles`}`,
      }));
    },

    get systemAlerts() {
      const alerts = [];
      if (this.roles.length === 0) {
        alerts.push({ id: 1, title: 'No Roles', message: 'No roles found. Add your first role.', type: 'info', time: 'Just now' });
      }
      if (this.permissions.length === 0) {
        alerts.push({ id: 2, title: 'No Permissions', message: 'No permissions found. Add your first permission.', type: 'warning', time: 'Just now' });
      }
      return alerts;
    },

    setActivityPeriod(days) {
      this.activityPeriod = Number(days);
      this.$nextTick(() => {
        this.clearExistingCharts();
        this.initCharts();
      });
    },

    initCharts() {
      this.initCoverageChart();
      this.initGuardChart();
    },

    initCoverageChart() {
      const el = document.querySelector('#accessCoverageChart');
      if (!el || el.hasAttribute('data-chart-initialized')) return;
      el.setAttribute('data-chart-initialized', 'true');

      const period = Number(this.activityPeriod) || 7;
      const labels = [];
      const roleCounts = [];
      const permissionCounts = [];
      const now = new Date();
      for (let i = period - 1; i >= 0; i--) {
        const date = new Date(now);
        date.setDate(date.getDate() - i);
        labels.push(date.toLocaleDateString('en', period <= 7 ? { weekday: 'short' } : { month: 'short', day: 'numeric' }));
        roleCounts.push(this.roles.filter(role => new Date(role.created_at).toDateString() === date.toDateString()).length);
        permissionCounts.push(this.permissions.filter(permission => new Date(permission.created_at).toDateString() === date.toDateString()).length);
      }

      this.charts.coverage = new ApexCharts(el, {
        series: [
          { name: 'Roles', data: roleCounts },
          { name: 'Permissions', data: permissionCounts },
        ],
        chart: { type: 'bar', height: 250, width: '100%', toolbar: { show: false }, zoom: { enabled: false } },
        colors: ['#6366f1', '#10b981'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        xaxis: { categories: labels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { fontSize: '12px', colors: '#64748b' } } },
        yaxis: { show: false },
        grid: { show: false },
        dataLabels: { enabled: false },
        tooltip: { theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light' },
      });
      this.charts.coverage.render();
    },

    initGuardChart() {
      const el = document.querySelector('#guardDistributionChart');
      if (!el || el.hasAttribute('data-chart-initialized')) return;
      el.setAttribute('data-chart-initialized', 'true');

      const counts = [...this.roles, ...this.permissions].reduce((acc, item) => {
        const guard = item.guard_name || 'web';
        acc[guard] = (acc[guard] || 0) + 1;
        return acc;
      }, {});
      if (Object.keys(counts).length === 0) return;

      this.charts.guards = new ApexCharts(el, {
        series: Object.values(counts),
        chart: { type: 'donut', height: 140 },
        labels: Object.keys(counts),
        colors: ['#6366f1', '#10b981', '#f59e0b', '#ef4444'],
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '70%' } } },
        dataLabels: { enabled: false },
        tooltip: { theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light' },
      });
      this.charts.guards.render();
    },
  }));

  Alpine.data('accessForm', () => ({
    type: 'roles',
    editingId: null,
    saving: false,
    permissionsLoading: false,
    permissionsError: '',
    permissionSearch: '',
    permissions: [],
    form: {
      name: '',
      guard_name: 'web',
      permissions: [],
    },

    get title() {
      if (this.editingId) return this.type === 'roles' ? 'Edit Role' : 'Edit Permission';
      return this.type === 'roles' ? 'Add Role' : 'Add Permission';
    },

    get groupedPermissions() {
      const query = this.permissionSearch.trim().toLowerCase();
      if (!query) return groupPermissions(this.permissions);

      return groupPermissions(this.permissions.filter(permission => {
        const name = String(permission.name ?? '').toLowerCase();
        const label = String(permission.actionLabel ?? '').toLowerCase();
        const group = permissionGroupFor(permission.name).label.toLowerCase();
        return name.includes(query) || label.includes(query) || group.includes(query);
      }));
    },

    get totalPermissionsCount() {
      return this.permissions.length;
    },

    get selectedGroups() {
      return groupPermissions(this.permissions.filter(permission => this.isPermissionSelected(permission.name)));
    },

    async init() {
      await this.loadPermissions();
      document.getElementById('accessModal')?.addEventListener('hidden.bs.modal', () => this.resetForm(this.type));
    },

    async loadPermissions() {
      this.permissionsLoading = true;
      this.permissionsError = '';
      try {
        const data = await apiFetch('/api/permissions/options');
        this.permissions = (data.data ?? data).map(permission => ({
          ...permission,
          actionLabel: permissionActionLabel(permission.name),
        }));
      } catch (err) {
        try {
          const data = await apiFetch('/api/permissions?per_page=100&deleted=without&sort_by=name&sort_dir=asc');
          this.permissions = (data.data ?? data).map(permission => ({
            ...permission,
            actionLabel: permissionActionLabel(permission.name),
          }));
        } catch (fallbackErr) {
          this.permissions = [];
          this.permissionsError = fallbackErr.message || err.message;
        }
      } finally {
        this.permissionsLoading = false;
      }
    },

    resetForm(type = 'roles') {
      this.type = type;
      this.editingId = null;
      this.saving = false;
      this.form = {
        name: '',
        guard_name: 'web',
        permissions: [],
      };
      this.permissionSearch = '';
      if (type === 'roles') this.loadPermissions();
    },

    editItem(type, item) {
      this.resetForm(type);
      this.editingId = item.id;
      this.form.name = item.name;
      this.form.guard_name = item.guard_name || 'web';
      this.form.permissions = (item.permissions ?? []).map(permission => permission.name);
    },

    isPermissionSelected(permissionName) {
      return this.form.permissions.includes(permissionName);
    },

    isPermissionGroupSelected(group) {
      return group.items.length > 0 && group.items.every(permission => this.isPermissionSelected(permission.name));
    },

    selectedPermissionCount(group) {
      return group.items.filter(permission => this.isPermissionSelected(permission.name)).length;
    },

    togglePermissionGroup(group) {
      const groupNames = group.items.map(permission => permission.name);
      if (this.isPermissionGroupSelected(group)) {
        this.form.permissions = this.form.permissions.filter(permission => !groupNames.includes(permission));
        return;
      }

      this.form.permissions = [...new Set([...this.form.permissions, ...groupNames])];
    },

    clearPermissions() {
      this.form.permissions = [];
    },

    async saveItem() {
      if (!this.form.name) {
        showToast('Name is required.', 'warning');
        return;
      }

      this.saving = true;
      try {
        const payload = {
          name: this.form.name,
          guard_name: this.form.guard_name || 'web',
        };
        if (this.type === 'roles') payload.permissions = this.form.permissions;

        const url = this.editingId ? `${endpointFor(this.type)}/${this.editingId}` : endpointFor(this.type);
        const method = this.editingId ? 'PUT' : 'POST';
        const res = await apiFetch(url, { method, body: JSON.stringify(payload) });
        showToast(res.message || 'Saved successfully.');
        getModal('#accessModal')?.hide();
        const table = Alpine.$data(document.querySelector('[x-data="rolesPermissionsTable"]'));
        if (table) {
          await Promise.all([table.loadRoles(), table.loadPermissions()]);
          table.$nextTick(() => {
            table.clearExistingCharts();
            table.initCharts();
          });
        }
        await this.loadPermissions();
      } catch (err) {
        showToast(`Save failed: ${err.message}`, 'danger');
      } finally {
        this.saving = false;
      }
    },
  }));

  Alpine.data('accessProfile', () => ({
    type: 'roles',
    item: null,
    loading: false,

    get relatedItems() {
      if (!this.item) return [];
      return this.type === 'roles' ? (this.item.permissions ?? []) : (this.item.roles ?? []);
    },

    get groupedRelatedItems() {
      if (!this.item || this.type !== 'roles') return [];
      return groupPermissions(this.item.permissions ?? []);
    },

    editFromProfile() {
      if (!this.item) return;
      getModal('#accessViewModal')?.hide();
      this.$nextTick(() => {
        const table = Alpine.$data(document.querySelector('[x-data="rolesPermissionsTable"]'));
        if (table) {
          table.activeTab = this.type;
          table.editItem(this.item);
        }
      });
    },
  }));

  Alpine.data('accessImportForm', () => ({
    file: null,
    importing: false,
    result: null,

    handleFile(event) {
      this.file = event.target.files?.[0] ?? null;
      this.result = null;
    },

    async importItems() {
      if (!this.file) {
        showToast('Please select a CSV file.', 'warning');
        return;
      }

      this.importing = true;
      this.result = null;
      const text = await this.file.text();
      const lines = text.trim().split('\n').filter(line => line.trim());
      let created = 0;
      const errors = [];

      for (let i = 1; i < lines.length; i++) {
        const [typeRaw, name, guardName, permissionsRaw] = parseCsvLine(lines[i]);
        const type = String(typeRaw ?? '').toLowerCase() === 'permission' ? 'permissions' : 'roles';
        if (!name) {
          errors.push(`Row ${i + 1}: missing name`);
          continue;
        }

        const payload = {
          name,
          guard_name: guardName || 'web',
        };
        if (type === 'roles') {
          payload.permissions = String(permissionsRaw ?? '').split('|').map(value => value.trim()).filter(Boolean);
        }

        try {
          await apiFetch(endpointFor(type), { method: 'POST', body: JSON.stringify(payload) });
          created++;
        } catch (err) {
          errors.push(`Row ${i + 1} (${name}): ${err.message}`);
        }
      }

      this.importing = false;
      this.result = { created, errors };
      if (created > 0) {
        showToast(`Imported ${created} record(s) successfully.`);
        const table = Alpine.$data(document.querySelector('[x-data="rolesPermissionsTable"]'));
        if (table) await Promise.all([table.loadRoles(), table.loadPermissions()]);
      }
    },
  }));
});
