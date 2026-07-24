import re

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'r') as f:
    content = f.read()

utilities = """
        // ── Utilities & Tools ──

        // Chat
        'chat-view',
        'chat-create',
        'chat-edit',
        'chat-delete',
        'chat-restore',
        'chat-permanent-delete',

        // Messages
        'messages-view',
        'messages-create',
        'messages-edit',
        'messages-delete',
        'messages-restore',
        'messages-permanent-delete',

        // Calendar
        'calendar-view',
        'calendar-create',
        'calendar-edit',
        'calendar-delete',
        'calendar-restore',
        'calendar-permanent-delete',

        // Files
        'files-view',
        'files-create',
        'files-edit',
        'files-delete',
        'files-restore',
        'files-permanent-delete',

        // Forms
        'forms-view',
        'forms-create',
        'forms-edit',
        'forms-delete',
        'forms-restore',
        'forms-permanent-delete',

        // Security
        'security-view',
        'security-create',
        'security-edit',
        'security-delete',
        'security-restore',
        'security-permanent-delete',

        // Help
        'help-view',
        'help-create',
        'help-edit',
        'help-delete',
        'help-restore',
        'help-permanent-delete',
"""

pattern = r"(        // Dashboards & Reports)"
replacement = utilities + r"\n\1"

content = re.sub(pattern, replacement, content)

with open('/home/user/metis/database/seeders/RolesAndPermissionsSeeder.php', 'w') as f:
    f.write(content)
