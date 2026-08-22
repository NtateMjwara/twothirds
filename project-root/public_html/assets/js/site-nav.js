/**
 * Site navigation.
 *
 * The dropdown is a <details>, so it opens, closes and is announced correctly
 * with no JavaScript at all. This file adds the three behaviours a <details>
 * doesn't give you but a menu implies: closing on an outside click, closing on
 * Escape with focus returned to the trigger, and arrow-key movement between
 * items.
 */
(function () {
    'use strict';

    // --- Profile dropdown ------------------------------------------------
    var menu = document.getElementById('profileMenu');

    if (menu) {
        var trigger = menu.querySelector('summary');

        var close = function (returnFocus) {
            if (!menu.open) {
                return;
            }
            menu.open = false;
            if (returnFocus && trigger) {
                trigger.focus();
            }
        };

        // A panel positioned over the page that stays open when you click away
        // reads as stuck rather than deliberate.
        document.addEventListener('click', function (event) {
            if (menu.open && !menu.contains(event.target)) {
                close(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close(true);
            }
        });

        // Arrow keys move between items once the menu is open, which is what a
        // role="menu" promises a screen reader user.
        menu.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
                return;
            }

            var items = Array.prototype.slice.call(menu.querySelectorAll('[role="menuitem"]'));
            if (!items.length) {
                return;
            }

            event.preventDefault();

            if (!menu.open) {
                menu.open = true;
                items[0].focus();
                return;
            }

            var index = items.indexOf(document.activeElement);
            var next = event.key === 'ArrowDown' ? index + 1 : index - 1;

            // Wrap, so the list has no dead ends at either end.
            if (next < 0) { next = items.length - 1; }
            if (next >= items.length) { next = 0; }

            items[next].focus();
        });

        // Opening with the keyboard should land on the first item rather than
        // leaving focus on the trigger with a panel hanging open below it.
        menu.addEventListener('toggle', function () {
            if (!menu.open) {
                return;
            }
            var first = menu.querySelector('[role="menuitem"]');
            if (first && document.activeElement === trigger) {
                first.focus();
            }
        });
    }

    // --- Mobile panel ----------------------------------------------------
    var toggle = document.getElementById('navToggle');
    var panel = document.getElementById('mobilePanel');

    if (toggle && panel) {
        // `hidden` in the markup rather than display:none in CSS, so without
        // this script the panel stays out of the tab order instead of being an
        // invisible run of focusable links.
        toggle.addEventListener('click', function () {
            var open = panel.hasAttribute('hidden');

            if (open) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', '');
            }

            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('nav-open', open);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hasAttribute('hidden')) {
                panel.setAttribute('hidden', '');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('nav-open');
                toggle.focus();
            }
        });
    }
}());
