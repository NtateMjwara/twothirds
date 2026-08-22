/**
 * Stepped SPV creation form.
 *
 * Progressive enhancement throughout. The markup is one plain form with four
 * fieldsets; if this file never loads, all four are visible and submitting works
 * exactly as it did before. Nothing here is required for the form to function -
 * it only makes a long form less punishing.
 *
 * Two modes, set by `data-mode` on the form:
 *
 *   wizard  (default)  linear, Back/Continue, forward movement gated by
 *                      validation. Creating an SPV is a task you do once.
 *   tabs    edit       every section reachable immediately, no ordering, one
 *                      Save. Editing is almost always "change this one thing",
 *                      and making someone click through four panels to reach it
 *                      would be worse than the single long form it replaced.
 *
 * The form carries `novalidate` so the browser doesn't block a submit on a field
 * inside a hidden panel, which is invisible and reads as a dead button. Each
 * step is validated explicitly instead, and the panel is shown before anything
 * is reported.
 */
(function () {
    'use strict';

    var form = document.getElementById('spvForm');
    if (!form) {
        return;
    }

    var panels = Array.prototype.slice.call(form.querySelectorAll('.wizard-panel'));
    var indicators = document.getElementById('wizardSteps');
    var backBtn = form.querySelector('[data-wizard-back]');
    var nextBtn = form.querySelector('[data-wizard-next]');
    var submitBtn = form.querySelector('[data-wizard-submit]');
    var position = form.querySelector('[data-wizard-position]');
    var review = form.querySelector('[data-review]');
    var reviewGrid = form.querySelector('[data-review-grid]');

    if (panels.length < 2) {
        return;
    }

    var total = panels.length;
    var isTabs = form.getAttribute('data-mode') === 'tabs';
    var current = parseInt(form.getAttribute('data-start-step'), 10) || 1;
    // How far the person has legitimately reached. The step indicator is
    // clickable, but only backwards - jumping to step 4 from step 1 skips the
    // validation that makes step 4 meaningful.
    var furthest = current;

    form.classList.add('is-enhanced');
    if (indicators) {
        indicators.hidden = false;
    }
    if (review) {
        review.hidden = false;
    }

    // --- Steps ---------------------------------------------------------

    function show(step) {
        current = Math.min(Math.max(step, 1), total);
        furthest = Math.max(furthest, current);

        panels.forEach(function (panel) {
            var isCurrent = parseInt(panel.getAttribute('data-step'), 10) === current;
            panel.classList.toggle('is-current', isCurrent);
            // inert would be neater, but support is patchy enough that a hidden
            // panel would still be reachable by tab in some browsers.
            panel.hidden = !isCurrent;
        });

        if (indicators) {
            indicators.querySelectorAll('[data-step-indicator]').forEach(function (item) {
                var number = parseInt(item.getAttribute('data-step-indicator'), 10);
                item.classList.toggle('is-current', number === current);
                item.classList.toggle('is-done', number < furthest && number !== current);
                var button = item.querySelector('[data-step-goto]');
                if (button) {
                    // In tabs mode nothing is gated - every section is a
                    // destination, not a stage.
                    button.disabled = isTabs ? false : number > furthest;
                }
            });
        }

        if (backBtn) {
            backBtn.hidden = isTabs || current === 1;
        }
        if (nextBtn) {
            nextBtn.hidden = isTabs || current === total;
        }
        if (submitBtn) {
            submitBtn.hidden = !isTabs && current !== total;
        }

        if (position) {
            position.textContent = isTabs ? '' : 'Step ' + current + ' of ' + total;
        }

        if (!isTabs && current === total) {
            buildReview();
        }

        // Move focus to the panel heading so a keyboard or screen reader user
        // lands on the new step rather than staying on a button that just moved.
        var legend = panels[current - 1].querySelector('.wizard-legend-title');
        if (legend) {
            legend.setAttribute('tabindex', '-1');
            legend.focus({ preventScroll: true });
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /** Validate only the fields in the visible panel. */
    function validateStep() {
        var panel = panels[current - 1];
        var fields = Array.prototype.slice.call(panel.querySelectorAll('input, select, textarea'));
        var firstInvalid = null;

        fields.forEach(function (field) {
            var ok = field.checkValidity();
            field.closest('.field') && field.closest('.field').classList.toggle('has-error', !ok);
            if (!ok && !firstInvalid) {
                firstInvalid = field;
            }
        });

        if (firstInvalid) {
            firstInvalid.focus();
            firstInvalid.reportValidity();
            return false;
        }
        return true;
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            if (validateStep()) {
                show(current + 1);
            }
        });
    }

    if (backBtn) {
        backBtn.addEventListener('click', function () {
            show(current - 1);
        });
    }

    if (indicators) {
        indicators.addEventListener('click', function (event) {
            var button = event.target.closest('[data-step-goto]');
            if (!button || button.disabled) {
                return;
            }
            var target = parseInt(button.getAttribute('data-step-goto'), 10);
            // In wizard mode, moving forward still has to pass the current step.
            if (!isTabs && target > current && !validateStep()) {
                return;
            }
            show(target);
        });
    }

    // The submit button lives outside the panels, so the last step needs the
    // same check the others get from Continue.
    form.addEventListener('submit', function (event) {
        for (var step = 1; step <= total; step++) {
            var panel = panels[step - 1];
            var invalid = Array.prototype.slice.call(panel.querySelectorAll('input, select, textarea'))
                .filter(function (field) { return !field.checkValidity(); });

            if (invalid.length) {
                event.preventDefault();
                show(step);
                invalid[0].focus();
                invalid[0].reportValidity();
                return;
            }
        }
    });

    // --- NAV per share -------------------------------------------------
    //
    // Mirrors the server's calculation so the number is visible while the
    // decision is being made. The server recalculates on save regardless - this
    // is a preview, not the source of truth.

    var valuationInput = form.querySelector('[data-nav-valuation]');
    var fallbackInput = form.querySelector('[data-nav-fallback]');
    var sharesInput = form.querySelector('[data-nav-shares]');
    var navOutput = form.querySelector('[data-nav-output]');
    var navExplainer = form.querySelector('[data-nav-explainer]');
    var navDelta = form.querySelector('[data-nav-delta]');
    // Present on the edit form only: what NAV is right now, so a change can be
    // stated rather than silently applied.
    var storedNav = navOutput ? parseFloat(navOutput.getAttribute('data-nav-current')) : NaN;

    function money(value, decimals) {
        return value.toLocaleString('en-ZA', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function assetValue() {
        var valuation = parseFloat(valuationInput && valuationInput.value);
        if (valuation > 0) {
            return { amount: valuation, source: 'current valuation' };
        }
        var purchase = parseFloat(fallbackInput && fallbackInput.value);
        if (purchase > 0) {
            return { amount: purchase, source: 'purchase price' };
        }
        return null;
    }

    function recalculateNav() {
        if (!navOutput) {
            return;
        }

        var asset = assetValue();
        var shares = parseInt(sharesInput && sharesInput.value, 10);

        if (!asset || !(shares > 0)) {
            navOutput.value = '—';
            if (navExplainer) {
                navExplainer.textContent = !asset
                    ? 'Add a valuation or a purchase price in step 2.'
                    : 'Enter the number of shares to issue.';
            }
            return;
        }

        var nav = asset.amount / shares;
        // Two decimals to read, four stored - the column is DECIMAL(10,4) and
        // rounding the display doesn't round what's saved.
        navOutput.value = money(nav, 2);

        if (navExplainer) {
            navExplainer.textContent =
                'R' + money(asset.amount, 2) + ' (' + asset.source + ') ÷ ' +
                shares.toLocaleString('en-ZA') + ' shares';
        }

        showDelta(nav);
    }

    /**
     * Say out loud when a save would re-price the company.
     *
     * Revaluing re-prices every share already on the register. That's correct
     * behaviour, but it should never be a surprise discovered after saving.
     */
    function showDelta(nav) {
        if (!navDelta || !(storedNav > 0)) {
            return;
        }

        // A cent either way is rounding, not a re-pricing.
        if (Math.abs(nav - storedNav) < 0.005) {
            navDelta.hidden = true;
            return;
        }

        var direction = nav > storedNav ? 'up' : 'down';
        var change = ((nav - storedNav) / storedNav) * 100;

        navDelta.hidden = false;
        navDelta.className = 'nav-delta is-' + direction;
        navDelta.textContent =
            'Saving will move NAV ' + direction + ' from R' + money(storedNav, 2) +
            ' to R' + money(nav, 2) + ' a share (' +
            (change > 0 ? '+' : '') + money(change, 1) + '%). ' +
            'Every existing shareholder is re-priced.';
    }

    [valuationInput, fallbackInput, sharesInput].forEach(function (input) {
        if (input) {
            input.addEventListener('input', recalculateNav);
        }
    });
    recalculateNav();

    // --- Review --------------------------------------------------------

    function labelFor(field) {
        if (field.tagName === 'SELECT') {
            var option = field.options[field.selectedIndex];
            return option && option.value ? option.text : '';
        }
        return field.value;
    }

    function buildReview() {
        if (!reviewGrid) {
            return;
        }

        var asset = assetValue();
        var shares = parseInt(sharesInput && sharesInput.value, 10);

        var rows = [
            ['Company', form.querySelector('[name="name"]').value],
            ['Registration', form.querySelector('[name="registration_number"]').value],
            ['Asset class', labelFor(form.querySelector('[name="asset_class_id"]'))],
            ['Vehicle', [
                form.querySelector('[name="make"]').value,
                form.querySelector('[name="model"]').value,
                form.querySelector('[name="year"]').value
            ].filter(Boolean).join(' ')],
            ['VIN', form.querySelector('[name="vin"]').value],
            ['Activity', labelFor(form.querySelector('[name="activity_type_id"]'))],
            ['Operating area', form.querySelector('[name="location"]').value],
            ['Asset value', asset ? 'R' + money(asset.amount, 2) + ' (' + asset.source + ')' : ''],
            ['Shares issued', shares > 0 ? shares.toLocaleString('en-ZA') : ''],
            ['NAV per share', (asset && shares > 0) ? 'R' + money(asset.amount / shares, 2) : '']
        ];

        reviewGrid.innerHTML = '';

        rows.forEach(function (row) {
            var div = document.createElement('div');
            var dt = document.createElement('dt');
            var dd = document.createElement('dd');

            dt.textContent = row[0];
            // Blank optional fields are shown as "not set" rather than dropped,
            // so the summary is a checklist and not just a list of what happens
            // to be filled in.
            if (row[1]) {
                dd.textContent = row[1];
            } else {
                dd.textContent = 'Not set';
                dd.className = 'is-unset';
            }

            div.appendChild(dt);
            div.appendChild(dd);
            reviewGrid.appendChild(div);
        });
    }

    // --- Unsaved changes ------------------------------------------------
    //
    // The edit form is long and tabbed, so a change made in one section can be
    // two clicks out of sight when someone navigates away. Opt-in via
    // data-dirty-warning; the create form doesn't need it.

    if (form.hasAttribute('data-dirty-warning')) {
        var status = form.querySelector('[data-dirty-status]');
        var dirty = false;

        function markDirty() {
            if (dirty) {
                return;
            }
            dirty = true;
            if (status) {
                status.textContent = 'Unsaved changes.';
                status.classList.add('is-dirty');
            }
        }

        form.addEventListener('input', markDirty);
        form.addEventListener('change', markDirty);

        // Submitting is not leaving.
        form.addEventListener('submit', function () { dirty = false; });

        window.addEventListener('beforeunload', function (event) {
            if (!dirty) {
                return;
            }
            event.preventDefault();
            // Browsers ignore custom text here and show their own wording; the
            // return value is only needed to trigger the prompt at all.
            event.returnValue = '';
        });
    }

    show(current);
}());
