import re

with open('/home/user/metis/resources/views/users/index.blade.php', 'r') as f:
    content = f.read()

pattern = r"""                            <template x-if="\!rolesLoading && availablePermissions\.length > 0">
                                <div class="row g-2 border rounded p-2 bg-body-tertiary" style="max-height: 250px; overflow-y: auto;">
                                    <template x-for="perm in availablePermissions" :key="perm\.id">
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" :value="perm\.name" :id="'perm_' \+ perm\.id" x-model="form\.permissions">
                                                <label class="form-check-label text-truncate w-100" style="font-size: 0\.85rem;" :for="'perm_' \+ perm\.id" x-text="perm\.name" :title="perm\.name"></label>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>"""

replacement = """                            <template x-if="!rolesLoading && availablePermissions.length > 0">
                                <div class="border rounded p-3 bg-body-tertiary" style="max-height: 400px; overflow-y: auto;">
                                    <template x-for="group in groupedAvailablePermissions" :key="group.key">
                                        <div class="mb-4 bg-body border rounded p-3 shadow-sm">
                                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                                <i class="bi fs-5 text-primary me-2" :class="`bi-${group.icon}`"></i>
                                                <h6 class="mb-0 fw-bold text-body" x-text="group.label"></h6>
                                            </div>
                                            <template x-for="(subGroup, index) in group.subGroups" :key="index">
                                                <div :class="index > 0 ? 'mt-3 pt-3 border-top border-light-subtle' : ''">
                                                    <h6 class="fw-semibold text-muted small mb-2 text-uppercase" style="letter-spacing: 0.05em;" x-text="subGroup.label"></h6>
                                                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-2 w-100 m-0">
                                                        <template x-for="perm in subGroup.items" :key="perm.id">
                                                            <div class="col px-1">
                                                                <div class="form-check d-flex align-items-start mb-0">
                                                                    <input class="form-check-input shadow-sm flex-shrink-0" type="checkbox" :value="perm.name" :id="'perm_' + perm.id" x-model="form.permissions" style="margin-top: 0.2em;">
                                                                    <label class="form-check-label ms-2 cursor-pointer w-100" :for="'perm_' + perm.id">
                                                                        <span class="fw-medium text-body d-block" style="font-size: 0.85rem;" x-text="perm.actionLabel"></span>
                                                                        <span class="text-muted d-block" style="font-size: 0.7rem; line-height: 1.1;" x-text="perm.name"></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>"""

content = re.sub(pattern, replacement, content, flags=re.DOTALL)

with open('/home/user/metis/resources/views/users/index.blade.php', 'w') as f:
    f.write(content)
