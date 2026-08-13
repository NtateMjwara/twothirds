/**
 * Discovery page behaviour.
 *
 * Everything on this page works without JavaScript - filters are links and
 * forms, pagination is real URLs. This file only adds the two things that would
 * otherwise be awkward on a phone: collapsing the refine panel, and scrolling
 * the industry rail to whatever is currently selected.
 */
(function () {
    'use strict';

    // --- Refine drawer -------------------------------------------------
    var toggle = document.getElementById('filterDrawerToggle');
    var drawer = document.getElementById('discoveryFilters');

    if (toggle && drawer) {
        toggle.addEventListener('click', function () {
            var open = drawer.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                var firstField = drawer.querySelector('select, input');
                if (firstField) {
                    firstField.focus({ preventScroll: true });
                }
            }
        });
    }

    // --- Rails ---------------------------------------------------------
    // Bring the active chip into view on load. Without this, picking the last
    // industry in the list and reloading leaves it scrolled off screen and the
    // rail looks unfiltered.
    document.querySelectorAll('.chip-rail').forEach(function (rail) {
        var active = rail.querySelector('.is-active');
        if (!active) {
            return;
        }

        var offset = active.offsetLeft - (rail.clientWidth / 2) + (active.clientWidth / 2);
        rail.scrollLeft = Math.max(0, offset);
    });

    // Horizontal rails swallow vertical scroll intent on trackpads. Translating
    // a deliberate horizontal gesture keeps the page itself scrolling normally.
    document.querySelectorAll('.chip-rail, .snapshot-rail').forEach(function (rail) {
        rail.addEventListener('wheel', function (event) {
            if (Math.abs(event.deltaX) >= Math.abs(event.deltaY)) {
                return;
            }
            if (event.shiftKey) {
                rail.scrollLeft += event.deltaY;
                event.preventDefault();
            }
        }, { passive: false });
    });
}());
