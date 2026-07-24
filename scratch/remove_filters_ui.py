import re

with open('/home/user/metis/resources/views/dashboard.blade.php', 'r') as f:
    content = f.read()

# Replace the old select block
old_select = """            <select class="form-select form-select-sm fw-semibold shadow-sm border-0 bg-body-tertiary rounded-pill px-3" style="min-width: 140px; cursor: pointer;" onchange="window.location.href = '?filter=' + this.value">
                <option value="all" {{ ($filter ?? 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ ($filter ?? 'all') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ ($filter ?? 'all') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ ($filter ?? 'all') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ ($filter ?? 'all') === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="this_year" {{ ($filter ?? 'all') === 'this_year' ? 'selected' : '' }}>This Year</option>
            </select>"""

new_select = """            <select class="form-select form-select-sm fw-semibold shadow-sm border-0 bg-body-tertiary rounded-pill px-3" style="min-width: 140px; cursor: pointer;" onchange="window.location.href = '?filter=' + this.value">
                <option value="today" {{ ($filter ?? 'today') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ ($filter ?? 'today') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ ($filter ?? 'today') === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="this_month" {{ ($filter ?? 'today') === 'this_month' ? 'selected' : '' }}>This Month</option>
            </select>"""

content = content.replace(old_select, new_select)

with open('/home/user/metis/resources/views/dashboard.blade.php', 'w') as f:
    f.write(content)
