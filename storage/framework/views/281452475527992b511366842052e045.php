
<?php if (isset($component)) { $__componentOriginal7762953202be6518eecd1cfbd075bf2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7762953202be6518eecd1cfbd075bf2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.modal','data' => ['id' => 'address-modal','maxWidth' => '2xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'address-modal','maxWidth' => '2xl']); ?>
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-primary/10 border border-primary/20 text-primary flex items-center justify-center shadow-inner">
                    <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'map-pin','size' => '5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'map-pin','size' => '5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                </div>
                <div>
                    <h3 class="text-sm font-black text-foreground uppercase tracking-widest" x-text="(editingAddress && editingAddress.id) ? 'Edit Address' : 'Add New Address'"></h3>
                    <p class="text-[10px] text-muted-foreground font-bold tracking-tight">Manage customer registered addresses</p>
                </div>
            </div>
            <button type="button" @click="$dispatch('close-modal', { name: 'address-modal' })" class="size-8 rounded-lg hover:bg-muted flex items-center justify-center transition-colors">
                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'x','size' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
            </button>
        </div>

        <form :action="(editingAddress && editingAddress.id) ? `/customers/<?php echo e($customer->id); ?>/addresses/${editingAddress.id}` : `/customers/<?php echo e($customer->id); ?>/addresses`" method="POST" class="space-y-4">
            <?php echo csrf_field(); ?>
            <template x-if="editingAddress && editingAddress.id">
                <?php echo method_field('PUT'); ?>
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="label" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">Address Label</label>
                    <select name="label" id="label" x-model="editingAddress.label" required 
                        class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 text-sm font-medium focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all outline-none">
                        <option value="Home">Home</option>
                        <option value="Shop">Shop</option>
                        <option value="Office">Office</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="status" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">Status</label>
                    <select name="status" id="status" x-model="editingAddress.status" class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 text-[10px] font-black uppercase tracking-widest focus:bg-background focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2 relative">
                <label for="villageSearch" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">Search Village / Area (Optional)</label>
                <div class="relative">
                    <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'search','size' => '4','class' => 'absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','size' => '4','class' => 'absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                    <input type="text" id="villageSearch" x-model="villageSearch" @input.debounce.500ms="searchVillages" placeholder="Search by village name or pincode..." autocomplete="off"
                        class="w-full h-11 pl-9 pr-4 rounded-xl border border-border bg-background/50 focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all text-sm font-medium outline-none">
                    <div x-show="searchingVillages" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2">
                        <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'refresh-cw','size' => '4','class' => 'animate-spin text-primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'refresh-cw','size' => '4','class' => 'animate-spin text-primary']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                    </div>
                </div>
                
                <div x-show="villages.length > 0" x-cloak @click.away="villages = []" class="absolute z-50 w-full mt-1 bg-card border border-border rounded-xl shadow-lg shadow-primary/5 max-h-60 overflow-y-auto backdrop-blur-xl">
                    <template x-for="village in villages" :key="village.id">
                        <div @click="selectVillage(village)" class="p-3 border-b border-border/40 hover:bg-primary/5 cursor-pointer transition-colors last:border-0 group">
                            <p class="text-sm font-bold text-foreground group-hover:text-primary transition-colors" x-text="village.name"></p>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-widest mt-0.5">
                                <span x-text="village.post_office"></span>, <span x-text="village.taluka"></span>, <span x-text="village.district"></span> - <span x-text="village.pincode"></span>
                            </p>
                        </div>
                    </template>
                </div>
            </div>
            
            <input type="hidden" name="village_id" id="village_id" x-model="editingAddress.village_id">

            <div class="space-y-2">
                <label for="address_line_1" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">Address Line 1</label>
                <input type="text" name="address_line_1" id="address_line_1" x-model="editingAddress.address_line_1" required 
                    class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all text-sm font-medium outline-none">
            </div>

            <div class="space-y-2">
                <label for="address_line_2" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">Address Line 2 (Optional)</label>
                <input type="text" name="address_line_2" id="address_line_2" x-model="editingAddress.address_line_2" 
                    class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all text-sm font-medium outline-none">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label for="village_name" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">Village</label>
                    <input type="text" name="village_name" id="village_name" x-model="editingAddress.village_name" 
                        class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all text-sm font-medium outline-none">
                </div>
                <div class="space-y-2">
                    <label for="post_office" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">Post Office</label>
                    <input type="text" name="post_office" id="post_office" x-model="editingAddress.post_office" 
                        class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all text-sm font-medium outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="space-y-2">
                    <label for="taluka" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">Taluka</label>
                    <input type="text" name="taluka" id="taluka" x-model="editingAddress.taluka" 
                        class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all text-sm font-medium outline-none">
                </div>
                <div class="space-y-2">
                    <label for="city" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">District/City</label>
                    <input type="text" name="city" id="city" x-model="editingAddress.city" required 
                        class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all text-sm font-medium outline-none">
                </div>
                <div class="space-y-2">
                    <label for="state" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">State</label>
                    <input type="text" name="state" id="state" x-model="editingAddress.state" required 
                        class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all text-sm font-medium outline-none">
                </div>
                <div class="space-y-2">
                    <label for="pincode" class="text-[10px] font-black uppercase tracking-widest text-muted-foreground/80 ml-1">Pincode</label>
                    <input type="text" name="pincode" id="pincode" x-model="editingAddress.pincode" required 
                        class="w-full h-11 px-4 rounded-xl border border-border bg-background/50 focus:bg-background focus:ring-2 focus:ring-primary/20 transition-all text-sm font-medium outline-none">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2 ml-1">
                <input type="checkbox" name="is_default" id="is_default" value="1" x-model="editingAddress.is_default"
                    class="rounded border-border text-primary focus:ring-primary/20">
                <label for="is_default" class="text-xs font-medium text-foreground cursor-pointer">Set as default address</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-border/40">
                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'button','variant' => 'outline','@click' => '$dispatch(\'close-modal\', { name: \'address-modal\' })','class' => 'rounded-xl font-black uppercase tracking-widest text-[10px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'outline','@click' => '$dispatch(\'close-modal\', { name: \'address-modal\' })','class' => 'rounded-xl font-black uppercase tracking-widest text-[10px]']); ?>
                    Cancel
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','class' => 'rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-primary/20']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','class' => 'rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-primary/20']); ?>
                    Save Address
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
            </div>
        </form>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7762953202be6518eecd1cfbd075bf2f)): ?>
