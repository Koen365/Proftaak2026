// TowerDefenseHQ – Main JS
document.addEventListener('DOMContentLoaded', function () {

    // ── User dropdown ──────────────────────────────
    var menuBtn  = document.getElementById('user-menu-btn');
    var dropdown = document.getElementById('user-dropdown');
    if (menuBtn && dropdown) {
        menuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            dropdown.classList.remove('open');
        });
    }

    // ── Mobile nav ─────────────────────────────────
    var mobileBtn = document.getElementById('mobile-menu-btn');
    var mainNav   = document.getElementById('main-nav');
    if (mobileBtn && mainNav) {
        mobileBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            mainNav.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!mainNav.contains(e.target)) mainNav.classList.remove('open');
        });
    }

    // ── Auto-dismiss alerts ─────────────────────────
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 5000);
    });

    // ── Toast system ───────────────────────────────
    window.showToast = function (msg, type, dur) {
        type = type || 'info'; dur = dur || 3500;
        var container = document.getElementById('toast-container');
        if (!container) return;
        var toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.innerHTML = '<span>' + msg + '</span><button onclick="this.parentElement.remove()">&times;</button>';
        container.appendChild(toast);
        setTimeout(function () {
            toast.style.transition = 'opacity .4s';
            toast.style.opacity = '0';
            setTimeout(function () { toast.remove(); }, 400);
        }, dur);
    };

    // ── Tab panels (generic) ───────────────────────
    document.querySelectorAll('[data-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var group = this.closest('[id]');
            group.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
            this.classList.add('active');
            var panel = document.getElementById('tab-' + this.dataset.tab) || document.getElementById(this.dataset.tab + '-tab');
            if (panel) panel.classList.add('active');
        });
    });
});
