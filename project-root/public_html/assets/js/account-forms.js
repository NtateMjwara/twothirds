/**
 * Account form conveniences.
 *
 * Every one of these is an enhancement. Without this file the forms submit and
 * validate exactly as they do with it - the server checks all of it again
 * regardless, because a disabled input and a selected option are suggestions to
 * a browser, not constraints on a request.
 */
(function () {
    'use strict';

    // --- Branch code prefill --------------------------------------------
    //
    // The universal branch code is the same for everyone at a given bank, so
    // typing it is busywork. It stays editable because some accounts still use
    // a physical branch code.
    var bankSelect = document.querySelector('[data-bank-select]');
    var branchInput = document.querySelector('[data-branch-input]');

    if (bankSelect && branchInput) {
        bankSelect.addEventListener('change', function () {
            var option = bankSelect.options[bankSelect.selectedIndex];
            var branch = option ? option.getAttribute('data-branch') : '';

            // Never overwrite something the person typed themselves.
            if (branch && (branchInput.value === '' || branchInput.dataset.autofilled === 'true')) {
                branchInput.value = branch;
                branchInput.dataset.autofilled = 'true';
            }
        });

        branchInput.addEventListener('input', function () {
            branchInput.dataset.autofilled = 'false';
        });
    }

    // --- Tax residency ---------------------------------------------------
    //
    // The foreign fields only mean anything for a non-resident. Disabling them
    // rather than hiding keeps the form's shape stable, so nothing jumps as the
    // checkbox is toggled.
    var residentToggle = document.querySelector('[data-tax-resident]');

    if (residentToggle) {
        var foreignFields = ['foreign_tax_country', 'foreign_tax_number']
            .map(function (name) { return document.querySelector('[name="' + name + '"]'); })
            .filter(Boolean);

        var syncResidency = function () {
            foreignFields.forEach(function (field) {
                field.disabled = residentToggle.checked;
                var wrapper = field.closest('.field');
                if (wrapper) {
                    wrapper.classList.toggle('is-disabled', residentToggle.checked);
                }
            });
        };

        residentToggle.addEventListener('change', syncResidency);
        syncResidency();
    }

    // --- Employer relevance ----------------------------------------------
    //
    // Asking a retired or unemployed investor for an employer produces a junk
    // value, not a record. The server applies the same rule.
    var employment = document.querySelector('[data-employment]');
    var employer = document.querySelector('[name="employer"]');

    if (employment && employer) {
        var needsEmployer = ['employed_full', 'employed_part', 'director'];

        var syncEmployer = function () {
            var required = needsEmployer.indexOf(employment.value) !== -1;
            employer.required = required;

            var wrapper = employer.closest('.field');
            if (wrapper) {
                wrapper.classList.toggle('is-dimmed', !required && employment.value !== '');
            }
        };

        employment.addEventListener('change', syncEmployer);
        syncEmployer();
    }
}());
