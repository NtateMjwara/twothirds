/**
 * Admin navigation toggle.
 *
 * The nav is a plain list of links, so it works with no JavaScript - this only
 * collapses it on narrow screens where seven items won't fit on one line.
 */
(function () {
    'use strict';

    var toggle = document.getElementById('adminNavToggle');
    var nav = document.getElementById('adminNav');

    if (!toggle || !nav) {
        return;
    }

    toggle.addEventListener('click', function () {
        var open = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    // Close on Escape so the menu can't trap someone on a small screen.
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && nav.classList.contains('is-open')) {
            nav.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }
    });
}());
