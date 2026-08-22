/**
 * Commitment form.
 *
 * Prices the commitment as the share count is typed. The server recalculates
 * everything on submit and the invoice is built from that - this is a preview,
 * so an investor isn't deciding a number blind and then discovering the total
 * on the confirmation page.
 */
(function () {
    'use strict';

    var input = document.querySelector('[data-shares]');
    var quote = document.querySelector('[data-quote]');

    if (!input || !quote) {
        return;
    }

    var nav = parseFloat(input.getAttribute('data-nav'));
    var feeRate = parseFloat(input.getAttribute('data-fee'));

    var out = {
        shares:   quote.querySelector('[data-quote-shares]'),
        subtotal: quote.querySelector('[data-quote-subtotal]'),
        fee:      quote.querySelector('[data-quote-fee]'),
        total:    quote.querySelector('[data-quote-total]')
    };

    function money(value) {
        return 'R' + value.toLocaleString('en-ZA', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function price() {
        var shares = parseInt(input.value, 10);

        if (!(shares > 0) || !(nav > 0)) {
            quote.hidden = true;
            return;
        }

        // Rounded at each step, the same way the server does it. Rounding only
        // the total would leave the preview a cent away from the invoice.
        var subtotal = Math.round(shares * nav * 100) / 100;
        var fee = Math.round(subtotal * feeRate * 100) / 100;

        out.shares.textContent = shares.toLocaleString('en-ZA');
        out.subtotal.textContent = money(subtotal);
        out.fee.textContent = money(fee);
        out.total.textContent = money(subtotal + fee);

        quote.hidden = false;
    }

    input.addEventListener('input', price);
    price();
}());
