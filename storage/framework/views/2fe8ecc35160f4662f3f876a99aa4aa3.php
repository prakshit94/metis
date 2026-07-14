<?php $__env->startSection('title', '500'); ?>
<?php $__env->startSection('page', '500'); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script type="module">
        import Alpine from 'alpinejs';

        document.addEventListener('alpine:init', () => {
            Alpine.data('errorCountdown', () => ({
                countdown: 30,
                _timer: null,

                start() {
                    this._timer = setInterval(() => {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            clearInterval(this._timer);
                            window.location.reload();
                        }
                    }, 1000);
                },

                destroy() {
                    if (this._timer) clearInterval(this._timer);
                },
            }));
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/ubuntu/metis/resources/views/errors/500.blade.php ENDPATH**/ ?>