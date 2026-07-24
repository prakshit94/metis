import re

with open('/home/user/metis/resources/views/users/roles-permissions.blade.php', 'r') as f:
    content = f.read()

# Replace the single list of permissions with nested `subGroups` for the detail view
pattern = r"""                                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                                        <template x-for="entry in group\.items" :key="entry\.id">
                                                            <span class="badge bg-light text-dark border fw-normal" x-text="entry\.actionLabel \|\| entry\.name"></span>
                                                        </template>
                                                    </div>"""

replacement = """                                                    <div class="mt-3">
                                                        <template x-for="(subGroup, index) in group.subGroups" :key="index">
                                                            <div :class="index > 0 ? 'mt-2 pt-2 border-top border-light-subtle' : ''">
                                                                <h6 class="fw-semibold text-muted mb-1" style="font-size: 0.75rem; letter-spacing: 0.05em;" x-text="subGroup.label"></h6>
                                                                <div class="d-flex flex-wrap gap-1">
                                                                    <template x-for="entry in subGroup.items" :key="entry.id">
                                                                        <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.7rem;" x-text="entry.actionLabel || entry.name"></span>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>"""

content = re.sub(pattern, replacement, content)

with open('/home/user/metis/resources/views/users/roles-permissions.blade.php', 'w') as f:
    f.write(content)
