import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

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

  const res = await fetch(url, { headers: fetchHeaders, ...otherOptions });
  const text = await res.text();
  const data = text ? JSON.parse(text) : {};

  if (!res.ok) {
    const validation = data?.errors ? Object.values(data.errors).flat().join(' ') : '';
    const message = validation || data?.message || data?.error || 'Request failed';
    throw new Error(message);
  }
  return data;
}

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const id = 'toast-' + Date.now();
  const iconMap = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
  const el = document.createElement('div');
  el.id = id;
  el.className = `toast align-items-center text-bg-${type} border-0 show mb-2`;
  el.setAttribute('role', 'alert');
  el.innerHTML = `<div class="d-flex"><div class="toast-body"><i class="bi ${iconMap[type] ?? 'bi-info-circle-fill'} me-2"></i><span></span></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
  el.querySelector('.toast-body span').textContent = message;
  container.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

async function confirmDelete({ title, text, confirmButtonText = 'Yes, delete it' }) {
  const result = await Swal.fire({
    title, text, icon: 'warning', showCancelButton: true, confirmButtonText, cancelButtonText: 'Cancel', confirmButtonColor: '#dc3545', reverseButtons: true, focusCancel: true,
  });
  return result.isConfirmed;
}

function getModal(selector) {
  const el = document.querySelector(selector);
  return el ? Modal.getOrCreateInstance(el) : null;
}

document.addEventListener('alpine:init', () => {
  Alpine.data('attendancesTable', () => ({
    items: [],
    leaves: [],
    selectedItems: [],
    searchQuery: '',
    statusFilter: '',
    dateFilter: '',
    sortField: 'date',
    sortDirection: 'desc',
    isLoading: false,

    currentView: 'list',
    currentMonthDate: new Date(),
    calendarDays: [],

    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
    itemsPerPage: 15,

    stats: { present: 0, absent: 0, late: 0, halfDay: 0, total: 0 },

    init() {
      this.generateCalendarDays();
      this.loadItems();
      window.addEventListener('attendance-saved', () => {
        this.loadItems();
      });
    },

    async loadItems() {
      this.isLoading = true;
      try {
        const params = new URLSearchParams({
          page: this.currentPage,
          per_page: this.currentView === 'calendar' ? -1 : this.itemsPerPage,
          sort_by: this.sortField,
          sort_dir: this.sortDirection,
        });
        if (this.searchQuery) params.set('search', this.searchQuery);
        if (this.statusFilter) params.set('status', this.statusFilter);
        
        if (this.currentView === 'calendar') {
          if (this.calendarDays.length > 0) {
            params.set('start_date', this.calendarDays[0].date);
            params.set('end_date', this.calendarDays[this.calendarDays.length - 1].date);
          }
        } else {
          if (this.dateFilter) params.set('date', this.dateFilter);
        }

        const data = await apiFetch(`/api/attendances?${params}`);
        this.items = data.data ?? [];
        this.leaves = data.leaves ?? [];
        this.totalItems = data.total ?? this.items.length;
        this.totalPages = data.last_page ?? 1;
        this.currentPage = data.current_page ?? 1;
        
        if (this.currentView === 'calendar') {
          this.mapEventsToCalendar();
        }
        
        this.calculateStats();
      } catch (err) {
        showToast('Failed to load attendances: ' + err.message, 'danger');
      } finally {
        this.isLoading = false;
      }
    },

    calculateStats() {
      // Group items by date to get unique days
      const uniqueDays = {};
      this.items.forEach(i => {
         if (!uniqueDays[i.date]) {
             uniqueDays[i.date] = i.status;
         } else {
             if (i.status !== 'Absent') uniqueDays[i.date] = i.status; // Prioritize non-absent
         }
      });
      
      const uniqueStatuses = Object.values(uniqueDays);

      this.stats.total = this.totalItems;
      this.stats.present = uniqueStatuses.filter(s => s === 'Present').length;
      this.stats.absent = uniqueStatuses.filter(s => s === 'Absent').length;
      this.stats.late = uniqueStatuses.filter(s => s === 'Late').length;
      this.stats.halfDay = uniqueStatuses.filter(s => s === 'Half-Day').length;
    },

    filterItems() {
      this.currentPage = 1;
      this.loadItems();
    },
    
    exportSummary() {
      const pad = (n) => String(n).padStart(2, '0');
      const monthStr = `${this.currentMonthDate.getFullYear()}-${pad(this.currentMonthDate.getMonth() + 1)}`;
      window.open(`/api/attendances/export/summary?month=${monthStr}`, '_blank');
    },
    
    exportDetailed() {
      const pad = (n) => String(n).padStart(2, '0');
      const monthStr = `${this.currentMonthDate.getFullYear()}-${pad(this.currentMonthDate.getMonth() + 1)}`;
      window.open(`/api/attendances/export/detailed?month=${monthStr}`, '_blank');
    },
    
    get currentMonthYear() {
      return this.currentMonthDate.toLocaleString('default', { month: 'long', year: 'numeric' });
    },
    
    switchView(view) {
      this.currentView = view;
      if (view === 'calendar') {
        this.generateCalendarDays();
      }
      this.loadItems();
    },

    previousMonth() {
      this.currentMonthDate = new Date(this.currentMonthDate.getFullYear(), this.currentMonthDate.getMonth() - 1, 1);
      this.generateCalendarDays();
      this.loadItems();
    },

    nextMonth() {
      this.currentMonthDate = new Date(this.currentMonthDate.getFullYear(), this.currentMonthDate.getMonth() + 1, 1);
      this.generateCalendarDays();
      this.loadItems();
    },

    goToToday() {
      this.currentMonthDate = new Date();
      this.generateCalendarDays();
      this.loadItems();
    },

    generateCalendarDays() {
      const year = this.currentMonthDate.getFullYear();
      const month = this.currentMonthDate.getMonth();
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const days = [];
      const today = new Date();

      let firstDayOfWeek = firstDay.getDay();
      for (let i = firstDayOfWeek; i > 0; i--) {
        const d = new Date(year, month, 1 - i);
        days.push({
          date: d.toISOString().split('T')[0],
          day: d.getDate(),
          isOtherMonth: true,
          isToday: d.toDateString() === today.toDateString(),
          events: []
        });
      }

      const pad = (n) => String(n).padStart(2, '0');
      
      for (let i = 1; i <= lastDay.getDate(); i++) {
        const d = new Date(year, month, i);
        days.push({
          date: `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`,
          day: d.getDate(),
          isOtherMonth: false,
          isToday: d.toDateString() === today.toDateString(),
          events: []
        });
      }

      let remainingDays = 42 - days.length;
      for (let i = 1; i <= remainingDays; i++) {
        const d = new Date(year, month + 1, i);
        days.push({
          date: `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`,
          day: d.getDate(),
          isOtherMonth: true,
          isToday: d.toDateString() === today.toDateString(),
          events: []
        });
      }

      this.calendarDays = days;
    },

    mapEventsToCalendar() {
      const itemsByDate = {};
      this.items.forEach(item => {
        if (!itemsByDate[item.date]) itemsByDate[item.date] = [];
        itemsByDate[item.date].push(item);
      });

      this.calendarDays.forEach(day => {
        day.events = [];
        
        // Add Leave events if they exist for this day
        this.leaves.forEach(leave => {
          if (day.date >= leave.start_date && day.date <= leave.end_date) {
            let leaveClass = 'bg-secondary text-white';
            if (leave.status === 'Approved') leaveClass = 'bg-primary text-white';
            if (leave.status === 'Rejected') leaveClass = 'bg-secondary text-white';
            if (leave.status === 'Pending') leaveClass = 'bg-info text-dark';
            
            day.events.push({
              id: 'leave-' + leave.id + '-' + day.date,
              type: leaveClass + ' border-0',
              status: `Leave (${leave.leave_type})`,
              checkIn: leave.status,
              checkOut: null,
              totalTime: null,
              raw: null
            });
          }
        });

        // Add Attendance events
        if (itemsByDate[day.date]) {
          const items = itemsByDate[day.date];
          let firstCheckIn = null;
          let lastCheckOut = null;
          let totalMins = 0;
          let overallStatus = 'Absent';
          let hasActiveSession = false;

          items.forEach(item => {
            if (item.status !== 'Absent') overallStatus = item.status; // Priorities: Present > Absent

            if (item.check_in) {
              if (!firstCheckIn || item.check_in < firstCheckIn) firstCheckIn = item.check_in;
            }
            if (item.check_out) {
              if (!lastCheckOut || item.check_out > lastCheckOut) lastCheckOut = item.check_out;
            } else if (item.check_in) {
              hasActiveSession = true;
            }

            if (item.total_time) {
              const parts = item.total_time.split(' ');
              if (parts.length === 2) {
                const h = parseInt(parts[0].replace('h', '')) || 0;
                const m = parseInt(parts[1].replace('m', '')) || 0;
                totalMins += (h * 60) + m;
              }
            }
          });

          let totalTimeStr = '--h --m';
          if (totalMins > 0) {
            const h = Math.floor(totalMins / 60);
            const m = String(totalMins % 60).padStart(2, '0');
            totalTimeStr = `${h}h ${m}m`;
          }

          day.events.push({
            id: items[0].id,
            status: overallStatus,
            type: this.getEventClass(overallStatus),
            checkIn: firstCheckIn ? firstCheckIn.substring(0, 5) : '--:--',
            checkOut: lastCheckOut ? lastCheckOut.substring(0, 5) : '--:--',
            totalTime: totalTimeStr,
            raw: items[0]
          });
        }
      });
      
      const now = new Date();
      const pad = (n) => String(n).padStart(2, '0');
      const todayDateStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
      
      this.calendarDays.forEach(day => {
        if (day.events.length === 0 && day.date <= todayDateStr && !day.isOtherMonth) {
          // Parse date properly across timezones avoiding shift
          const [year, month, date] = day.date.split('-');
          const d = new Date(year, month - 1, date);
          
          if (d.getDay() !== 0 && d.getDay() !== 6) {
            day.events.push({
              id: 'auto-absent-' + day.date,
              status: 'Absent',
              type: this.getEventClass('Absent'),
              checkIn: '--:--',
              checkOut: '--:--',
              totalTime: '--h --m',
              raw: { date: day.date, status: 'Absent' }
            });
          }
        }
      });
    },

    getEventClass(status) {
      if (status === 'Present') return 'bg-success text-white border-0';
      if (status === 'Absent') return 'bg-danger text-white border-0';
      if (status === 'Late') return 'bg-warning text-dark border-0';
      if (status === 'Half-Day') return 'bg-dark text-white border-0';
      return 'bg-secondary text-white border-0';
    },

    addEventForDay(day) {
      window.dispatchEvent(new CustomEvent('open-attendance-modal', { detail: { date: day.date } }));
    },

    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'desc';
      }
      this.loadItems();
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadItems();
      }
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
      return result.filter((v, i, a) => a.indexOf(v) === i && (typeof v === 'string' || v <= this.totalPages));
    },

    get pageFrom() {
      if (this.totalItems === 0) return 0;
      return (this.currentPage - 1) * this.itemsPerPage + 1;
    },
    get pageTo() {
      return Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
    },

    toggleAll(checked) {
      if (checked) {
        this.items.forEach(item => {
          if (!this.selectedItems.includes(String(item.id))) this.selectedItems.push(String(item.id));
        });
      } else {
        const currentIds = this.items.map(item => String(item.id));
        this.selectedItems = this.selectedItems.filter(id => !currentIds.includes(id));
      }
    },

    toggleItem(id) {
      id = String(id);
      if (this.selectedItems.includes(id)) {
        this.selectedItems = this.selectedItems.filter(i => i !== id);
      } else {
        this.selectedItems.push(id);
      }
    },

    openCreate() {
      window.dispatchEvent(new CustomEvent('open-attendance-modal', { detail: null }));
    },

    editItem(item) {
      if (!item) return;
      window.dispatchEvent(new CustomEvent('open-attendance-modal', { detail: item }));
    },

    async deleteItem(item) {
      const confirmed = await confirmDelete({
        title: 'Delete Attendance?',
        text: `Do you want to delete the attendance record for ${item.user ? item.user.name : 'this user'}?`,
        confirmButtonText: 'Yes, delete',
      });
      if (!confirmed) return;
      try {
        const res = await apiFetch(`/api/attendances/${item.id}`, { method: 'DELETE' });
        showToast(res.message, 'success');
        this.loadItems();
      } catch (err) {
        showToast('Failed to delete: ' + err.message, 'danger');
      }
    },

    async bulkAction(action) {
      if (this.selectedItems.length === 0) {
        showToast('Please select records first.', 'warning');
        return;
      }
      const confirmed = await confirmDelete({
        title: 'Delete selected records?',
        text: `Do you want to delete ${this.selectedItems.length} selected record(s)?`,
        confirmButtonText: 'Yes, delete',
      });
      if (!confirmed) return;
      
      try {
        const res = await apiFetch('/api/attendances/bulk-action', {
          method: 'POST',
          body: JSON.stringify({ action, ids: this.selectedItems }),
        });
        showToast(res.message, 'success');
        this.selectedItems = [];
        this.loadItems();
      } catch (err) {
        showToast(`Bulk action failed: ${err.message}`, 'danger');
      }
    },
  }));

  Alpine.data('attendanceForm', () => ({
    editingId: null,
    saving: false,
    users: [],
    form: {
      user_id: '',
      date: new Date().toISOString().split('T')[0],
      check_in_time: '',
      check_out_time: '',
      status: 'Present',
      notes: ''
    },

    init() {
      this.loadUsers();
      window.addEventListener('open-attendance-modal', (e) => {
        if (e.detail) {
          this.editingId = e.detail.id;
          this.form.user_id = e.detail.user_id;
          
          const dateStr = e.detail.date ? e.detail.date.split('T')[0] : '';
          this.form.date = dateStr;
          
          this.form.check_in_time = e.detail.check_in ? `${dateStr}T${e.detail.check_in}` : '';
          this.form.check_out_time = e.detail.check_out ? `${dateStr}T${e.detail.check_out}` : '';
          this.form.status = e.detail.status;
          this.form.notes = e.detail.notes || '';
          
          const title = document.querySelector('#attendanceModalLabel');
          if (title) title.innerHTML = '<i class="bi bi-calendar-check-fill text-primary me-2"></i>Edit Attendance';
        } else {
          this.resetForm();
          const title = document.querySelector('#attendanceModalLabel');
          if (title) title.innerHTML = '<i class="bi bi-calendar-check-fill text-primary me-2"></i>Log Attendance';
        }
        getModal('#attendanceModal')?.show();
      });
    },

    resetForm() {
      this.editingId = null;
      this.form = {
        user_id: '',
        date: new Date().toISOString().split('T')[0],
        check_in_time: '',
        check_out_time: '',
        status: 'Present',
        notes: ''
      };
      this.saving = false;
    },

    async loadUsers() {
      try {
        const res = await apiFetch('/api/users?per_page=100');
        this.users = res.data ?? res;
      } catch(e) {}
    },

    async saveItem() {
      this.saving = true;
      try {
        const method = this.editingId ? 'PUT' : 'POST';
        const url = this.editingId ? `/api/attendances/${this.editingId}` : '/api/attendances';
        const res = await apiFetch(url, {
          method: method,
          body: JSON.stringify(this.form)
        });
        showToast(res.message, 'success');
        window.dispatchEvent(new CustomEvent('attendance-saved'));
        getModal('#attendanceModal')?.hide();
        this.resetForm();
      } catch (err) {
        showToast(err.message, 'danger');
      } finally {
        this.saving = false;
      }
    }
  }));
});
