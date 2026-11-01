<script>
    // The page shell is ready when parsing finishes. Do not wait for the main
    // module (or its imports) to load before releasing the full-page overlay.
    (function () {
        var dismissed = false;
        function dismissLoadingScreen() {
            if (dismissed || document.readyState === 'loading') return;
            dismissed = true;
            document.removeEventListener('readystatechange', dismissLoadingScreen);
            window.removeEventListener('pageshow', dismissLoadingScreen);
            var screen = document.getElementById('loading-screen');
            if (!screen) return;
            screen.classList.add('hidden');
            screen.setAttribute('aria-hidden', 'true');
            // Also remove it when transitions are disabled or never fire.
            setTimeout(function () { screen.remove(); }, 400);
        }
        document.addEventListener('readystatechange', dismissLoadingScreen);
        window.addEventListener('pageshow', dismissLoadingScreen);
        dismissLoadingScreen();
    })();
</script>
