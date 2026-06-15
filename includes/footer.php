<?php
/**
 * CEMABLN - Footer común
 */
?>
        </div><!-- /Page Content -->
    </main>
</div><!-- /Layout -->

<!-- Mobile sidebar overlay & Theme Toggle -->
<script>
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && !sidebar.contains(e.target) && !e.target.closest('button[onclick]')) {
        if (window.innerWidth < 1024) {
            sidebar.classList.add('-translate-x-full');
        }
    }
});

// Theme toggle logic for header button
var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon-header');
var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon-header');
var themeToggleBtn = document.getElementById('theme-toggle-header');

if (themeToggleDarkIcon && themeToggleLightIcon && themeToggleBtn) {
    // Change the icons inside the button based on previous settings
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        themeToggleLightIcon.classList.remove('hidden');
    } else {
        themeToggleDarkIcon.classList.remove('hidden');
    }

    themeToggleBtn.addEventListener('click', function() {
        // toggle icons inside button
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');

        // if set via local storage previously
        if (localStorage.getItem('color-theme')) {
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }
        // if NOT set via local storage previously
        } else {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        }
    });
}
</script>

<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
