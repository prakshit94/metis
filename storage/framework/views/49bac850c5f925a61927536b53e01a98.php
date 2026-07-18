<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo e($config->get('ui.title') ?? config('app.name') . ' - API Docs'); ?></title>
</head>
<body>
<div id="app"></div>
<script src="<?php echo e($config->renderer()->get('cdn', 'https://cdn.jsdelivr.net/npm/@scalar/api-reference')); ?>"></script>

<script>
    const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
    const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
    const getCookieValue = (key) => {
        const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
        return cookie?.split("=")[1];
    };

    Scalar.createApiReference('#app', {
        content: <?php echo json_encode($spec, 15, 512) ?>,
        ...<?php echo json_encode($config->renderer()->all(except: ['cdn', 'credentials']), 512) ?>,
        onBeforeRequest: ({ requestBuilder }) => {
            requestBuilder.headers.set(CSRF_TOKEN_HEADER_KEY, decodeURIComponent(getCookieValue(CSRF_TOKEN_COOKIE_KEY)))
        },
        customFetch: (input, init) => {
            return window.fetch(input, { ...init, credentials: <?php echo json_encode($config->renderer()->get('credentials', 'include'), 512) ?> })
        }
    })
</script>
</body>
</html>
<?php /**PATH /home/user/metis/vendor/dedoc/scramble/resources/views/scalar.blade.php ENDPATH**/ ?>