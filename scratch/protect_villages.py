import re

with open('/home/user/metis/resources/views/villages/index.blade.php', 'r') as f:
    content = f.read()

# 1. Main action buttons at top
content = re.sub(
    r'(<button type="button" class="btn btn-outline-secondary" @click="exportVillages\(\)">\s*<i class="bi bi-download me-2"></i>Export\s*</button>)',
    r"@can('village-export')\n            \1\n            @endcan",
    content
)

content = re.sub(
    r'(<div class="dropdown d-inline-block">\s*<button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">\s*<i class="bi bi-upload me-2"></i>Import\s*</button>[\s\S]*?</div>)',
    r"@can('village-import')\n            \1\n            @endcan",
    content
)

content = re.sub(
    r'(<button type="button" class="btn btn-primary" @click="openCreateVillage\(\)">\s*<i class="bi bi-plus-lg me-2"></i>Add Village\s*</button>)',
    r"@can('village-create')\n            \1\n            @endcan",
    content
)

# 2. Bulk Action Buttons
content = re.sub(
    r'(<button class="btn btn-sm btn-outline-primary" @click="exportSelectedVillages\(\)" title="Export Selected to CSV">\s*<i class="bi bi-download"></i>\s*</button>)',
    r"@can('village-export')\n                        \1\n                        @endcan",
    content
)

content = re.sub(
    r'(<button class="btn btn-sm btn-success" @click="openBulkServiceModal\(\)" x-show="!hasSelectedDeletedVillages">\s*<i class="bi bi-gear-wide-connected me-1"></i>Services\s*</button>)',
    r"@can('village-edit')\n                        \1\n                        @endcan",
    content
)

content = re.sub(
    r'(<button class="btn btn-sm btn-danger" @click="bulkAction\(\'delete\'\)" x-show="!hasSelectedDeletedVillages">\s*<i class="bi bi-trash"></i>\s*</button>)',
    r"@can('village-delete')\n                        \1\n                        @endcan",
    content
)

content = re.sub(
    r'(<button class="btn btn-sm btn-success" @click="bulkAction\(\'restore\'\)" x-show="hasSelectedDeletedVillages">\s*<i class="bi bi-arrow-counterclockwise"></i>\s*</button>)',
    r"@can('village-restore')\n                        \1\n                        @endcan",
    content
)

content = re.sub(
    r'(<button class="btn btn-sm btn-danger" @click="bulkAction\(\'force-delete\'\)" x-show="hasSelectedDeletedVillages">\s*<i class="bi bi-trash-fill"></i>\s*</button>)',
    r"@can('village-permanent-delete')\n                        \1\n                        @endcan",
    content
)


# 3. Individual Row Actions (dropdown items)
content = re.sub(
    r'(<li><a class="dropdown-item" href="#" @click\.prevent="openEditVillage\(village\)">\s*<i class="bi bi-pencil me-2"></i>Edit\s*</a></li>)',
    r"@can('village-edit')\n                                                \1\n                                                @endcan",
    content
)

content = re.sub(
    r'(<li><a class="dropdown-item" href="#" @click\.prevent="openServiceModal\(village\)">\s*<i class="bi bi-gear-wide-connected me-2"></i>Services\s*</a></li>)',
    r"@can('village-edit')\n                                                \1\n                                                @endcan",
    content
)

content = re.sub(
    r'(<li><a class="dropdown-item text-danger" href="#" @click\.prevent="deleteVillage\(village\.id\)">\s*<i class="bi bi-trash me-2"></i>Delete\s*</a></li>)',
    r"@can('village-delete')\n                                                \1\n                                                @endcan",
    content
)

content = re.sub(
    r'(<li><a class="dropdown-item text-success" href="#" @click\.prevent="restoreVillage\(village\.id\)">\s*<i class="bi bi-arrow-counterclockwise me-2"></i>Restore\s*</a></li>)',
    r"@can('village-restore')\n                                                \1\n                                                @endcan",
    content
)

content = re.sub(
    r'(<li><a class="dropdown-item text-danger fw-bold" href="#" @click\.prevent="forceDeleteVillage\(village\.id\)">\s*<i class="bi bi-trash-fill me-2"></i>Force Delete\s*</a></li>)',
    r"@can('village-permanent-delete')\n                                                \1\n                                                @endcan",
    content
)


with open('/home/user/metis/resources/views/villages/index.blade.php', 'w') as f:
    f.write(content)