<?php $attributes = $__attributesOriginal7762953202be6518eecd1cfbd075bf2f; ?>
<?php unset($__attributesOriginal7762953202be6518eecd1cfbd075bf2f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7762953202be6518eecd1cfbd075bf2f)): ?>
<?php $component = $__componentOriginal7762953202be6518eecd1cfbd075bf2f; ?>
<?php unset($__componentOriginal7762953202be6518eecd1cfbd075bf2f); ?>
<?php endif; ?>


<?php if (isset($component)) { $__componentOriginal7762953202be6518eecd1cfbd075bf2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7762953202be6518eecd1cfbd075bf2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.modal','data' => ['id' => 'delete-address-modal','maxWidth' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'delete-address-modal','maxWidth' => 'sm']); ?>
    <div class="p-8 text-center">
        <div class="size-16 rounded-full bg-destructive/10 text-destructive flex items-center justify-center mx-auto mb-4">
            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'alert-triangle','size' => '8']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'alert-triangle','size' => '8']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
        </div>
        <h3 class="text-lg font-black text-foreground mb-2">Delete Address?</h3>
        <p class="text-sm text-muted-foreground mb-6">Are you sure you want to delete <span class="font-bold text-foreground" x-text="deletingAddress?.label || 'this address'"></span>? This action cannot be undone.</p>
        
        <form :action="(deletingAddress && deletingAddress.id) ? `/customers/<?php echo e($customer->id); ?>/addresses/${deletingAddress.id}` : '#'" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'button','variant' => 'outline','@click' => '$dispatch(\'close-modal\', { name: \'delete-address-modal\' })','class' => 'w-full sm:w-auto rounded-xl font-black uppercase tracking-widest text-[10px]']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','variant' => 'outline','@click' => '$dispatch(\'close-modal\', { name: \'delete-address-modal\' })','class' => 'w-full sm:w-auto rounded-xl font-black uppercase tracking-widest text-[10px]']); ?>
                    Cancel
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'destructive','class' => 'w-full sm:w-auto rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-destructive/20 bg-destructive text-destructive-foreground hover:bg-destructive/90']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'destructive','class' => 'w-full sm:w-auto rounded-xl font-black uppercase tracking-widest text-[10px] shadow-lg shadow-destructive/20 bg-destructive text-destructive-foreground hover:bg-destructive/90']); ?>
                    Delete Address
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
            </div>
        </form>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7762953202be6518eecd1cfbd075bf2f)): ?>
