import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';
import { createSearchComponent } from '../utils/search-component.js';

function getModal(elementOrSelector) {
  const element = typeof elementOrSelector === 'string'
    ? document.querySelector(elementOrSelector)
    : elementOrSelector;

  return element ? Modal.getOrCreateInstance(element) : null;
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
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

function escapeCsv(value) {
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

function normalizeStatus(status) {
  const value = String(status ?? '').trim().toLowerCase();
  if (['published', 'publish'].includes(value)) return 'published';
  if (value === 'active') return 'active';
  if (['draft', 'unpublished'].includes(value)) return 'draft';
  if (['pending', 'review', 'pending review'].includes(value)) return 'pending';
  if (value === 'out_of_stock') return 'out_of_stock';
  return 'draft';
}

function normalizeCategory(category) {
  const value = String(category ?? '').trim().toLowerCase();
  if (value.includes('electronic')) return 'electronics';
  if (value.includes('cloth')) return 'clothing';
  if (value.includes('book')) return 'books';
  if (value.includes('home') || value.includes('garden')) return 'home';
  return value || 'home';
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString() : 'N/A';
}


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
    if (res.status === 403 || (typeof message === 'string' && (message.toLowerCase().includes("authoriz") || message.toLowerCase().includes("forbidden")))) { window.location.href = "/"; return; }
    throw new Error(message);
  }

  return data;
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

document.addEventListener('alpine:init', () => {
  Alpine.data('productTable', () => ({
    products: [],
    filteredProducts: [],
    selectedProducts: [],
    editingProductId: null,
    previewProduct: null,
    importing: false,
    importMode: 'overwrite',
    importErrors: [],
    options: {
      categories: [],
      brands: [],
      uoms: [],
      taxRates: [],
      hsnCodes: [],
      warehouses: [],
      attributes: [],
      statusList: [],
    },
    currentPage: 1,
    itemsPerPage: 10,
    searchQuery: '',
    categoryFilter: '',
    stockFilter: '',
    sortField: 'name',
    sortDirection: 'asc',
    isLoading: false,
    apiBase: '/api/products',
    charts: {},
    _resizeHandler: null,
    _themeObserver: null,
    chartsInitialized: false,

    // Statistics
    stats: {
      total: 0,
      active: 0,
      inStock: 0,
      lowStock: 0,
      outOfStock: 0,
      totalValue: 0
    },

    categoryStats: [],

    async init() {
      Alpine.store('productTable', this);
      await this.loadProductsFromApi();
      this.filterProducts();
      this.calculateStats();
      
      // Delay chart initialization to ensure DOM is fully ready
      setTimeout(() => {
        this.initCharts();
        this.initResizeHandler();
      }, 500);

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

      const onHide = () => this.destroy();
      window.addEventListener('pagehide', onHide, { once: true });
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

    clearExistingCharts() {
      Object.values(this.charts).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') {
          chart.destroy();
        }
      });
      this.charts = {};
      this.chartsInitialized = false;
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

    get nextProductId() {
      return this.products.length ? Math.max(...this.products.map(product => Number(product.id) || 0)) + 1 : 1;
    },

    get pageFrom() {
      if (this.filteredProducts.length === 0) return 0;
      return (this.currentPage - 1) * this.itemsPerPage + 1;
    },

    get pageTo() {
      return Math.min(this.currentPage * this.itemsPerPage, this.filteredProducts.length);
    },

    async loadProductsFromApi() {
      this.isLoading = true;

      try {
        const payload = await apiFetch(this.apiBase);
        this.products = Array.isArray(payload.data) ? payload.data : [];

        if (payload.stats) {
          this.stats = payload.stats;
        }

        if (payload.options) {
          this.options = {
            ...this.options,
            ...payload.options,
          };
          try {
            const form = this._getProductForm();
            if (form && !form.editingProductId && !form.form.default_warehouse_id && this.options.warehouses && this.options.warehouses.length > 0) {
              form.form.default_warehouse_id = String(this.options.warehouses[0].id);
            }
          } catch (e) { /* ignore */ }
        }
      } catch (error) {
        console.error('Failed to load products from API:', error);
        this.loadSampleData();
        showToast('Loaded fallback product samples.', 'warning');
      } finally {
        this.isLoading = false;
      }
    },

    loadSampleData() {
      this.products = [];
    },

    calculateStats() {
      this.stats.total = this.products.length;
      this.stats.active = this.products.filter(p => ['published', 'active'].includes(String(p.status || '').toLowerCase())).length;
      this.stats.inStock = this.products.filter(p => p.stock > (p.min_stock_level || 10)).length;
      this.stats.lowStock = this.products.filter(p => p.stock > 0 && p.stock <= (p.min_stock_level || 10)).length;
      this.stats.outOfStock = this.products.filter(p => p.stock <= 0).length;
      this.stats.totalValue = this.products.reduce((sum, p) => sum + (p.price * p.stock), 0);

      // Calculate category distribution
      const categories = {};
      this.products.forEach(product => {
        const key = product.category || product.category_label || 'uncategorized';
        categories[key] = (categories[key] || 0) + 1;
      });

      const total = this.products.length || 1;
      this.categoryStats = Object.entries(categories).map(([name, count]) => ({
        name: name.charAt(0).toUpperCase() + name.slice(1),
        count,
        percentage: Math.round((count / total) * 100),
        color: this.getCategoryColor(name)
      }));

      this.updateCategoryChart();
    },

    updateCategoryChart() {
      if (this.charts.category && typeof this.charts.category.updateSeries === 'function') {
        this.charts.category.updateSeries(this.categoryStats.map(cat => cat.count));
        this.charts.category.updateOptions({
          labels: this.categoryStats.map(cat => cat.name),
          colors: this.categoryStats.map(cat => cat.color),
        });
      }
    },

    getCategoryColor(category) {
      const colors = {
        electronics: '#6366f1',
        clothing: '#8b5cf6',
        books: '#06b6d4',
        home: '#10b981',
        uncategorized: '#6b7280'
      };
      return colors[category] || '#6b7280';
    },

    filterProducts() {
      this.filteredProducts = this.products.filter(product => {
        const matchesSearch = !this.searchQuery || 
          product.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
          product.sku.toLowerCase().includes(this.searchQuery.toLowerCase());
        
        const matchesCategory = !this.categoryFilter || product.category === this.categoryFilter;
        
        const matchesStock = !this.stockFilter || 
          (this.stockFilter === 'in-stock' && product.stock > (product.min_stock_level || 10)) ||
          (this.stockFilter === 'low-stock' && product.stock > 0 && product.stock <= (product.min_stock_level || 10)) ||
          (this.stockFilter === 'out-of-stock' && product.stock === 0);

        return matchesSearch && matchesCategory && matchesStock;
      });

      this.sortProducts();
      this.currentPage = 1;
    },

    resetFilters() {
      this.searchQuery = '';
      this.categoryFilter = '';
      this.stockFilter = '';
      this.filterProducts();
    },

    sortProducts() {
      this.filteredProducts.sort((a, b) => {
        let aVal = a[this.sortField];
        let bVal = b[this.sortField];

        if (this.sortField === 'price' || this.sortField === 'stock') {
          aVal = parseFloat(aVal);
          bVal = parseFloat(bVal);
        } else if (this.sortField === 'created') {
          aVal = new Date(aVal);
          bVal = new Date(bVal);
        } else {
          aVal = aVal.toString().toLowerCase();
          bVal = bVal.toString().toLowerCase();
        }

        if (this.sortDirection === 'asc') {
          return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;
        } else {
          return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;
        }
      });
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.filterProducts();
    },

    toggleAll(checked) {
      if (checked) {
        this.selectedProducts = this.filteredProducts.map(p => p.id);
      } else {
        this.selectedProducts = [];
      }
    },

    toggleProduct(productId) {
      if (this.selectedProducts.includes(productId)) {
        this.selectedProducts = this.selectedProducts.filter(id => id !== productId);
      } else {
        this.selectedProducts = [...this.selectedProducts, productId];
      }
    },

    resetProductForm() {
      const form = Alpine.$data(document.querySelector('[x-data="productForm"]'));
      if (!form) return;

      form.resetForm();

      const title = document.querySelector('#productModal .modal-title');
      if (title) title.textContent = 'Add New Product';
    },

    openCreateProduct() {
      this.resetProductForm();
      getModal('#productModal')?.show();
    },

    _mapProductForForm(product) {
      return {
        name: product.name ?? '',
        sku: product.sku ?? '',
        category_id: String(product.category_id ?? ''),
        brand_id: String(product.brand_id ?? ''),
        supplier_id: String(product.supplier_id ?? ''),
        uom_id: String(product.uom_id ?? ''),
        tax_rate_id: String(product.tax_rate_id ?? ''),
        hsn_code_id: String(product.hsn_code_id ?? ''),
        default_warehouse_id: String(product.warehouse_id ?? ''),
        barcode: product.barcode ?? '',
        weight: product.weight ?? '',
        purchase_price: String(product.purchase_price ?? ''),
        mrp: String(product.mrp ?? ''),
        selling_price_inc_gst: (() => {
          let basePrice = parseFloat(product.selling_price ?? product.price ?? 0);
          let rateId = String(product.tax_rate_id ?? '');
          let rate = 0;
          if (rateId && Alpine.store('productTable')?.options?.taxRates) {
            let taxObj = Alpine.store('productTable').options.taxRates.find(r => String(r.id) === rateId);
            if (taxObj) rate = parseFloat(taxObj.rate) || 0;
          }
          return String((basePrice + (basePrice * rate / 100)).toFixed(2));
        })(),
        selling_price: String(product.selling_price ?? product.price ?? ''),
        stock: String(product.stock_quantity ?? product.stock ?? ''),
        min_stock_level: String(product.min_stock_level ?? 0),
        overselling_qty: String(product.overselling_qty ?? 0),
        default_discount: String(product.default_discount ?? 0),
        default_discount_type: product.default_discount_type ?? 'percent',
        allow_overselling: Boolean(product.allow_overselling),
        manage_stock: product.manage_stock !== undefined ? Boolean(product.manage_stock) : true,
        batch_tracking: Boolean(product.batch_tracking),
        expiry_tracking: Boolean(product.expiry_tracking),
        is_sku_enabled: product.is_sku_enabled !== undefined ? Boolean(product.is_sku_enabled) : true,
        description: product.description ?? '',
        status: normalizeStatus(product.status),
        image: product.image ?? '/assets/images/product-placeholder.svg',
        application_instructions: product.application_instructions ?? '',
        grade: product.grade ?? '',
        attributes: Array.isArray(product.attributes) ? product.attributes.map(attribute => String(attribute.id)) : [],
      };
    },

    _findProductIndex(productId) {
      return this.products.findIndex(product => product.id === productId);
    },

    _getProductForm() {
      return Alpine.$data(document.querySelector('[x-data="productForm"]'));
    },

    handleImageUpload(event) {
      const file = event?.target?.files?.[0];
      if (!file) return;

      const form = this._getProductForm();
      if (!form) return;

      form.form.image = URL.createObjectURL(file);
      form.form.imageFile = file;
    },

    editProduct(product) {
      const form = this._getProductForm();
      if (!form) return;

      form.editingProductId = product.id;
      form.form = this._mapProductForForm(product);
      form.form.imageFile = null;

      const title = document.querySelector('#productModal .modal-title');
      if (title) title.textContent = `Edit ${product.name}`;

      getModal('#productModal')?.show();
    },

    viewProduct(product) {
      this.previewProduct = { ...product };
      getModal('#productViewModal')?.show();
    },

    bulkAction(action) {
      if (this.selectedProducts.length === 0) return;

      if (action === 'delete') {
        this.deleteProductsByIds(this.selectedProducts);
        return;
      }

      if (action === 'disable_sku') {
        apiFetch(`${this.apiBase}/bulk-disable-sku`, {
          method: 'POST',
          body: JSON.stringify({ ids: this.selectedProducts }),
        })
          .then(async () => {
            await this.loadProductsFromApi();
            this.filterProducts();
            this.calculateStats();
            this.selectedProducts = [];
            showToast('SKUs disabled successfully!', 'success');
          })
          .catch((error) => showToast(error.message || 'Failed to disable SKUs.', 'danger'));
        return;
      }

      if (action === 'enable_sku') {
        apiFetch(`${this.apiBase}/bulk-enable-sku`, {
          method: 'POST',
          body: JSON.stringify({ ids: this.selectedProducts }),
        })
          .then(async () => {
            await this.loadProductsFromApi();
            this.filterProducts();
            this.calculateStats();
            this.selectedProducts = [];
            showToast('SKUs enabled successfully!', 'success');
          })
          .catch((error) => showToast(error.message || 'Failed to enable SKUs.', 'danger'));
        return;
      }

      const status = action === 'publish' ? 'published' : 'draft';
      apiFetch(`${this.apiBase}/bulk-status`, {
        method: 'POST',
        body: JSON.stringify({
          ids: this.selectedProducts,
          status,
        }),
      })
        .then(async () => {
          await this.loadProductsFromApi();
          this.filterProducts();
          this.calculateStats();
          this.selectedProducts = [];
          showToast('Products updated successfully!', 'success');
        })
        .catch((error) => showToast(error.message || 'Failed to update products.', 'danger'));
    },

    async deleteProductsByIds(productIds) {
      const ids = [...new Set(productIds)];
      if (ids.length === 0) return;

      const confirmed = await confirmDelete({
        title: 'Are you sure?',
        text: `You are about to delete ${ids.length} product(s). This action can be undone from trash.`,
        confirmButtonText: 'Yes, delete!',
      });
      
      if (confirmed) {
        this.executeDelete(ids);
      }
    },

    executeDelete(ids) {
      apiFetch(`${this.apiBase}/bulk-delete`, {
        method: 'POST',
        body: JSON.stringify({ ids }),
      })
        .then(async () => {
          await this.loadProductsFromApi();
          this.filterProducts();
          this.calculateStats();
          this.selectedProducts = this.selectedProducts.filter(id => !ids.includes(id));
          showToast('Products deleted successfully!', 'success');
        })
        .catch((error) => showToast(error.message || 'Failed to delete products.', 'danger'));
    },

    duplicateProduct(product) {
      apiFetch(`${this.apiBase}/${product.id}/duplicate`, {
        method: 'POST',
      })
        .then(async () => {
          await this.loadProductsFromApi();
          this.filterProducts();
          this.calculateStats();
          showToast('Product duplicated successfully!', 'success');
        })
        .catch((error) => showToast(error.message || 'Failed to duplicate product.', 'danger'));
    },

    deleteProduct(product) {
      this.deleteProductsByIds([product.id]);
    },

    exportProducts() {
      const csvContent = [
        ['Name', 'SKU', 'Category', 'Price', 'Stock', 'Status', 'Created', 'Description'],
        ...this.filteredProducts.map(product => ([
          escapeCsv(product.name),
          escapeCsv(product.sku),
          escapeCsv(product.category),
          escapeCsv(product.price),
          escapeCsv(product.stock),
          escapeCsv(product.status),
          escapeCsv(product.created),
          escapeCsv(product.description),
        ])),
      ].map(row => row.join(',')).join('\n');

      downloadBlob('products.csv', csvContent, 'text/csv;charset=utf-8');
      
      showToast('Products exported successfully!', 'success');
    },

    async importProducts() {
      const fileInput = document.getElementById('productImportFile');
      const file = fileInput?.files?.[0];
      if (!file) {
        showToast('Choose a CSV file to import first.', 'warning');
        return;
      }

      this.importing = true;
      this.importErrors = [];

      try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('import_mode', this.importMode);

        const resData = await apiFetch(`${this.apiBase}/import`, {
          method: 'POST',
          body: formData,
        });

        await this.loadProductsFromApi();
        this.filterProducts();
        this.calculateStats();
        this.selectedProducts = [];
        
        if (resData.errors && resData.errors.length > 0) {
          this.importErrors = resData.errors;
          showToast(`Imported ${resData.imported || 0} products, but encountered errors.`, 'warning');
        } else {
          fileInput.value = '';
          getModal('#importModal')?.hide();
          showToast(resData.message || 'Products imported successfully.', 'success');
        }
      } catch (error) {
        showToast(error.message || 'Failed to import products.', 'danger');
      } finally {
        this.importing = false;
      }
    },


    initCharts() {
      // Prevent multiple chart initializations
      if (this.chartsInitialized) return;
      
      this.initSalesChart();
      this.initCategoryChart();
      this.chartsInitialized = true;
    },

    initSalesChart() {
      const salesChart = document.getElementById('salesChart');
      if (!salesChart) {
        console.warn('Sales chart element not found');
        return;
      }

      // Clear any existing chart content
      salesChart.innerHTML = '';

      try {

      // Sample sales data
      const salesData = {
        series: [{
          name: 'Sales',
          data: [65, 78, 85, 92, 88, 95, 102]
        }],
        chart: {
          type: 'area',
          height: 300,
          toolbar: { show: false }
        },
        colors: ['#6366f1'],
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3,
          }
        },
        stroke: {
          curve: 'smooth',
          width: 2
        },
        xaxis: {
          categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        },
        yaxis: {
          title: {
            text: 'Sales (₹1000s)'
          }
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return "₹" + val + "k"
            }
          }
        }
      };

        this.charts.sales = new ApexCharts(salesChart, salesData);
        this.charts.sales.render();
      } catch (error) {
        console.error('Error rendering sales chart:', error);
      }
    },

    initCategoryChart() {
      const categoryChart = document.getElementById('categoryChart');
      if (!categoryChart) {
        console.warn('Category chart element not found');
        return;
      }

      // Clear any existing chart content
      categoryChart.innerHTML = '';

      try {

      const chartData = {
        series: this.categoryStats.map(cat => cat.count),
        chart: {
          type: 'donut',
          height: 200
        },
        labels: this.categoryStats.map(cat => cat.name),
        colors: this.categoryStats.map(cat => cat.color),
        plotOptions: {
          pie: {
            donut: {
              size: '70%'
            }
          }
        },
        legend: {
          show: false
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val + " products"
            }
          }
        }
      };

        this.charts.category = new ApexCharts(categoryChart, chartData);
        this.charts.category.render();
      } catch (error) {
        console.error('Error rendering category chart:', error);
      }
    },

    get paginatedProducts() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredProducts.slice(start, end);
    },

    get totalPages() {
      return Math.ceil(this.filteredProducts.length / this.itemsPerPage);
    },

    get visiblePages() {
      if (this.totalPages <= 1) return [1];

      const pages = [];

      // Always show first page
      pages.push(1);
      
      if (this.totalPages <= 7) {
        // If total pages is small, show all
        for (let i = 2; i <= this.totalPages; i++) {
          pages.push(i);
        }
      } else {
        // Complex pagination logic
        if (this.currentPage <= 4) {
          // Near the beginning
          for (let i = 2; i <= 5; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(this.totalPages);
        } else if (this.currentPage >= this.totalPages - 3) {
          // Near the end
          pages.push('...');
          for (let i = this.totalPages - 4; i <= this.totalPages; i++) {
            pages.push(i);
          }
        } else {
          // In the middle
          pages.push('...');
          for (let i = this.currentPage - 1; i <= this.currentPage + 1; i++) {
            pages.push(i);
          }
          pages.push('...');
          pages.push(this.totalPages);
        }
      }
      
      return pages;
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
      }
    }
  }));

  // Product form component for modals
  Alpine.data('productForm', () => ({
    editingProductId: null,
    get options() {
      return Alpine.store('productTable')?.options || {
        categories: [],
        brands: [],
        uoms: [],
        taxRates: [],
        hsnCodes: [],
        warehouses: [],
        attributes: [],
        statusList: [],
      };
    },
    get baseSellingPriceExcludingTax() {
      let priceIncGst = parseFloat(this.form.selling_price_inc_gst) || 0;
      if (!this.form.tax_rate_id) return priceIncGst;
      let taxRate = this.options.taxRates.find(r => String(r.id) === String(this.form.tax_rate_id));
      if (!taxRate) return priceIncGst;
      let rate = parseFloat(taxRate.rate) || 0;
      return priceIncGst / (1 + (rate / 100));
    },
    form: {
      name: '',
      sku: '',
      category_id: '',
      brand_id: '',
      supplier_id: '',
      uom_id: '',
      tax_rate_id: '',
      hsn_code_id: '',
      default_warehouse_id: '',
      barcode: '',
      weight: '',
      purchase_price: '',
      mrp: '',
      selling_price_inc_gst: '',
      selling_price: '',
      stock: '',
      min_stock_level: 0,
      overselling_qty: 0,
      default_discount: 0,
      default_discount_type: 'percent',
      allow_overselling: false,
      manage_stock: true,
      batch_tracking: false,
      expiry_tracking: false,
      is_sku_enabled: true,
      description: '',
      application_instructions: '',
      status: 'draft',
      grade: '',
      image: '/assets/images/product-placeholder.svg',
      imageFile: null,
      attributes: [],
    },

    resetForm() {
      this.editingProductId = null;
      this.form = {
        name: '',
        sku: '',
        category_id: '',
        brand_id: '',
        supplier_id: '',
        uom_id: '',
        tax_rate_id: '',
        hsn_code_id: '',
        default_warehouse_id: '',
        barcode: '',
        weight: '',
        purchase_price: '',
        mrp: '',
        selling_price_inc_gst: '',
        selling_price: '',
        stock: '',
        min_stock_level: 0,
        overselling_qty: 0,
        default_discount: 0,
        default_discount_type: 'percent',
        allow_overselling: false,
        manage_stock: true,
        batch_tracking: false,
        expiry_tracking: false,
        is_sku_enabled: true,
        description: '',
        application_instructions: '',
        status: 'draft',
        grade: '',
        image: '/assets/images/product-placeholder.svg',
        imageFile: null,
        attributes: [],
      };

      const table = Alpine.store('productTable');
      if (table && table.options.warehouses && table.options.warehouses.length > 0) {
        this.form.default_warehouse_id = String(table.options.warehouses[0].id);
      }
    },

    handleImageUpload(event) {
      const file = event?.target?.files?.[0];
      if (!file) return;

      if (file.size > 5 * 1024 * 1024) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Image too large',
            text: 'The selected image exceeds the maximum size limit of 5MB.',
            icon: 'warning',
            confirmButtonColor: '#3085d6',
          });
        } else {
          alert('The selected image exceeds the maximum size limit of 5MB.');
        }
        event.target.value = '';
        return;
      }

      this.form.image = URL.createObjectURL(file);
      this.form.imageFile = file;
    },

    async saveProduct() {
      const table = Alpine.store('productTable');
      if (!table) return;

      if (!this.form.is_sku_enabled && !this.form.sku) {
        const prefix = this.form.name ? String(this.form.name).substring(0, 3).toUpperCase().replace(/[^A-Z]/g, '') || 'PROD' : 'PROD';
        const timestamp = Date.now().toString().slice(-6);
        this.form.sku = `${prefix}-${timestamp}`;
      }

      if (!this.form.name || !this.form.sku || !this.form.category_id ||
          this.form.selling_price_inc_gst === '' || this.form.purchase_price === '' ||
          this.form.stock === '' || !this.form.status || 
          !this.form.tax_rate_id || !this.form.hsn_code_id || 
          !this.form.uom_id || !this.form.weight) {
        showToast('Please fill in all required fields (Name, SKU, Category, Purchase Price, Selling Price, Stock, Status, Tax Rate, HSN Code, UOM, Weight/Volume).', 'warning');
        return;
      }

      const formData = new FormData();
      formData.append('name', String(this.form.name).trim());
      formData.append('sku', String(this.form.sku).trim());
      formData.append('category_id', String(this.form.category_id || ''));
      if (this.form.brand_id) formData.append('brand_id', String(this.form.brand_id));
      if (this.form.supplier_id) formData.append('supplier_id', String(this.form.supplier_id));
      if (this.form.uom_id) formData.append('uom_id', String(this.form.uom_id));
      if (this.form.tax_rate_id) formData.append('tax_rate_id', String(this.form.tax_rate_id));
      if (this.form.hsn_code_id) formData.append('hsn_code_id', String(this.form.hsn_code_id));
      if (this.form.default_warehouse_id) formData.append('default_warehouse_id', String(this.form.default_warehouse_id));
      if (this.form.barcode) formData.append('barcode', String(this.form.barcode).trim());
      if (this.form.weight) formData.append('weight', String(this.form.weight).trim());
      formData.append('purchase_price', String(Number(this.form.purchase_price || 0)));
      if (this.form.mrp !== '' && this.form.mrp !== null && this.form.mrp !== undefined) {
        formData.append('mrp', String(Number(this.form.mrp)));
      }
      formData.append('selling_price', String(Number(this.baseSellingPriceExcludingTax.toFixed(2))));
      if (!this.editingProductId) {
        formData.append('stock', String(Number(this.form.stock || 0)));
      }
      formData.append('min_stock_level', String(Number(this.form.min_stock_level || 0)));
      formData.append('overselling_qty', String(Number(this.form.overselling_qty || 0)));
      formData.append('default_discount', String(Number(this.form.default_discount || 0)));
      formData.append('default_discount_type', this.form.default_discount_type || 'percent');
      formData.append('description', String(this.form.description ?? '').trim());
      formData.append('status', normalizeStatus(this.form.status));
      formData.append('allow_overselling', this.form.allow_overselling ? '1' : '0');
      formData.append('manage_stock', this.form.manage_stock ? '1' : '0');
      formData.append('batch_tracking', this.form.batch_tracking ? '1' : '0');
      formData.append('expiry_tracking', this.form.expiry_tracking ? '1' : '0');
      formData.append('is_sku_enabled', this.form.is_sku_enabled ? '1' : '0');
      if (this.form.application_instructions) {
        formData.append('application_instructions', String(this.form.application_instructions).trim());
      }
      if (this.form.grade) {
        formData.append('grade', String(this.form.grade));
      }

      this.form.attributes.forEach((attributeId) => {
        formData.append('attributes[]', String(attributeId));
      });

      if (this.form.imageFile instanceof File) {
        if (this.form.imageFile.size > 5 * 1024 * 1024) {
          showToast('Selected image is too large. Maximum size allowed is 5MB.', 'warning');
          return;
        }
        formData.append('image', this.form.imageFile);
      }

      try {
        if (this.editingProductId !== null) {
          formData.append('_method', 'PATCH');
          await apiFetch(`${table.apiBase}/${this.editingProductId}`, {
            method: 'POST',
            body: formData,
            headers: {},
          });
          showToast(`Updated ${String(this.form.name).trim()} successfully.`, 'success');
        } else {
          await apiFetch(table.apiBase, {
            method: 'POST',
            body: formData,
            headers: {},
          });
          showToast(`Created ${String(this.form.name).trim()} successfully.`, 'success');
        }

        await table.loadProductsFromApi();
        table.filterProducts();
        table.calculateStats();
        this.resetForm();
        getModal('#productModal')?.hide();
      } catch (error) {
        showToast(error.message || 'Failed to save product.', 'danger');
      }
    }
  }));

  // Search component for header
  Alpine.data('searchComponent', createSearchComponent({ getResults: () => [] }));

  // Theme switch component
  Alpine.data('themeSwitch', () => ({
    currentTheme: 'light',

    init() {
      this.currentTheme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-bs-theme', this.currentTheme);
    },

    toggle() {
      this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-bs-theme', this.currentTheme);
      localStorage.setItem('theme', this.currentTheme);
    }
  }));
});
