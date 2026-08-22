/**
 * Asset gallery lightbox.
 *
 * Built on <dialog>, so the browser handles the modal semantics: focus is
 * trapped, the page behind is inert, and Escape closes it. Re-implementing that
 * by hand is where most homemade lightboxes get accessibility wrong.
 *
 * Every photograph is already in the page markup. Without this script the
 * thumbnails simply do nothing rather than showing a broken overlay.
 */
(function () {
    'use strict';

    var dialog = document.getElementById('assetGallery');
    if (!dialog || typeof dialog.showModal !== 'function') {
        return;
    }

    var slides = Array.prototype.slice.call(dialog.querySelectorAll('[data-gallery-slide]'));
    var captionEl = dialog.querySelector('[data-gallery-caption]');
    var indexEl = dialog.querySelector('[data-gallery-index]');
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('.hero-thumb'));
    var current = 0;

    if (!slides.length) {
        return;
    }

    function show(index) {
        // Wrap rather than clamp: at the last photo, "next" should return to the
        // first instead of doing nothing.
        current = (index + slides.length) % slides.length;

        slides.forEach(function (slide, i) {
            slide.classList.toggle('is-current', i === current);
        });
        thumbs.forEach(function (thumb, i) {
            thumb.classList.toggle('is-active', i === current);
        });

        if (captionEl) {
            captionEl.textContent = slides[current].getAttribute('alt') || '';
        }
        if (indexEl) {
            indexEl.textContent = String(current + 1);
        }
    }

    function open(index) {
        show(index);
        dialog.showModal();
    }

    document.querySelectorAll('[data-gallery-open]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            open(parseInt(trigger.getAttribute('data-gallery-open'), 10) || 0);
        });
    });

    dialog.querySelectorAll('[data-gallery-step]').forEach(function (button) {
        button.addEventListener('click', function () {
            show(current + (parseInt(button.getAttribute('data-gallery-step'), 10) || 0));
        });
    });

    dialog.querySelectorAll('[data-gallery-close]').forEach(function (button) {
        button.addEventListener('click', function () { dialog.close(); });
    });

    // Arrow keys are what people reach for in a full-screen viewer. Escape is
    // already handled by <dialog> itself.
    dialog.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowRight') {
            show(current + 1);
        } else if (event.key === 'ArrowLeft') {
            show(current - 1);
        }
    });

    // Clicking the backdrop closes. The dialog fills the viewport, so the test
    // is whether the click landed on the dialog element itself rather than on
    // any of its children.
    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    // Keep the thumbnail highlight in step when the dialog is dismissed.
    dialog.addEventListener('close', function () {
        thumbs.forEach(function (thumb, i) {
            thumb.classList.toggle('is-active', i === current);
        });
    });
}());