<?php $attributes = $__attributesOriginal7762953202be6518eecd1cfbd075bf2f; ?>
<?php unset($__attributesOriginal7762953202be6518eecd1cfbd075bf2f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7762953202be6518eecd1cfbd075bf2f)): ?>
<?php $component = $__componentOriginal7762953202be6518eecd1cfbd075bf2f; ?>
<?php unset($__componentOriginal7762953202be6518eecd1cfbd075bf2f); ?>
<?php endif; ?>



<div x-show="isOffersModalOpen" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="isOffersModalOpen = false"
     class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[110]">
</div>


<div x-show="isOffersModalOpen" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
     x-transition:leave-end="opacity-0 scale-95 translate-y-4"
     class="fixed inset-0 z-[115] flex items-center justify-center p-4">

    <div class="w-full max-w-lg bg-card border border-border/50 rounded-[2rem] shadow-2xl overflow-hidden flex flex-col max-h-[80vh]">

        
        <div class="flex items-center justify-between px-6 py-5 border-b border-border/40 bg-gradient-to-r from-amber-500/10 via-card to-card shrink-0">
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 flex items-center justify-center shadow-inner">
                    <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'gift','size' => '5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'gift','size' => '5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                </div>
                <div>
                    <h3 class="text-sm font-black text-foreground tracking-tight">Available Offers</h3>
                    <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest"
                       x-text="availableOrderOffers.length + ' offer(s) for your cart'"></p>
                </div>
            </div>
            <button type="button" @click="isOffersModalOpen = false"
                class="size-9 rounded-xl bg-muted hover:bg-muted/80 flex items-center justify-center text-muted-foreground hover:text-foreground transition-all">
                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'x','size' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
            </button>
        </div>

        
        <div class="flex-1 overflow-y-auto p-5 space-y-3" style="scrollbar-width:thin;">

            
            <template x-if="availableOrderOffers.length === 0">
                <div class="flex flex-col items-center justify-center h-48 text-center gap-3 opacity-50">
                    <div class="size-16 rounded-3xl bg-muted flex items-center justify-center">
                        <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'tag','size' => '8','class' => 'text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'tag','size' => '8','class' => 'text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                    </div>
                    <p class="text-sm font-black uppercase tracking-widest text-foreground">No offers available</p>
                    <p class="text-xs text-muted-foreground">Add more items to unlock backend offers.</p>
                </div>
            </template>

            
            <template x-for="offer in availableOrderOffers" :key="offer.id">
                <div class="relative rounded-2xl border-2 p-5 transition-all duration-200 cursor-pointer group"
                     :class="appliedOrderOfferId === offer.id
                         ? 'border-emerald-500 bg-emerald-500/5'
                         : 'border-border/50 bg-muted/10 hover:border-primary/40 hover:bg-primary/[0.02]'">

                    <div class="flex items-start gap-4">
                        
                        <div class="size-10 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                             :class="appliedOrderOfferId === offer.id ? 'bg-emerald-500/20 text-emerald-600' : 'bg-amber-500/10 text-amber-600'">
                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'percent','size' => '5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'percent','size' => '5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                        </div>

                        
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-sm font-black text-foreground" x-text="offer.name"></p>
                                <template x-if="offer.priority > 0">
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20 uppercase tracking-widest"
                                          x-text="'Priority ' + offer.priority"></span>
                                </template>
                                <template x-if="appliedOrderOfferId === offer.id">
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-700 border border-emerald-500/30 uppercase tracking-widest flex items-center gap-1">
                                        <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'check','size' => '2.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check','size' => '2.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?> Applied
                                    </span>
                                </template>
                            </div>

                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                                
                                <p class="text-xs text-muted-foreground font-medium">
                                    <span class="font-black text-foreground"
                                          x-text="offer.discount_type === 'percentage'
                                              ? offer.value + '% off'
                                              : 'Rs ' + Number(offer.value).toFixed(2) + ' flat off'">
                                    </span>
                                </p>
                                
                                <template x-if="(parseFloat(offer.min_spend) || 0) > 0">
                                    <p class="text-xs text-muted-foreground font-medium">
                                        Min spend: <span class="font-black" x-text="'Rs ' + Number(offer.min_spend).toFixed(2)"></span>
                                    </p>
                                </template>
                                
                                <template x-if="(parseFloat(offer.max_discount) || 0) > 0">
                                    <p class="text-xs text-muted-foreground font-medium">
                                        Max discount: <span class="font-black" x-text="'Rs ' + Number(offer.max_discount).toFixed(2)"></span>
                                    </p>
                                </template>
                            </div>

                            
                            <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl"
                                 :class="appliedOrderOfferId === offer.id ? 'bg-emerald-500/20' : 'bg-amber-500/10'">
                                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'zap','size' => '3','class' => 'text-amber-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'zap','size' => '3','class' => 'text-amber-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                <span class="text-xs font-black"
                                      :class="appliedOrderOfferId === offer.id ? 'text-emerald-700' : 'text-amber-700'"
                                      x-text="'You save Rs ' + Number(offer.computed_discount).toFixed(2) + ' on this order'">
                                </span>
                            </div>
                        </div>

                        
                        <div class="shrink-0">
                            <template x-if="appliedOrderOfferId !== offer.id">
                                <button type="button" @click.stop="applyOrderOffer(offer.id)"
                                    class="h-9 px-4 rounded-xl bg-primary text-primary-foreground text-[10px] font-black uppercase tracking-widest shadow-lg shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 transition-all">
                                    Apply
                                </button>
                            </template>
                            <template x-if="appliedOrderOfferId === offer.id">
                                <button type="button" @click.stop="removeOrderOffer()"
                                    class="h-9 px-4 rounded-xl bg-destructive/10 text-destructive border border-destructive/20 text-[10px] font-black uppercase tracking-widest hover:bg-destructive/20 transition-all">
                                    Remove
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        
        <div class="px-6 py-4 border-t border-border/40 bg-muted/10 flex items-center justify-between shrink-0">
            <div x-show="bestOrderOffer" x-cloak class="flex items-center gap-2 text-emerald-600">
                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'check-circle','size' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check-circle','size' => '4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                <span class="text-xs font-black" x-text="'Applied: ' + (bestOrderOffer?.name ?? '')"></span>
                <span class="text-xs font-semibold text-emerald-700" x-text="'(- Rs ' + Number(orderDiscountAmount).toFixed(2) + ')'"></span>
            </div>
            <div x-show="!bestOrderOffer" class="text-[10px] text-muted-foreground font-semibold uppercase tracking-widest">
                No offer selected
            </div>
            <button type="button" @click="isOffersModalOpen = false"
                class="h-10 px-5 rounded-xl bg-primary text-primary-foreground text-[10px] font-black uppercase tracking-widest shadow-lg shadow-primary/20 hover:-translate-y-0.5 transition-all">
                Done
            </button>
        </div>
    </div>
