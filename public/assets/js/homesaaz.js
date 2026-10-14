/* ==========================================================================
   HomeSaaz — shared front-end script
   Loaded by layouts/homesaaz.blade.php on every HomeSaaz page.
   Each block guards for its own elements, so it is safe on all pages.
   ========================================================================== */
document.addEventListener('DOMContentLoaded', function () {

    /* -------- Login screen: toggle entry buttons <-> login form -------- */
    var actions   = document.getElementById('hsActions');
    var loginForm = document.getElementById('hsLoginForm');
    var loginBtn  = document.getElementById('hsLoginBtn');
    var backBtn   = document.getElementById('hsBackBtn');

    if (loginBtn && actions && loginForm) {
        loginBtn.addEventListener('click', function () {
            actions.style.display = 'none';
            loginForm.classList.add('is-open');
        });
    }
    if (backBtn && actions && loginForm) {
        backBtn.addEventListener('click', function () {
            loginForm.classList.remove('is-open');
            actions.style.display = 'flex';
        });
    }

    /* -------- Placeholder guard: buttons/tiles whose route is not built yet -------- */
    document.querySelectorAll('[data-todo]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            alert('This section is not ready yet.');
        });
    });

    /* -------- Flash toast: auto-dismiss, and open login form if error on login -------- */
    var toast = document.querySelector('.hs-toast');
    if (toast) {
        // On the login page, a flash message means a failed attempt — open the form.
        if (actions && loginForm) {
            actions.style.display = 'none';
            loginForm.classList.add('is-open');
        }
        setTimeout(function () { toast.remove(); }, 2500);
    }
});
