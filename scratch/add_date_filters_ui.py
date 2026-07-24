import re

with open('/home/user/metis/resources/views/dashboard.blade.php', 'r') as f:
    content = f.read()

header_find = """    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <ul class="nav nav-pills gap-2" role="tablist">
            <li class="nav-item">"""
            
header_replace = """    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <ul class="nav nav-pills gap-2" role="tablist">
            <li class="nav-item">"""

dropdown_html = """
        <div x-show="activeTab === 'dashboard'" x-transition.opacity.duration.300ms class="d-flex align-items-center gap-2">
            <label class="text-muted small fw-bold mb-0 text-nowrap"><i class="bi bi-calendar3 me-1"></i> Date Filter:</label>
            <select class="form-select form-select-sm fw-semibold shadow-sm border-0 bg-body-tertiary rounded-pill px-3" style="min-width: 140px; cursor: pointer;" onchange="window.location.href = '?filter=' + this.value">
                <option value="all" {{ ($filter ?? 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ ($filter ?? 'all') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ ($filter ?? 'all') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ ($filter ?? 'all') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ ($filter ?? 'all') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="this_year" {{ ($filter ?? 'all') === 'this_year' ? 'selected' : '' }}>This Year</option>
            </select>
        </div>
"""

# inject dropdown before the closing div of the header
content = re.sub(
    r"(        </ul>\n\n    </div>)",
    dropdown_html + r"\1",
    content
)

with open('/home/user/metis/resources/views/dashboard.blade.php', 'w') as f:
    f.write(content)
