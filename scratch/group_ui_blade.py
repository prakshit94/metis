import re

with open('/home/user/metis/resources/views/users/roles-permissions.blade.php', 'r') as f:
    content = f.read()

# Replace the single list of permissions with nested `subGroups`
pattern = r"""                                                            <td class="py-3 pe-3">
                                                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3 w-100 m-0">
                                                                    <template x-for="permission in group.items" :key="permission.id">
                                                                        <div class="col px-1">
                                                                            <div class="mb-0 h-100 d-flex align-items-start">
                                                                                <input class="user-select-checkbox cursor-pointer flex-shrink-0 shadow-sm" 
                                                                                       type="checkbox" 
                                                                                       :id="`perm-\$\{permission\.id\}`" 
                                                                                       :value="permission\.name" 
                                                                                       x-model="form\.permissions"
                                                                                       style="width: 1\.2em; height: 1\.2em; margin-top: 0\.15em;">
                                                                                <label class="w-100 cursor-pointer ms-2" :for="`perm-\$\{permission\.id\}`">
                                                                                    <span class="fw-medium text-body d-block" x-text="permission\.actionLabel"></span>
                                                                                    <span class="text-muted" style="font-size: 0\.7rem; word-break: break-all;" x-text="permission\.name"></span>
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </td>"""

replacement = """                                                            <td class="py-3 pe-3">
                                                                <template x-for="(subGroup, index) in group.subGroups" :key="index">
                                                                    <div :class="index > 0 ? 'mt-3 pt-3 border-top border-light-subtle' : ''">
                                                                        <h6 class="fw-semibold text-muted small mb-2 text-uppercase" style="letter-spacing: 0.05em;" x-text="subGroup.label"></h6>
                                                                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3 w-100 m-0">
                                                                            <template x-for="permission in subGroup.items" :key="permission.id">
                                                                                <div class="col px-1">
                                                                                    <div class="mb-0 h-100 d-flex align-items-start">
                                                                                        <input class="user-select-checkbox cursor-pointer flex-shrink-0 shadow-sm" 
                                                                                               type="checkbox" 
                                                                                               :id="`perm-${permission.id}`" 
                                                                                               :value="permission.name" 
                                                                                               x-model="form.permissions"
                                                                                               style="width: 1.2em; height: 1.2em; margin-top: 0.15em;">
                                                                                        <label class="w-100 cursor-pointer ms-2" :for="`perm-${permission.id}`">
                                                                                            <span class="fw-medium text-body d-block" x-text="permission.actionLabel"></span>
                                                                                            <span class="text-muted" style="font-size: 0.7rem; word-break: break-all;" x-text="permission.name"></span>
                                                                                        </label>
                                                                                    </div>
                                                                                </div>
                                                                            </template>
                                                                        </div>
                                                                    </div>
                                                                </template>
                                                            </td>"""

content = re.sub(pattern, replacement, content)

with open('/home/user/metis/resources/views/users/roles-permissions.blade.php', 'w') as f:
    f.write(content)
