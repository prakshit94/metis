<?php $__env->startSection('title', 'Sign In'); ?>
<?php $__env->startSection('description', 'Sign in to your Metis Admin account'); ?>
<?php $__env->startSection('page', 'login'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-page" id="main-content">
    <div class="auth-card" x-data="loginApp()">

        
        <div class="auth-logo mb-4">
            <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect width="36" height="36" rx="10" fill="url(#metis-grad)"/>
                <path d="M10 26V12l8 8 8-8v14" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <defs>
                    <linearGradient id="metis-grad" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#6366f1"/>
                        <stop offset="1" stop-color="#8b5cf6"/>
                    </linearGradient>
                </defs>
            </svg>
            <span class="auth-brand-name">Ecommerce</span>
        </div>

        
        
        
        <div>
            <p class="auth-subtitle">Welcome back! Please sign in to your account.</p>

            
            <?php if(session('error')): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 py-2 px-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                    <span><?php echo e(session('error')); ?></span>
                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-3 py-2 px-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <div>
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div><?php echo e($error); ?></div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>

            
            <form method="POST" action="<?php echo e(route('login.submit')); ?>" id="loginForm" @submit="isSubmitting = true">
                <?php echo csrf_field(); ?>

                
                <div class="mb-3">
                    <label for="loginEmail" class="form-label fw-semibold">Email address</label>
                    <input type="email"
                           class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           id="loginEmail"
                           name="email"
                           value="<?php echo e(old('email', request()->cookie('remembered_email'))); ?>"
                           placeholder="you@example.com"
                           autocomplete="email"
                           autofocus
                           required>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                
                <div class="mb-3">
                    <!-- <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="loginPassword" class="form-label fw-semibold mb-0">Password</label>
                        <a href="#" class="auth-forgot-link" tabindex="-1">Forgot password?</a>
                    </div> -->
                    <div class="input-group">
                        <input :type="showPassword ? 'text' : 'password'"
                               class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               id="loginPassword"
                               name="password"
                               placeholder="••••••••"
                               autocomplete="current-password"
                               required>
                        <button class="btn btn-outline-secondary password-toggle"
                                type="button"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                tabindex="-1">
                            <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                
                <div class="mb-4 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 mb-0">
                        <input class="auth-checkbox"
                               type="checkbox"
                               id="rememberMe"
                               name="remember"
                               value="1"
                               <?php echo e(old('remember') ? 'checked' : ''); ?>

                               x-model="remember">
                        <label class="text-secondary" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    <span class="auth-remember-hint" x-show="remember" x-transition>
                        <i class="bi bi-shield-check text-success"></i>
                        <small class="text-success">30 days</small>
                    </span>
                </div>

                
                <button type="submit"
                        class="btn btn-primary w-100 auth-submit-btn"
                        id="loginSubmitBtn"
                        :disabled="isSubmitting">
                    <span x-show="!isSubmitting" class="d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Sign In
                    </span>
                    <span x-show="isSubmitting" style="display: none;" class="d-flex align-items-center justify-content-center gap-2">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Signing in…
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('loginApp', () => ({
            showPassword: false,
            isSubmitting: false,

            // Sign-in state
            remember: <?php echo e(old('remember') ? 'true' : 'false'); ?>,

            // Reset spinner if server returned a validation error
            init() {
                <?php if($errors->any()): ?>
                    this.isSubmitting = false;
                <?php endif; ?>
            },
        }));
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/metis/resources/views/login.blade.php ENDPATH**/ ?>