</div>


<?php if (isset($component)) { $__componentOriginal7762953202be6518eecd1cfbd075bf2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7762953202be6518eecd1cfbd075bf2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.modal','data' => ['id' => 'edit-profile-modal','maxWidth' => '5xl']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'edit-profile-modal','maxWidth' => '5xl']); ?>
    <div class="p-0 overflow-hidden" x-data="{
        selectedSources: <?php echo e(json_encode(old('source', is_array($customer->source) ? $customer->source : []))); ?>,
        showSourceDropdown: false,
        sources: ['Referral', 'Walk-in', 'Social Media', 'Website', 'Advertisement', 'Event', 'Cold Call', 'Other'],
        toggleSource(name) {
            if(this.selectedSources.includes(name)) {
                this.selectedSources = this.selectedSources.filter(s => s !== name);
            } else {
                this.selectedSources.push(name);
            }
        },
        selectedIrrigation: <?php echo e(json_encode(old('irrigation_type', is_array($customer->irrigation_type) ? $customer->irrigation_type : []))); ?>,
        showIrrigationDropdown: false,
        types: <?php echo e(json_encode($irrigationTypes->map(fn($t) => $t->name))); ?>,
        toggleIrrigation(name) {
            if(this.selectedIrrigation.includes(name)) {
                this.selectedIrrigation = this.selectedIrrigation.filter(t => t !== name);
            } else {
                this.selectedIrrigation.push(name);
            }
        },
        selectedCrops: <?php echo e(json_encode(old('crops', is_array($customer->crops) ? $customer->crops : []))); ?>,
        showCropsDropdown: false,
        allCrops: <?php echo e(json_encode($crops->map(fn($c) => $c->name))); ?>,
        cropSearch: '',
        get filteredCrops() {
            if (!this.cropSearch) return this.allCrops;
            return this.allCrops.filter(c => c.toLowerCase().includes(this.cropSearch.toLowerCase()));
        },
        toggleCrop(name) {
            if(this.selectedCrops.includes(name)) {
                this.selectedCrops = this.selectedCrops.filter(c => c !== name);
            } else {
                this.selectedCrops.push(name);
            }
        }
    }">
        <div class="p-6 border-b border-border/40 bg-muted/10 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-2xl bg-gradient-to-tr from-primary/20 to-primary/5 border border-primary/10 text-primary flex items-center justify-center shadow-inner font-black text-lg">
                    <?php echo e($customer->initials()); ?>

                </div>
                <div>
                    <h3 class="text-lg font-bold tracking-tight text-foreground">Edit Profile: <?php echo e($customer->name); ?></h3>
                    <!-- <p class="text-xs text-muted-foreground mt-0.5">Complete administrative & agricultural management</p> -->
                </div>
            </div>
            <button type="button" @click="$dispatch('close-modal', { name: 'edit-profile-modal' })" class="size-10 rounded-xl hover:bg-muted flex items-center justify-center transition-colors">
                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'x','size' => '5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
            </button>
        </div>

        <form action="<?php echo e(route('api.customers.update', $customer)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="p-8 space-y-12 max-h-[75vh] overflow-y-auto custom-scrollbar">

                
                <div class="relative">
                    <!-- <div class="flex items-center gap-3 pb-4 mb-8 border-b border-border/40">
                        <div class="size-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'user','size' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user','size' => '4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-foreground">Basic Identity</h4>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-wider font-bold mt-0.5">Personal and categorization details</p>
                        </div>
                    </div> -->

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">First Name *</label>
                            <div class="relative">
                                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'user','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                <input type="text" name="firstname" value="<?php echo e($customer->firstname); ?>" required class="w-full pl-11 pr-4 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                            </div>
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Middle Name</label>
                            <input type="text" name="middlename" value="<?php echo e($customer->middlename); ?>" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Last Name *</label>
                            <input type="text" name="lastname" value="<?php echo e($customer->lastname); ?>" required class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Account Status *</label>
                            <div class="relative">
                                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'activity','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground z-10']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'activity','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground z-10']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                <select name="status" class="w-full pl-11 pr-10 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none text-sm font-bold text-foreground">
                                    <option value="active" <?php echo e($customer->status === 'active' ? 'selected' : ''); ?>>Active</option>
                                    <option value="inactive" <?php echo e($customer->status === 'inactive' ? 'selected' : ''); ?>>Inactive</option>
                                    <option value="suspended" <?php echo e($customer->status === 'suspended' ? 'selected' : ''); ?>>Suspended</option>
                                </select>
                                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'chevron-down','size' => '4','class' => 'absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','size' => '4','class' => 'absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                            </div>
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Client Category</label>
                            <div class="relative">
                                <select name="category" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all appearance-none text-sm font-bold">
                                    <option value="">— Select —</option>
                                    <option value="individual" <?php echo e($customer->category === 'individual' ? 'selected' : ''); ?>>Individual</option>
                                    <option value="business" <?php echo e($customer->category === 'business' ? 'selected' : ''); ?>>Business</option>
                                </select>
                                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'chevron-down','size' => '4','class' => 'absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','size' => '4','class' => 'absolute right-4 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                            </div>
                        </div>
                        <div class="space-y-2 group" @click.away="showSourceDropdown = false">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Lead Source</label>
                            <div class="relative">
                                <div @click="showSourceDropdown = !showSourceDropdown" class="w-full min-h-[3.5rem] p-2.5 rounded-2xl bg-background/40 border border-border/60 focus-within:ring-4 focus-within:ring-primary/10 focus-within:border-primary outline-none transition-all shadow-sm flex flex-wrap gap-2 items-center cursor-pointer">
                                    <template x-if="selectedSources.length === 0">
                                        <span class="text-sm text-muted-foreground/60 px-2">Select sources...</span>
                                    </template>
                                    <template x-for="source in selectedSources" :key="source">
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-500 text-[10px] font-black uppercase tracking-wider animate-in zoom-in-95">
                                            <span x-text="source"></span>
                                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'x','size' => '3','@click.stop' => 'toggleSource(source)','class' => 'hover:text-destructive transition-colors']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '3','@click.stop' => 'toggleSource(source)','class' => 'hover:text-destructive transition-colors']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                            <input type="hidden" name="source[]" :value="source">
                                        </div>
                                    </template>
                                    <div class="ml-auto pr-2">
                                        <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'chevron-down','size' => '4','class' => 'text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','size' => '4','class' => 'text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                    </div>
                                </div>
                                <div x-show="showSourceDropdown" class="absolute z-50 left-0 right-0 mt-2 py-3 bg-card border border-border/60 rounded-2xl shadow-2xl max-h-60 overflow-y-auto backdrop-blur-xl">
                                    <template x-for="source in sources" :key="source">
                                        <label class="flex items-center gap-3 px-5 py-2.5 hover:bg-primary/10 cursor-pointer transition-colors group/item">
                                            <input type="checkbox" :value="source" :checked="selectedSources.includes(source)" @change="toggleSource(source)" class="size-4 rounded border-border text-primary">
                                            <span class="text-sm font-medium text-muted-foreground group-hover/item:text-foreground" x-text="source"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div>
                    <div class="flex items-center gap-3 pb-4 mb-8 border-b border-border/40">
                        <div class="size-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-500">
                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'phone','size' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'phone','size' => '4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-foreground">Contact Channels</h4>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-wider font-bold mt-0.5">Primary and emergency contact info</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Email Address</label>
                            <div class="relative">
                                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'mail','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'mail','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                <input type="email" name="email" value="<?php echo e($customer->email); ?>" class="w-full pl-11 pr-4 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                            </div>
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Primary Phone</label>
                            <div class="relative">
                                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'phone','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'phone','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                <input type="text" name="phone" value="<?php echo e($customer->phone); ?>" class="w-full pl-11 pr-4 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                            </div>
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Alternate Mobile</label>
                            <input type="text" name="alternatemobile" value="<?php echo e($customer->alternatemobile); ?>" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Secondary Landline</label>
                            <input type="text" name="phone_number_2" value="<?php echo e($customer->phone_number_2); ?>" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Relative Contact Name</label>
                            <input type="text" name="relative_mobile" value="<?php echo e($customer->relative_mobile); ?>" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Relative Contact Phone</label>
                            <input type="text" name="relative_phone" value="<?php echo e($customer->relative_phone); ?>" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                    </div>
                </div>

                
                <div>
                    <div class="flex items-center gap-3 pb-4 mb-8 border-b border-border/40">
                        <div class="size-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'briefcase','size' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'briefcase','size' => '4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-foreground">Business & Compliance</h4>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-wider font-bold mt-0.5">Corporate identity and tax details</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2 group md:col-span-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Registered Company Name</label>
                            <div class="relative">
                                <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'building','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'building','size' => '4','class' => 'absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                <input type="text" name="company_name" value="<?php echo e($customer->company_name); ?>" class="w-full pl-11 pr-4 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                            </div>
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">GST Number</label>
                            <input type="text" name="gst_no" value="<?php echo e($customer->gst_no); ?>" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-mono uppercase tracking-widest">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">PAN Number</label>
                            <input type="text" name="pan_no" value="<?php echo e($customer->pan_no); ?>" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-mono uppercase tracking-widest">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Aadhaar (Last 4)</label>
                            <input type="text" name="aadhaar_last4" value="<?php echo e($customer->aadhaar_last4); ?>" maxlength="4" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-mono tracking-[0.3em]">
                        </div>
                        <div class="flex items-center gap-6 pt-4">
                            <label class="flex items-center gap-3 cursor-pointer group/toggle">
                                <input type="hidden" name="kyc_completed" value="0">
                                <input type="checkbox" name="kyc_completed" value="1" <?php echo e($customer->kyc_completed ? 'checked' : ''); ?> class="size-5 rounded-lg border-border text-primary">
                                <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground group-hover/toggle:text-foreground">KYC Verified</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group/toggle">
                                <input type="hidden" name="is_blacklisted" value="0">
                                <input type="checkbox" name="is_blacklisted" value="1" <?php echo e($customer->is_blacklisted ? 'checked' : ''); ?> class="size-5 rounded-lg border-destructive text-destructive">
                                <span class="text-[10px] font-black uppercase tracking-widest text-destructive group-hover/toggle:text-destructive/80">Blacklisted</span>
                            </label>
                        </div>
                    </div>
                </div>

                
                <div>
                    <div class="flex items-center gap-3 pb-4 mb-8 border-b border-border/40">
                        <div class="size-8 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-500">
                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'sun','size' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'sun','size' => '4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-foreground">Agriculture Profile</h4>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-wider font-bold mt-0.5">Land and cultivation details</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Land Area & Unit</label>
                            <div class="flex gap-3">
                                <input type="number" name="land_area" value="<?php echo e($customer->land_area); ?>" step="0.01" class="w-2/3 px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                                <select name="land_unit" class="w-1/3 px-4 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-xs font-bold text-foreground">
                                    <option value="">Unit</option>
                                    <?php $__currentLoopData = $landUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($unit->name); ?>" <?php echo e($customer->land_unit === $unit->name ? 'selected' : ''); ?>><?php echo e($unit->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-2 group" @click.away="showIrrigationDropdown = false">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Irrigation Type</label>
                            <div class="relative">
                                <div @click="showIrrigationDropdown = !showIrrigationDropdown" class="w-full min-h-[3.5rem] p-2.5 rounded-2xl bg-background/40 border border-border/60 focus-within:ring-4 focus-within:ring-primary/10 focus-within:border-primary outline-none transition-all shadow-sm flex flex-wrap gap-2 items-center cursor-pointer">
                                    <template x-if="selectedIrrigation.length === 0">
                                        <span class="text-sm text-muted-foreground/60 px-2 font-medium">Select types...</span>
                                    </template>
                                    <template x-for="type in selectedIrrigation" :key="type">
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-600 text-[10px] font-black uppercase tracking-wider animate-in zoom-in-95">
                                            <span x-text="type"></span>
                                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'x','size' => '3','@click.stop' => 'toggleIrrigation(type)','class' => 'hover:text-destructive transition-colors']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '3','@click.stop' => 'toggleIrrigation(type)','class' => 'hover:text-destructive transition-colors']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                            <input type="hidden" name="irrigation_type[]" :value="type">
                                        </div>
                                    </template>
                                    <div class="ml-auto pr-2">
                                        <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'chevron-down','size' => '4','class' => 'text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','size' => '4','class' => 'text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                    </div>
                                </div>
                                <div x-show="showIrrigationDropdown" class="absolute z-50 left-0 right-0 mt-2 py-3 bg-card border border-border/60 rounded-2xl shadow-2xl max-h-60 overflow-y-auto backdrop-blur-xl">
                                    <template x-for="type in types" :key="type">
                                        <label class="flex items-center gap-3 px-5 py-2.5 hover:bg-primary/10 cursor-pointer transition-colors group/item">
                                            <input type="checkbox" :value="type" :checked="selectedIrrigation.includes(type)" @change="toggleIrrigation(type)" class="size-4 rounded border-border text-primary">
                                            <span class="text-sm font-medium text-muted-foreground group-hover/item:text-foreground" x-text="type"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 md:col-span-2" @click.away="showCropsDropdown = false">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Cultivated Major Crops</label>
                            <div class="relative">
                                <div class="w-full min-h-[4rem] p-2.5 rounded-2xl bg-background/40 border border-border/60 focus-within:ring-4 focus-within:ring-primary/10 focus-within:border-primary outline-none transition-all shadow-sm flex flex-wrap gap-2 items-center cursor-pointer" @click="showCropsDropdown = true">
                                    <template x-for="crop in selectedCrops" :key="crop">
                                        <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-[10px] font-black uppercase tracking-wider animate-in zoom-in-95">
                                            <span x-text="crop"></span>
                                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'x','size' => '3','@click.stop' => 'toggleCrop(crop)','class' => 'hover:text-destructive transition-colors']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'x','size' => '3','@click.stop' => 'toggleCrop(crop)','class' => 'hover:text-destructive transition-colors']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                            <input type="hidden" name="crops[]" :value="crop">
                                        </div>
                                    </template>
                                    <div class="flex-1 min-w-[12rem] relative">
                                        <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'search','size' => '3','class' => 'absolute left-2 top-1/2 -translate-y-1/2 text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','size' => '3','class' => 'absolute left-2 top-1/2 -translate-y-1/2 text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                        <input type="text" x-model="cropSearch" @focus="showCropsDropdown = true" placeholder="Search crops..." class="w-full pl-8 pr-2 py-1 bg-transparent border-none outline-none focus:ring-0 text-sm font-medium">
                                    </div>
                                    <div class="ml-auto pr-2">
                                        <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'chevron-down','size' => '4','class' => 'text-muted-foreground']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','size' => '4','class' => 'text-muted-foreground']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                                    </div>
                                </div>
                                <div x-show="showCropsDropdown && filteredCrops.length > 0" class="absolute z-50 left-0 right-0 mt-2 py-2 bg-card border border-border/60 rounded-2xl shadow-2xl max-h-60 overflow-y-auto backdrop-blur-xl">
                                    <template x-for="crop in filteredCrops" :key="crop">
                                        <label class="flex items-center gap-3 px-5 py-2.5 hover:bg-primary/10 cursor-pointer transition-colors group/item">
                                            <input type="checkbox" :value="crop" :checked="selectedCrops.includes(crop)" @change="toggleCrop(crop)" class="size-4 rounded border-border text-primary">
                                            <span class="text-sm font-medium text-muted-foreground group-hover/item:text-foreground" x-text="crop"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div>
                    <div class="flex items-center gap-3 pb-4 mb-8 border-b border-border/40">
                        <div class="size-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'credit-card','size' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'credit-card','size' => '4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-foreground">Financial Terms</h4>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-wider font-bold mt-0.5">Credit limits and payment cycles</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Credit Limit (Rs )</label>
                            <input type="number" name="credit_limit" value="<?php echo e($customer->credit_limit); ?>" step="0.01" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Credit Days</label>
                            <input type="number" name="credit_days" value="<?php echo e($customer->credit_days); ?>" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Wallet Balance (Rs )</label>
                            <input type="number" name="outstanding_balance" value="<?php echo e($customer->outstanding_balance); ?>" step="0.01" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                        <div class="space-y-2 group">
                            <label class="text-[10px] font-black uppercase tracking-[0.15em] text-muted-foreground">Validity Period</label>
                            <input type="date" name="credit_valid_till" value="<?php echo e($customer->credit_valid_till?->format('Y-m-d')); ?>" class="w-full px-5 py-3 rounded-2xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium">
                        </div>
                    </div>
                </div>

                
                <div class="pt-4">
                    <div class="flex items-center gap-3 pb-4 mb-6 border-b border-border/40">
                        <div class="size-8 rounded-lg bg-slate-500/10 flex items-center justify-center text-slate-500">
                            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'file-text','size' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-text','size' => '4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
                        </div>
                        <h4 class="text-xs font-black uppercase tracking-[0.2em] text-foreground">Internal Documentation</h4>
                    </div>
                    <textarea name="internal_notes" rows="4" placeholder="Add private administrative notes..." class="w-full px-6 py-4 rounded-3xl bg-background/40 border border-border/60 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all text-sm font-medium resize-none"><?php echo e($customer->internal_notes); ?></textarea>
                </div>
            </div>

            <div class="p-8 bg-muted/20 border-t border-border/40 flex items-center justify-end gap-4">
                <button type="button" @click="$dispatch('close-modal', { name: 'edit-profile-modal' })" class="flex items-center px-6 text-xs font-bold uppercase tracking-widest text-muted-foreground hover:text-foreground transition-colors">Cancel</button>
                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','class' => 'rounded-2xl px-10 py-6 shadow-xl shadow-primary/20']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','class' => 'rounded-2xl px-10 py-6 shadow-xl shadow-primary/20']); ?>
                    Update Customer Profile
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
            </div>
        </form>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7762953202be6518eecd1cfbd075bf2f)): ?>
<?php $attributes = $__attributesOriginal7762953202be6518eecd1cfbd075bf2f; ?>
<?php unset($__attributesOriginal7762953202be6518eecd1cfbd075bf2f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7762953202be6518eecd1cfbd075bf2f)): ?>
<?php $component = $__componentOriginal7762953202be6518eecd1cfbd075bf2f; ?>
<?php unset($__componentOriginal7762953202be6518eecd1cfbd075bf2f); ?>
<?php endif; ?>
<?php /**PATH /home/user/metis/resources/views/customers/partials/modals.blade.php ENDPATH**/ ?>