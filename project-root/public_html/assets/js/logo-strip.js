/**
 * Brand logo strip.
 *
 * The strip is a native scroll container, so swiping, trackpad scrolling and
 * keyboard scrolling all work with no JavaScript at all. This file adds the two
 * arrows.
 *
 * Both arrows stay live at all times and wrap around at the ends — pressing
 * next on the last logo returns to the first, pressing previous on the first
 * jumps to the last. That keeps the row walkable in either direction
 * indefinitely and means neither control is ever dead, which is what a pair of
 * permanently visible arrows implies.
 */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : null;

    document.querySelectorAll('.brand-strip-wrap').forEach(function (wrap) {
        var strip = wrap.querySelector('.brand-strip');
        var prev = wrap.querySelector('.strip-prev');
        var next = wrap.querySelector('.strip-next');

        if (!strip || !prev || !next) {
            return;
        }

        function smooth() {
            return (reduceMotion && reduceMotion.matches) ? 'auto' : 'smooth';
        }

        /**
         * Distance between two adjacent logos, measured rather than assumed —
         * the gap is a clamp() that changes with viewport width, so hardcoding
         * it here would drift out of sync with the stylesheet.
         */
        function pitch() {
            var logos = strip.querySelectorAll('.brand-logo');
            if (logos.length < 2) {
                return strip.clientWidth;
            }
            return logos[1].offsetLeft - logos[0].offsetLeft;
        }

        /** A screenful, rounded down to whole logos so nothing lands half-cut. */
        function step() {
            var p = pitch();
            var fits = Math.floor(strip.clientWidth / p);
            return Math.max(1, fits) * p;
        }

        function maxScroll() {
            return strip.scrollWidth - strip.clientWidth;
        }

        function move(direction) {
            // 2px of slack: sub-pixel layout means scrollLeft rarely lands on
            // an exact zero or maximum.
            var atStart = strip.scrollLeft <= 2;
            var atEnd = strip.scrollLeft >= maxScroll() - 2;

            if (direction > 0 && atEnd) {
                // Wrapping is a jump, not a journey. Sliding the full width
                // back would be a long confusing sweep past every logo.
                strip.scrollTo({ left: 0, behavior: 'auto' });
                return;
            }
            if (direction < 0 && atStart) {
                strip.scrollTo({ left: maxScroll(), behavior: 'auto' });
                return;
            }

            strip.scrollBy({ left: direction * step(), behavior: smooth() });
        }

        prev.addEventListener('click', function () { move(-1); });
        next.addEventListener('click', function () { move(1); });

        /** Hide both arrows when every logo already fits. */
        function sync() {
            wrap.classList.toggle('is-static', strip.scrollWidth <= strip.clientWidth + 2);
        }

        window.addEventListener('resize', sync);

        // Images have explicit width and height attributes, so layout is
        // correct before they load — but re-check anyway in case one fails and
        // collapses its slot.
        strip.querySelectorAll('img').forEach(function (img) {
            if (!img.complete) {
                img.addEventListener('load', sync);
                img.addEventListener('error', sync);
            }
        });

        sync();
    });
}());
