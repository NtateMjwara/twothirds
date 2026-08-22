/**
 * Discovery and browse behaviour.
 *
 * Both pages work without JavaScript: every filter is a link or a form, the
 * "Add filter" dropdown is a <details>, and pagination is real URLs. This file
 * only adds what would otherwise be awkward.
 */
(function () {
    'use strict';

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
    // only a deliberate shift-scroll keeps the page itself scrolling normally.
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

    // --- Add filter dropdown -------------------------------------------
    var addFilter = document.querySelector('.add-filter');

    if (addFilter) {
        // A <details> panel stays open when you click elsewhere, which for
        // something positioned over the results reads as stuck rather than
        // deliberate. Closing on outside click is the behaviour a dropdown
        // implies; Escape closes it too, for anyone on a keyboard.
        document.addEventListener('click', function (event) {
            if (addFilter.open && !addFilter.contains(event.target)) {
                addFilter.open = false;
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && addFilter.open) {
                addFilter.open = false;
                var summary = addFilter.querySelector('summary');
                if (summary) {
                    summary.focus();
                }
            }
        });
    }

    // --- Activity cloud ------------------------------------------------
    // Same problem as the rail: a selected pill part-way down a scrolling cloud
    // is invisible on load.
    document.querySelectorAll('.pill-cloud').forEach(function (cloud) {
        var active = cloud.querySelector('.is-active');
        if (active && cloud.scrollHeight > cloud.clientHeight) {
            cloud.scrollTop = Math.max(0, active.offsetTop - cloud.clientHeight / 2);
        }
    });
}());
