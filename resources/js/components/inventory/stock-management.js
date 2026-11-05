import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const id = 'toast-' + Date.now();
  const iconMap = {
    success: 'bi-check-circle-fill',
    danger: 'bi-x-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    error: 'bi-x-circle-fill',
  };
  const el = document.createElement('div');
  el.id = id;
  el.className = `toast align-items-center text-bg-${type === 'error' || type === 'danger' ? 'danger' : type} border-0 show mb-2`;
  el.setAttribute('role', 'alert');
  el.innerHTML = `<div class="d-flex"><div class="toast-body"><i class="bi ${iconMap[type] ?? 'bi-info-circle-fill'} me-2"></i><span></span></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  el.querySelector('.toast-body span').textContent = message;
  container.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

export default () => ({
  items: [],
  stats: { total_products: 0, total_warehouses: 0, low_stock_count: 0, out_of_stock: 0 },
  warehouses: [],
  isLoading: false,
  exporting: false,
  error: '',
  dataRequestController: null,
  searchQuery: new URLSearchParams(window.location.search).get('search') || '',
  warehouseFilter: new URLSearchParams(window.location.search).get('warehouse_id') ? parseInt(new URLSearchParams(window.location.search).get('warehouse_id')) : '',
  stockLevelFilter: new URLSearchParams(window.location.search).get('stock_level') || 'in_stock',
  sortField: new URLSearchParams(window.location.search).get('sort_by') || 'id',
  sortDirection: new URLSearchParams(window.location.search).get('sort_dir') || 'desc',
  currentPage: parseInt(new URLSearchParams(window.location.search).get('page')) || 1,
  itemsPerPage: parseInt(new URLSearchParams(window.location.search).get('per_page')) || 25,
  totalItems: 0,
  totalPages: 1,

  // Selection state
  selectedItems: [],

  async init() {
    if (await this.loadOptions()) await this.loadData();
  },

  async apiRequest(url, options = {}) {
    const { headers, ...otherOptions } = options;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const reqHeaders = {
      'X-CSRF-TOKEN': csrfToken,
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json',
      ...(headers || {}),
    };
    if (options.body && !(options.body instanceof FormData))
      reqHeaders['Content-Type'] = 'application/json';
    const response = await fetch(url, { headers: reqHeaders, ...otherOptions });
    const text = await response.text();
    let payload;
    try { payload = text ? JSON.parse(text) : {}; }
    catch { throw new Error(response.status === 401 || response.status === 419 ? 'Your session expired. Please sign in again.' : 'The server could not complete this request. Please retry.'); }
    if (!response.ok) {
      const msg = payload?.errors
        ? Object.values(payload.errors).flat().join(' ')
        : payload?.message || 'Request failed.';
      throw new Error(msg);
    }
    return payload;
  },

  async loadOptions() {
    try {
      const urlParam = new URLSearchParams(window.location.search).get('warehouse_id');
      const initialWarehouse = urlParam ? parseInt(urlParam) : '';
      const data = await this.apiRequest('/api/inventory/stocks/warehouse-options');
      this.warehouses = data.data || [];

      // Wait for DOM to render options
      await new Promise(resolve => this.$nextTick(resolve));

      if (initialWarehouse !== '') {
        this.warehouseFilter = initialWarehouse;
      } else if (this.warehouseFilter === '') {
        const defaultWh = this.warehouses.find((w) => w.is_default);
        if (defaultWh) {
          this.warehouseFilter = defaultWh.id;
        }
      }
      return true;
    } catch (e) {
      this.error = 'Warehouse options could not be loaded: ' + e.message;
      return false;
    }
  },

  async loadData() {
    this.dataRequestController?.abort();
    const requestController = new AbortController();
    this.dataRequestController = requestController;
    this.isLoading = true;
    this.error = '';
    try {
      const params = new URLSearchParams({
        per_page: this.itemsPerPage,
        page: this.currentPage,
        sort_by: this.sortField,
        sort_dir: this.sortDirection,
      });
      if (this.searchQuery) params.set('search', this.searchQuery);
      if (this.warehouseFilter) params.set('warehouse_id', this.warehouseFilter);
      if (this.stockLevelFilter) params.set('stock_level', this.stockLevelFilter);
      
      const currentUrl = new URL(window.location.href);
      currentUrl.search = params.toString();
      window.history.pushState({}, '', currentUrl);

      const data = await this.apiRequest(`/api/inventory/stocks?${params}`, { signal: requestController.signal });
      if (this.dataRequestController !== requestController) return;
      const lastPage = data.meta?.last_page || 1;
      if (this.currentPage > lastPage) { this.currentPage = lastPage; return await this.loadData(); }
      this.items = data.data || [];
      this.stats = data.stats || this.stats;
      this.totalItems = data.meta?.total || 0;
      this.totalPages = data.meta?.last_page || 1;

      // Clear selection on page load
      this.selectedItems = [];
    } catch (e) {
      if (e.name !== 'AbortError' && this.dataRequestController === requestController) { this.error = e.message; showToast(e.message, 'error'); }
    } finally {
      if (this.dataRequestController === requestController) {
        this.isLoading = false;
        this.dataRequestController = null;
      }
    }
  },

  // Selection helper methods
  toggleAll(checked) {
    if (checked) {
      this.items.forEach((item) => {
        if (!this.selectedItems.includes(item.id)) {
          this.selectedItems.push(item.id);
        }
      });
    } else {
      const currentIds = this.items.map((item) => item.id);
      this.selectedItems = this.selectedItems.filter((id) => !currentIds.includes(id));
    }
  },

  toggleItem(itemId) {
    if (this.selectedItems.includes(itemId)) {
      this.selectedItems = this.selectedItems.filter((id) => id !== itemId);
    } else {
      this.selectedItems = [...this.selectedItems, itemId];
    }
  },

  get paginatedItems() {
    return this.items;
  },

  getPhysicalAvailable(item) { return Number(item.physical_available ?? Math.max(0, Number(item.quantity || 0) - Number(item.reserved_qty || 0))); },
  getAlertLevel(item) { return Number(item.min_stock_level ?? item.product?.min_stock_level ?? 5); },
  getAvailableForSell(item) {
    if (item.available_for_sell != null) return Number(item.available_for_sell);
    const raw = Number(item.quantity || 0) - Number(item.reserved_qty || 0) - Number(item.pending_qty || 0);
    const enabled = item.allow_overselling ?? item.product?.allow_overselling ?? false;
    const extra = enabled ? Number(item.overselling_qty ?? item.product?.overselling_qty ?? 999) : 0;
    return Math.max(0, raw + extra);
  },
  stockBadgeClass(item) {
    const available = this.getAvailableForSell(item);
    return available <= 0 ? 'out-of-stock' : available <= this.getAlertLevel(item) ? 'low-stock' : 'in-stock';
  },
  destroy() { this.dataRequestController?.abort(); },

  onSearch() {
    this.currentPage = 1;
    this.loadData();
  },

  sortBy(field) {
    if (this.sortField === field) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortField = field;
      this.sortDirection = 'asc';
    }
    this.currentPage = 1;
    this.loadData();
  },

  get pageFrom() {
    return this.totalItems === 0 ? 0 : (this.currentPage - 1) * this.itemsPerPage + 1;
  },
  get pageTo() {
    return Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
  },

  // Standardized visiblePages getter
  get visiblePages() {
    const delta = 2;
    const range = [];
    for (
      let i = Math.max(2, this.currentPage - delta);
      i <= Math.min(this.totalPages - 1, this.currentPage + delta);
      i++
    ) {
      range.push(i);
    }
    const result = [];
    if (this.currentPage - delta > 2) result.push(1, '...');
    else result.push(1);
    result.push(...range);
    if (this.currentPage + delta < this.totalPages - 1) result.push('...', this.totalPages);
    else if (this.totalPages > 1) result.push(this.totalPages);
    return result.filter(
      (v, i, a) => a.indexOf(v) === i && (typeof v === 'string' || v <= this.totalPages)
    );
  },

  goToPage(page) {
    if (page >= 1 && page <= this.totalPages) {
      this.currentPage = page;
      this.loadData();
    }
  },

  // Export every filtered row server-side in bounded chunks.
  async exportStock(selectedOnly = false) {
    if (this.exporting) return;
    if (selectedOnly && !this.selectedItems.length) { showToast('No items selected for export.', 'warning'); return; }
    this.exporting = true;
    try {
      const params = new URLSearchParams({sort_by:this.sortField,sort_dir:this.sortDirection});
      if (this.searchQuery) params.set('search',this.searchQuery);
      if (this.warehouseFilter) params.set('warehouse_id',this.warehouseFilter);
      if (this.stockLevelFilter) params.set('stock_level',this.stockLevelFilter);
      if (selectedOnly) this.selectedItems.forEach(id => params.append('ids[]',id));
      const response = await fetch('/api/inventory/stocks/export?' + params, {headers:{Accept:'text/csv, application/json','X-Requested-With':'XMLHttpRequest'}});
      if (!response.ok) {
        const payload = await response.json().catch(() => ({}));
        throw new Error(payload.errors ? Object.values(payload.errors).flat().join(' ') : payload.message || 'Export failed.');
      }
      const url = URL.createObjectURL(await response.blob());
      const link = document.createElement('a'); link.href = url; link.download = 'stock-levels-export.csv';
      document.body.appendChild(link); link.click(); URL.revokeObjectURL(url); link.remove();
      showToast('Stock export downloaded successfully.');
    } catch (e) { showToast('Export failed: ' + e.message,'danger'); }
    finally { this.exporting = false; }
  },
});
