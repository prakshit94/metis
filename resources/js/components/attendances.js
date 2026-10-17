import Alpine from 'alpinejs';
import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
  const { headers, ...otherOptions } = options;
  const fetchHeaders = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': getCsrfToken(),
    ...(headers || {}),
  };

  const res = await fetch(url, { headers: fetchHeaders, ...otherOptions });
  if (res.status === 401 || res.status === 419 || res.redirected) {
    throw new Error('Your session has expired. Reload the page and sign in again.');
  }
  const text = await res.text();
  let data;
  try {
    data = text ? JSON.parse(text) : {};
  } catch {
    throw new Error('The server returned an unexpected response. Please retry.');
  }
  if (!res.ok) {
    const validation = data?.errors ? Object.values(data.errors).flat().join(' ') : '';
    throw new Error(validation || data?.message || data?.error || 'Request failed');
  }
  return data;
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
  el.innerHTML = `<div class="d-flex"><div class="toast-body"><i class="bi ${iconMap[type] ?? 'bi-info-circle-fill'} me-2"></i><span></span></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
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

function getModal(selector) {
  const el = document.querySelector(selector);
  return el ? Modal.getOrCreateInstance(el) : null;
}

function localDate(date = new Date()) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function employeeDate(value, timeZone = 'UTC') {
  if (!value) return '';
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const parts = new Intl.DateTimeFormat('en', {
    timeZone,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(date);
  const fields = Object.fromEntries(parts.map((part) => [part.type, part.value]));
  return `${fields.year}-${fields.month}-${fields.day}`;
}

async function loadUserOptions(currentUserId) {
  const users = [];
  let page = 1;
  try {
    let result;
    do {
      result = await apiFetch(`/api/users?per_page=100&page=${page++}`);
      users.push(...(result.data ?? result));
    } while (page <= (result.last_page ?? 1));
  } catch (error) {
    // A viewer need not have permission to browse the employee directory.
  }
  if (currentUserId && !users.some((user) => String(user.id) === String(currentUserId))) {
    users.unshift({ id: currentUserId, name: 'Current user' });
  }
  return users;
}

document.addEventListener('alpine:init', () => {
  Alpine.data('rosterManager', () => ({
    file: null, preview: [], hash: '', busy: false, error: '', message: '', claims: [], page: 1, pages: 1,
    date: '', reason: '',
    _onSaved: null,
    init() { this.loadClaims(); this._onSaved = () => this.loadClaims(); window.addEventListener('attendance-saved', this._onSaved); },
    destroy() { window.removeEventListener('attendance-saved', this._onSaved); },
    choose(event) { this.file = event.target.files[0] || null; this.preview = []; this.hash = ''; this.message = ''; },
    template(systemIds = false) {
      const url = URL.createObjectURL(new Blob([systemIds ? 'user_id,date,day_type\nUSER_ID_1,2026-10-18,Week Off\nUSER_ID_2,2026-10-18,Working\n' : 'employee_id,date,day_type\nEMP001,2026-10-18,Week Off\nEMP002,2026-10-18,Working\n'], {type:'text/csv'}));
      const link = document.createElement('a'); link.href = url; link.download = 'roster-template.csv'; link.click(); URL.revokeObjectURL(url);
    },
    async upload(commit = false) {
      if(this.busy || !this.file || (commit && !this.hash)) return;
      this.busy = true; this.error = ''; this.message = '';
      try {
        const body = new FormData(); body.append('file', this.file); body.append('commit', commit ? '1':'0'); if(commit) body.append('preview_hash',this.hash);
        const response = await fetch('/api/rosters/import', {method:'POST', headers:{Accept:'application/json','X-CSRF-TOKEN':getCsrfToken()}, body});
        const result = await response.json();
        if(!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : result.message);
        this.preview = result.data; this.hash = commit ? '' : result.preview_hash; this.message = result.message;
        if(commit) window.dispatchEvent(new CustomEvent('attendance-saved'));
      } catch(error) { this.error = error.message; this.hash = ''; }
      finally { this.busy = false; }
    },
    async loadClaims() {
      try { const result = await apiFetch('/api/rosters/co?page='+this.page); this.claims = result.data; this.pages = result.last_page; }
      catch(error) { this.error = error.message; }
    },
    async claim() {
      if(this.busy) return; this.busy = true; this.error = ''; this.message = '';
      try { const result = await apiFetch('/api/rosters/co',{method:'POST', body:JSON.stringify({date:this.date,reason:this.reason})}); this.message = result.message; this.reason = ''; this.page = 1; await this.loadClaims(); }
      catch(error) { this.error = error.message; } finally { this.busy = false; }
    },
    async review(item, status) {
      if(this.busy) return;
      const answer = await Swal.fire({title:status === 'Approved' ? 'Approve and credit 1 CO?' : 'Reject CO request?', input:'textarea', inputLabel:'Review comment (optional)', showCancelButton:true, confirmButtonText:status === 'Approved'?'Approve':'Reject'});
      if(!answer.isConfirmed || this.busy) return;
      this.busy = true; this.error = ''; this.message = '';
      try { const result = await apiFetch('/api/rosters/co/'+item.id+'/review',{method:'PATCH',body:JSON.stringify({status,comment:answer.value || ''})}); this.message = result.message; await this.loadClaims(); window.dispatchEvent(new CustomEvent('attendance-saved')); }
      catch(error) { this.error = error.message; } finally { this.busy = false; }
    }
  }));
  Alpine.data('attendancesTable', (currentUserId = '', access = {}) => ({
    items: [],
    leaves: [],
    holidays: [],
    rosters: [],
    holidayError: '',
    selectedDay: null,
    rangeStart: '',
    rangeEnd: '',
    insights: {
      records: 0,
      recorded_days: 0,
      completed_minutes: 0,
      open_sessions: 0,
      incomplete_sessions: 0,
    },
    selectedItems: [],
    searchQuery: '',
    statusFilter: '',
    dateFilter: '',
    userFilter: /^\d+$/.test(
      new URLSearchParams(window.location?.search || '').get('user_id') || ''
    )
      ? new URLSearchParams(window.location.search).get('user_id')
      : currentUserId
        ? String(currentUserId)
        : '',
    usersList: [],
    sortField: 'date',
    sortDirection: 'desc',
    isLoading: false,
    error: null,
    _loadRequestId: 0,
    _initialized: false,
    _onSaved: null,

    currentView: 'calendar',
    currentMonthDate: new Date(),
    calendarDays: [],

    currentPage: 1,
    totalPages: 1,
    totalItems: 0,
    itemsPerPage: 15,

    stats: { present: 0, absent: 0, late: 0, halfDay: 0, total: 0 },

    init() {
      if (this._initialized) return;
      this._initialized = true;
      this.generateCalendarDays();
      this.loadUsersList();
      this.loadItems();
      if (access.holidays) this.loadHolidayMarkers();
      this._onSaved = () => this.loadItems();
      window.addEventListener('attendance-saved', this._onSaved);
    },

    destroy() {
      this._loadRequestId++;
      window.removeEventListener('attendance-saved', this._onSaved);
    },

    async loadUsersList() {
      this.usersList = await loadUserOptions(currentUserId);
      if (this.currentView === 'calendar') this.mapEventsToCalendar();
    },

    async loadItems() {
      const requestId = ++this._loadRequestId;
      this.isLoading = true;
      this.error = null;
      try {
        const params = this.reportParams();

        const data = await apiFetch(`/api/attendances?${params}`);
        if (requestId !== this._loadRequestId) return;
        this.insights = data.summary || this.insights;
        this.items = data.data ?? [];
        this.selectedItems = this.selectedItems.filter((id) =>
          this.items.some((item) => String(item.id) === String(id))
        );
        this.leaves = data.leaves ?? [];
        this.rosters = data.rosters ?? [];
        this.calendarDays.forEach(day => { day.rosters = this.rosters.filter(row => row.date === day.date); });
        this.totalItems = data.total ?? this.items.length;
        if (
          this.currentView === 'list' &&
          this.totalItems > 0 &&
          this.currentPage > (data.last_page || 1)
        ) {
          this.currentPage = data.last_page || 1;
          return this.loadItems();
        }
        this.totalPages = data.last_page ?? 1;
        this.currentPage = data.current_page ?? 1;

        if (this.currentView === 'calendar') {
          this.mapEventsToCalendar();
        }

        this.calculateStats();
      } catch (err) {
        if (requestId !== this._loadRequestId) return;
        this.error = err.message;
        showToast('Failed to load attendances: ' + err.message, 'danger');
      } finally {
        if (requestId === this._loadRequestId) this.isLoading = false;
      }
    },

    calculateStats() {
      // Group items by date to get unique days
      const uniqueDays = {};
      this.items.forEach((i) => {
        const dayKey = `${i.user_id}:${i.date}`;
        if (!uniqueDays[dayKey]) {
          uniqueDays[dayKey] = i.status;
        } else {
          if (i.status !== 'Absent') uniqueDays[dayKey] = i.status; // Prioritize non-absent
        }
      });

      const uniqueStatuses = Object.values(uniqueDays);

      this.stats.total = this.totalItems;
      this.stats.present = uniqueStatuses.filter((s) => s === 'Present').length;
      this.stats.absent = uniqueStatuses.filter((s) => s === 'Absent').length;
      this.stats.late = uniqueStatuses.filter((s) => s === 'Late').length;
      this.stats.halfDay = uniqueStatuses.filter((s) => s === 'Half-Day').length;
    },

    filterItems() {
      if (this.rangeStart && this.rangeEnd && this.rangeEnd < this.rangeStart) {
        this.error = 'The end date must be on or after the start date.';
        return;
      }
      this.currentPage = 1;
      this.loadItems();
    },

    get employeeJoined() {
      return employeeDate(this.selectedEmployee?.joining_date, access.timeZone) || '—';
    },
    get selectedEmployee() {
      return this.usersList.find((user) => String(user.id) === String(this.userFilter)) || null;
    },
    get completedTime() {
      const minutes = Number(this.insights.completed_minutes || 0);
      return `${Math.floor(minutes / 60)}h ${String(minutes % 60).padStart(2, '0')}m`;
    },
    get daySessions() {
      return this.selectedDay
        ? this.items.filter((item) => item.date === this.selectedDay.date)
        : [];
    },
    get dayLeaves() {
      return this.selectedDay
        ? this.leaves.filter(
            (item) =>
              item.start_date <= this.selectedDay.date && item.end_date >= this.selectedDay.date
          )
        : [];
    },
    inspectDay(day) {
      this.selectedDay = day;
      getModal('#attendanceDayModal')?.show();
    },
    requestCorrection(item = null) {
      if (item && String(item.user_id) !== String(currentUserId)) return;
      getModal('#attendanceDayModal')?.hide();
      // Wait for the current modal to finish closing before opening the request.
      const open = () =>
        window.dispatchEvent(new CustomEvent('open-correction-modal', { detail: item }));
      const dayModal = document.querySelector('#attendanceDayModal');
      if (
        dayModal?.classList.contains('show') ||
        (dayModal?.classList.contains('fade') && document.querySelector('.modal-backdrop'))
      ) {
        dayModal.addEventListener('hidden.bs.modal', open, { once: true });
      } else open();
    },
    resetFilters() {
      this.searchQuery = '';
      this.statusFilter = '';
      this.dateFilter = '';
      this.rangeStart = '';
      this.rangeEnd = '';
      this.userFilter = String(currentUserId);
      this.filterItems();
    },
    useMonthRange() {
      const year = this.currentMonthDate.getFullYear(),
        month = this.currentMonthDate.getMonth();
      this.rangeStart = localDate(new Date(year, month, 1));
      this.rangeEnd = localDate(new Date(year, month + 1, 0));
      this.dateFilter = '';
      this.currentView = 'list';
      this.filterItems();
    },
    async loadHolidayMarkers() {
      try {
        let page = 1,
          lastPage = 1;
        const holidays = [];
        do {
          const result = await apiFetch(`/api/holidays?per_page=100&page=${page++}`);
          holidays.push(...(result.data || []));
          lastPage = result.last_page || 1;
        } while (page <= lastPage);
        this.holidays = holidays;
        this.calendarDays.forEach((day) => {
          day.holidays = holidays.filter((holiday) => holiday.date === day.date);
        });
      } catch (error) {
        this.holidayError = 'Holiday markers could not be loaded: ' + error.message;
      }
    },

    reportParams(all = false) {
      const params = new URLSearchParams({
        page: this.currentPage,
        per_page: all || this.currentView === 'calendar' ? -1 : this.itemsPerPage,
        sort_by: this.sortField,
        sort_dir: this.sortDirection,
      });
      if (this.searchQuery) params.set('search', this.searchQuery);
      if (this.statusFilter) params.set('status', this.statusFilter);
      if (this.userFilter) params.set('user_id', this.userFilter);

      if (this.currentView === 'calendar') {
        if (this.calendarDays.length > 0) {
          params.set('start_date', this.calendarDays[0].date);
          params.set('end_date', this.calendarDays[this.calendarDays.length - 1].date);
        }
      } else {
        if (this.dateFilter) params.set('date', this.dateFilter);
        if (this.rangeStart) params.set('start_date', this.rangeStart);
        if (this.rangeEnd) params.set('end_date', this.rangeEnd);
      }
      return params;
    },
    exportFiltered() {
      window.open('/api/attendances/export/filtered?' + this.reportParams(true), '_blank');
    },

    exportSummary() {
      const pad = (n) => String(n).padStart(2, '0');
      const monthStr = `${this.currentMonthDate.getFullYear()}-${pad(this.currentMonthDate.getMonth() + 1)}`;
      window.open(
        `/api/attendances/export/summary?${new URLSearchParams({ month: monthStr, ...(this.userFilter ? { user_id: this.userFilter } : {}) })}`,
        '_blank'
      );
    },

    exportDetailed() {
      const pad = (n) => String(n).padStart(2, '0');
      const monthStr = `${this.currentMonthDate.getFullYear()}-${pad(this.currentMonthDate.getMonth() + 1)}`;
      window.open(
        `/api/attendances/export/detailed?${new URLSearchParams({ month: monthStr, ...(this.userFilter ? { user_id: this.userFilter } : {}) })}`,
        '_blank'
      );
    },

    get currentMonthYear() {
      return this.currentMonthDate.toLocaleString('default', { month: 'long', year: 'numeric' });
    },

    switchView(view) {
      this.currentView = view;
      this.currentPage = 1;
      this.selectedItems = [];
      if (view === 'calendar') {
        this.generateCalendarDays();
      }
      this.loadItems();
    },

    previousMonth() {
      this.currentMonthDate = new Date(
        this.currentMonthDate.getFullYear(),
        this.currentMonthDate.getMonth() - 1,
        1
      );
      this.generateCalendarDays();
      this.loadItems();
    },

    nextMonth() {
      this.currentMonthDate = new Date(
        this.currentMonthDate.getFullYear(),
        this.currentMonthDate.getMonth() + 1,
        1
      );
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
          date: localDate(d),
          day: d.getDate(),
          isOtherMonth: true,
          isToday: d.toDateString() === today.toDateString(),
          events: [],
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
          events: [],
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
          events: [],
        });
      }

      this.calendarDays = days.map((day) => ({
        ...day,
        holidays: this.holidays.filter((holiday) => holiday.date === day.date),
        rosters: this.rosters.filter((row) => row.date === day.date),
      }));
    },

    mapEventsToCalendar() {
      const itemsByDate = {};
      this.items.forEach((item) => {
        if (!itemsByDate[item.date]) itemsByDate[item.date] = [];
        itemsByDate[item.date].push(item);
      });

      this.calendarDays.forEach((day) => {
        day.events = [];

        // Add Leave events if they exist for this day
        this.leaves.forEach((leave) => {
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
              raw: { ...leave, isLeave: true },
            });
          }
        });

        // Add Attendance events
        if (itemsByDate[day.date]) {
          const userGroups = itemsByDate[day.date].reduce((groups, item) => {
            (groups[item.user_id] ||= []).push(item);
            return groups;
          }, {});
          Object.values(userGroups).forEach((items) => {
            let firstCheckIn = null;
            let lastCheckOut = null;
            let totalMins = 0;
            let overallStatus = 'Absent';
            let hasActiveSession = false;

            items.forEach((item) => {
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
                  totalMins += h * 60 + m;
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
              userName: items[0].user?.name || '',
              type: this.getEventClass(overallStatus),
              checkIn: firstCheckIn ? firstCheckIn.substring(0, 5) : '--:--',
              checkOut: lastCheckOut ? lastCheckOut.substring(0, 5) : '--:--',
              totalTime: totalTimeStr,
              raw: items[0],
            });
          });
        }
      });

      const now = new Date();
      const pad = (n) => String(n).padStart(2, '0');
      const todayDateStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

      let joiningDateStr = null;
      if (this.userFilter) {
        const u = this.usersList.find((x) => String(x.id) === String(this.userFilter));
        if (u && u.joining_date) {
          joiningDateStr = employeeDate(u.joining_date, access.timeZone);
        }
      } else if (this.items.length > 0 && this.items[0].user && this.items[0].user.joining_date) {
        joiningDateStr = employeeDate(this.items[0].user.joining_date, access.timeZone);
      }

      this.calendarDays.forEach((day) => {
        if (
          this.userFilter &&
          day.events.length === 0 &&
          day.date <= todayDateStr &&
          !day.isOtherMonth
        ) {
          if (joiningDateStr && day.date < joiningDateStr) return;

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
              raw: { date: day.date, status: 'Absent' },
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
      if (access.create === false) return;
      window.dispatchEvent(
        new CustomEvent('open-attendance-modal', {
          detail: { date: day.date, user_id: this.userFilter || currentUserId },
        })
      );
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

    get pageFrom() {
      if (this.totalItems === 0) return 0;
      return (this.currentPage - 1) * this.itemsPerPage + 1;
    },
    get pageTo() {
      return Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
    },

    toggleAll(checked) {
      if (checked) {
        this.items.forEach((item) => {
          if (!this.selectedItems.includes(String(item.id)))
            this.selectedItems.push(String(item.id));
        });
      } else {
        const currentIds = this.items.map((item) => String(item.id));
        this.selectedItems = this.selectedItems.filter((id) => !currentIds.includes(id));
      }
    },

    toggleItem(id) {
      id = String(id);
      if (this.selectedItems.includes(id)) {
        this.selectedItems = this.selectedItems.filter((i) => i !== id);
      } else {
        this.selectedItems.push(id);
      }
    },

    openCreate() {
      window.dispatchEvent(new CustomEvent('open-attendance-modal', { detail: null }));
    },

    openLeaveCreate() {
      window.dispatchEvent(new CustomEvent('open-leave-modal', { detail: null }));
    },

    editItem(item) {
      if (!item) return;
      if (!item.id) {
        this.addEventForDay({ date: item.date });
        return;
      }
      const ownRecord = String(item.user_id) === String(currentUserId);
      if (item.isLeave) {
        if (
          item.status !== 'Pending' ||
          access.leaveEdit === false ||
          (!access.global && !ownRecord)
        ) {
          showToast('This leave is read-only.', 'info');
          return;
        }
        window.dispatchEvent(new CustomEvent('open-leave-modal', { detail: item }));
      } else {
        if (access.edit === false || (!access.global && !ownRecord)) {
          showToast('This attendance is read-only.', 'info');
          return;
        }
        window.dispatchEvent(new CustomEvent('open-attendance-modal', { detail: item }));
      }
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

  Alpine.data('attendanceCorrections', (currentUserId, canReview = false) => ({
    items: [],
    scope: 'mine',
    statusFilter: 'Pending',
    currentPage: 1,
    totalPages: 1,
    total: 0,
    loading: false,
    saving: false,
    error: '',
    formError: '',
    _requestId: 0,
    _onOpen: null,
    form: {
      attendance_id: '',
      date: localDate(),
      check_in_time: '',
      check_out_time: '',
      attendance_status: 'Present',
      notes: '',
      reason: '',
    },
    init() {
      this.loadItems();
      this._onOpen = (event) => this.openForm(event.detail || null);
      window.addEventListener('open-correction-modal', this._onOpen);
    },
    destroy() {
      this._requestId++;
      window.removeEventListener('open-correction-modal', this._onOpen);
    },
    async loadItems() {
      const requestId = ++this._requestId;
      this.loading = true;
      this.error = '';
      try {
        const params = new URLSearchParams({
          scope: this.scope,
          page: this.currentPage,
          per_page: 15,
        });
        if (this.statusFilter) params.set('status', this.statusFilter);
        const result = await apiFetch('/api/attendance-corrections?' + params);
        if (requestId !== this._requestId) return;
        this.items = result.data || [];
        this.total = result.total || 0;
        this.totalPages = result.last_page || 1;
        if (this.total && this.currentPage > this.totalPages) {
          this.currentPage = this.totalPages;
          return this.loadItems();
        }
      } catch (error) {
        if (requestId === this._requestId) this.error = error.message;
      } finally {
        if (requestId === this._requestId) this.loading = false;
      }
    },
    filterItems() {
      this.currentPage = 1;
      this.loadItems();
    },
    page(delta) {
      const page = this.currentPage + delta;
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadItems();
      }
    },
    openForm(item = null) {
      if (item && String(item.user_id) !== String(currentUserId)) return;
      this.formError = '';
      const date = item?.date || localDate();
      let checkoutDate = date;
      if (item?.check_in && item?.check_out && item.check_out < item.check_in) {
        const next = new Date(date + 'T12:00:00');
        next.setDate(next.getDate() + 1);
        checkoutDate = localDate(next);
      }
      this.form = {
        attendance_id: item?.id || '',
        date,
        check_in_time: item?.check_in ? date + 'T' + item.check_in.slice(0, 5) : '',
        check_out_time: item?.check_out ? checkoutDate + 'T' + item.check_out.slice(0, 5) : '',
        attendance_status: item?.status || 'Present',
        notes: item?.notes || '',
        reason: '',
      };
      getModal('#correctionModal')?.show();
    },
    closeForm() {
      getModal('#correctionModal')?.hide();
    },
    async submit() {
      if (this.saving) return;
      this.saving = true;
      this.formError = '';
      try {
        const payload = { ...this.form, attendance_id: this.form.attendance_id || null };
        const result = await apiFetch('/api/attendance-corrections', {
          method: 'POST',
          body: JSON.stringify(payload),
        });
        getModal('#correctionModal')?.hide();
        showToast(result.message);
        this.loadItems();
      } catch (error) {
        this.formError = error.message;
      } finally {
        this.saving = false;
      }
    },
    async review(item, status) {
      if (this.saving || !canReview || !item.can_review) return;
      const result = await Swal.fire({
        title: status === 'Approved' ? 'Approve correction?' : 'Reject correction?',
        text:
          status === 'Approved'
            ? 'This applies the proposed values to attendance.'
            : 'Attendance will remain unchanged.',
        input: 'textarea',
        inputLabel: 'Reviewer comment (optional)',
        inputAttributes: { maxlength: 5000 },
        showCancelButton: true,
      });
      if (!result.isConfirmed) return;
      this.saving = true;
      try {
        const response = await apiFetch(`/api/attendance-corrections/${item.id}/review`, {
          method: 'PATCH',
          body: JSON.stringify({ status, reviewer_comment: result.value || '' }),
        });
        showToast(response.message);
        this.loadItems();
        window.dispatchEvent(new CustomEvent('attendance-saved'));
      } catch (error) {
        this.error = error.message;
      } finally {
        this.saving = false;
      }
    },
  }));

  Alpine.data('leaveForm', (currentUserId = '') => ({
    editingId: null,
    originalLeaveType: null,
    saving: false,
    error: null,
    users: [],
    userBalances: [],
    isLoadingBalances: false,
    _balanceRequestId: 0,
    form: {
      user_id: currentUserId,
      leave_type: '',
      start_date: '',
      end_date: '',
      reason: '',
      status: 'Pending',
    },

    init() {
      this.loadUsers();
      window.addEventListener('open-leave-modal', (e) => {
        this.error = null;
        if (e.detail) {
          this.editingId = e.detail.id;
          this.originalLeaveType = e.detail.leave_type;
          this.form.user_id = e.detail.user_id;
          this.form.leave_type = e.detail.leave_type;
          this.form.start_date = e.detail.start_date
            ? e.detail.start_date.split('T')[0].split(' ')[0]
            : '';
          this.form.end_date = e.detail.end_date
            ? e.detail.end_date.split('T')[0].split(' ')[0]
            : '';
          this.form.reason = e.detail.reason;
          this.form.status = e.detail.status;
        } else {
          this.editingId = null;
          this.originalLeaveType = null;
          this.form.user_id = currentUserId;
          this.form.leave_type = '';
          this.form.start_date = '';
          this.form.end_date = '';
          this.form.reason = '';
          this.form.status = 'Pending';
        }
        this.fetchBalances(this.form.user_id);
        getModal('#leaveModal')?.show();
      });

      this.$watch('form.user_id', (value) => {
        this.fetchBalances(value);
      });

      this.$watch('users', () => {
        if (!this.editingId && currentUserId) {
          this.$nextTick(() => {
            this.form.user_id = currentUserId;
          });
        }
      });
    },

    async fetchBalances(userId) {
      const requestId = (this._balanceRequestId = (this._balanceRequestId || 0) + 1);
      const prevType = this.form.leave_type;
      this.userBalances = [];
      if (!userId) {
        this.isLoadingBalances = false;
        return;
      }

      this.isLoadingBalances = true;
      try {
        const res = await apiFetch(
          `/api/leave-balances?user_id=${userId}&is_active=1&per_page=100`
        );
        if (requestId !== this._balanceRequestId) return;
        if (res && res.data) {
          this.userBalances = res.data;
          this.$nextTick(() => {
            if (prevType) this.form.leave_type = prevType;
          });
        }
      } catch (e) {
        if (requestId === this._balanceRequestId) this.error = e.message;
      }
      if (requestId === this._balanceRequestId) this.isLoadingBalances = false;
    },

    async loadUsers() {
      this.users = await loadUserOptions(currentUserId);
    },

    async saveItem() {
      if (this.saving) return;
      this.saving = true;
      this.error = null;

      try {
        const method = this.editingId ? 'PUT' : 'POST';
        const url = this.editingId ? `/api/leaves/${this.editingId}` : '/api/leaves';

        await apiFetch(url, {
          method: method,
          body: JSON.stringify(this.form),
        });

        window.dispatchEvent(new CustomEvent('leave-saved'));
        showToast('Leave requested successfully', 'success');
        getModal('#leaveModal')?.hide();
        window.dispatchEvent(new CustomEvent('attendance-saved'));
      } catch (e) {
        this.error = e.message || 'Validation error.';
      }
      this.saving = false;
    },
  }));

  Alpine.data('attendanceForm', (currentUserId = '') => ({
    editingId: null,
    error: null,
    saving: false,
    users: [],
    form: {
      user_id: currentUserId,
      date: localDate(),
      check_in_time: '',
      check_out_time: '',
      status: 'Present',
      notes: '',
    },

    init() {
      this.loadUsers();
      window.addEventListener('open-attendance-modal', (e) => {
        this.error = null;
        if (e.detail?.id) {
          this.editingId = e.detail.id;
          this.form.user_id = e.detail.user_id;

          const dateStr = e.detail.date ? e.detail.date.split('T')[0] : '';
          this.form.date = dateStr;

          this.form.check_in_time = e.detail.check_in
            ? `${dateStr}T${e.detail.check_in.slice(0, 5)}`
            : '';
          let checkoutDate = dateStr;
          if (e.detail.check_in && e.detail.check_out && e.detail.check_out < e.detail.check_in) {
            const next = new Date(`${dateStr}T12:00:00`);
            next.setDate(next.getDate() + 1);
            checkoutDate = localDate(next);
          }
          this.form.check_out_time = e.detail.check_out
            ? `${checkoutDate}T${e.detail.check_out.slice(0, 5)}`
            : '';
          this.form.status = e.detail.status;
          this.form.notes = e.detail.notes || '';

          const title = document.querySelector('#attendanceModalLabel');
          if (title)
            title.innerHTML =
              '<i class="bi bi-calendar-check-fill text-primary me-2"></i>Edit Attendance';
        } else {
          this.resetForm();
          if (e.detail?.date) this.form.date = e.detail.date;
          if (e.detail?.user_id) this.form.user_id = e.detail.user_id;
          const title = document.querySelector('#attendanceModalLabel');
          if (title)
            title.innerHTML =
              '<i class="bi bi-calendar-check-fill text-primary me-2"></i>Log Attendance';
        }
        getModal('#attendanceModal')?.show();
      });

      this.$watch('users', () => {
        if (!this.editingId && currentUserId) {
          this.$nextTick(() => {
            this.form.user_id = currentUserId;
          });
        }
      });
    },

    resetForm() {
      this.editingId = null;
      this.error = null;
      this.form = {
        user_id: currentUserId,
        date: localDate(),
        check_in_time: '',
        check_out_time: '',
        status: 'Present',
        notes: '',
      };
      this.saving = false;
    },

    async loadUsers() {
      this.users = await loadUserOptions(currentUserId);
    },

    async saveItem() {
      if (this.saving) return;
      this.saving = true;
      try {
        const method = this.editingId ? 'PUT' : 'POST';
        const url = this.editingId ? `/api/attendances/${this.editingId}` : '/api/attendances';
        const res = await apiFetch(url, {
          method: method,
          body: JSON.stringify(this.form),
        });
        showToast(res.message, 'success');
        window.dispatchEvent(new CustomEvent('attendance-saved'));
        getModal('#attendanceModal')?.hide();
        this.resetForm();
      } catch (err) {
        this.error = err.message;
        showToast(err.message, 'danger');
      } finally {
        this.saving = false;
      }
    },
  }));
});
