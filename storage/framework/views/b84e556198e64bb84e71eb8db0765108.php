<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="description" content="<?php echo $__env->yieldContent('description', 'Sign in to Modern Bootstrap Admin Dashboard'); ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <!-- Title -->
    <title><?php echo $__env->yieldContent('title', 'Login'); ?> - Ecommerce Admin</title>

    <!-- Theme Color -->
    <meta name="theme-color" content="#6366f1">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/icons/favicon.svg">
    <link rel="icon" type="image/png" href="/assets/icons/favicon.png">

    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/scss/main.scss', 'resources/js/main.js']); ?>

    <?php echo $__env->yieldPushContent('head'); ?>
</head>

<body data-page="<?php echo $__env->yieldContent('page', 'login'); ?>">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <?php echo $__env->yieldContent('content'); ?>

    <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="toast-stack position-fixed end-0 p-3">
        <div id="toast-container"></div>
    </div>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /home/user/metis/resources/views/layouts/auth.blade.php ENDPATH**/ ?>