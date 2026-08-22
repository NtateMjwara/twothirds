<?php
/**
 * Invoice email.
 *
 * Table-based layout with inline styles, because that is what mail clients
 * reliably render - Outlook still ignores most of a stylesheet, and a floated
 * div layout collapses. This is deliberately not built like the rest of the
 * site's markup.
 *
 * Expects: $invoice (from InvoiceService::build).
 */
$fmt = static fn (float $n): string => 'R' . number_format($n, 2);
?>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F6F5F0;padding:24px 0;font-family:Helvetica,Arial,sans-serif;color:#1B2028;">
<tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border:1px solid #E4E2DC;border-radius:6px;">

    <tr>
      <td style="background:#2B3038;padding:22px 28px;border-radius:6px 6px 0 0;">
        <div style="color:#FFFFFF;font-size:18px;letter-spacing:3px;">TWOTHIRDS</div>
        <div style="color:rgba(255,255,255,0.6);font-size:11px;letter-spacing:2px;margin-top:4px;">INVOICE</div>
      </td>
    </tr>

    <tr>
      <td style="padding:26px 28px 8px;">
        <p style="margin:0 0 14px;font-size:15px;">Hello <?= e($invoice['investor']['name']) ?>,</p>
        <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#5A5F68;">
          Your shares in <strong style="color:#1B2028;"><?= e($invoice['company']['name']) ?></strong>
          are reserved. They're already out of what other investors can buy, and they stay
          yours until this invoice is paid or the commitment expires.
        </p>
      </td>
    </tr>

    <tr>
      <td style="padding:0 28px;">
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;border:1px solid #E4E2DC;border-radius:4px;">
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;border-bottom:1px solid #E4E2DC;">Invoice number</td>
            <td style="padding:12px 14px;text-align:right;font-family:monospace;border-bottom:1px solid #E4E2DC;"><?= e($invoice['invoice_number']) ?></td>
          </tr>
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;border-bottom:1px solid #E4E2DC;">Commitment</td>
            <td style="padding:12px 14px;text-align:right;font-family:monospace;border-bottom:1px solid #E4E2DC;"><?= e($invoice['commitment']) ?></td>
          </tr>
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;border-bottom:1px solid #E4E2DC;">Company</td>
            <td style="padding:12px 14px;text-align:right;border-bottom:1px solid #E4E2DC;">
              <?= e($invoice['company']['name']) ?><br>
              <span style="font-family:monospace;font-size:11px;color:#5A5F68;"><?= e($invoice['company']['reference']) ?></span>
            </td>
          </tr>
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;">Issued</td>
            <td style="padding:12px 14px;text-align:right;"><?= e(date('j F Y', strtotime($invoice['issued_at']))) ?></td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding:22px 28px 0;">
        <div style="font-size:11px;letter-spacing:1px;color:#5A5F68;margin-bottom:10px;">WHAT YOU OWE</div>
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;">
          <tr>
            <td style="padding:9px 0;border-bottom:1px solid #E4E2DC;">
              <?= number_format($invoice['shares']) ?> shares at <?= $fmt($invoice['nav']) ?>
            </td>
            <td style="padding:9px 0;text-align:right;font-family:monospace;border-bottom:1px solid #E4E2DC;">
              <?= $fmt($invoice['share_amount']) ?>
            </td>
          </tr>
          <tr>
            <td style="padding:9px 0;border-bottom:1px solid #E4E2DC;">
              Transaction fee (<?= rtrim(rtrim(number_format($invoice['fee_rate'] * 100, 1), '0'), '.') ?>%)
            </td>
            <td style="padding:9px 0;text-align:right;font-family:monospace;border-bottom:1px solid #E4E2DC;">
              <?= $fmt($invoice['fee_amount']) ?>
            </td>
          </tr>
          <tr>
            <td style="padding:12px 0;font-weight:bold;border-top:2px solid #1B2028;">Total due</td>
            <td style="padding:12px 0;text-align:right;font-family:monospace;font-weight:bold;font-size:16px;border-top:2px solid #1B2028;">
              <?= $fmt($invoice['total_due']) ?>
            </td>
          </tr>
        </table>
        <?php if (!empty($invoice['due_at'])): ?>
          <p style="margin:10px 0 0;font-size:12px;color:#5A5F68;">
            Payable by <?= e(date('j F Y', strtotime($invoice['due_at']))) ?>. After that the
            commitment lapses and the shares go back into the available pool. Nothing is owed
            if that happens.
          </p>
        <?php endif; ?>
      </td>
    </tr>

    <?php if ($invoice['bank']): ?>
    <tr>
      <td style="padding:24px 28px 0;">
        <div style="font-size:11px;letter-spacing:1px;color:#5A5F68;margin-bottom:10px;">WHERE TO PAY</div>
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;background:#F6F5F0;border:1px solid #E4E2DC;border-radius:4px;">
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;width:45%;">Account holder</td>
            <td style="padding:12px 14px;text-align:right;"><?= e($invoice['bank']['account_holder']) ?></td>
          </tr>
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;">Bank</td>
            <td style="padding:12px 14px;text-align:right;"><?= e($invoice['bank']['bank_name']) ?></td>
          </tr>
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;">Account number</td>
            <td style="padding:12px 14px;text-align:right;font-family:monospace;"><?= e($invoice['bank']['account_number']) ?></td>
          </tr>
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;">Branch code</td>
            <td style="padding:12px 14px;text-align:right;font-family:monospace;"><?= e($invoice['bank']['branch_code']) ?></td>
          </tr>
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;">Account type</td>
            <td style="padding:12px 14px;text-align:right;"><?= e(ucfirst($invoice['bank']['account_type'])) ?></td>
          </tr>
          <?php if (!empty($invoice['bank']['swift_code'])): ?>
          <tr>
            <td style="padding:12px 14px;color:#5A5F68;">SWIFT</td>
            <td style="padding:12px 14px;text-align:right;font-family:monospace;"><?= e($invoice['bank']['swift_code']) ?></td>
          </tr>
          <?php endif; ?>
        </table>

        <!-- Boxed and repeated because a deposit without it can't be matched. -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:14px;background:#FBF4DF;border:1px solid #C9A227;border-radius:4px;">
          <tr>
            <td style="padding:14px;">
              <div style="font-size:11px;letter-spacing:1px;color:#7A6414;">USE THIS REFERENCE</div>
              <div style="font-family:monospace;font-size:19px;font-weight:bold;margin:6px 0;color:#1B2028;">
                <?= e($invoice['payment_reference']) ?>
              </div>
              <div style="font-size:12px;color:#7A6414;line-height:1.5;">
                A payment without this reference can't be matched to your commitment and will
                delay settlement.
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <?php else: ?>
    <tr>
      <td style="padding:24px 28px 0;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#FBF4DF;border:1px solid #C9A227;border-radius:4px;">
          <tr><td style="padding:14px;font-size:13px;line-height:1.6;">
            We couldn't attach this company's banking details to your invoice. Reply to this
            email quoting <strong><?= e($invoice['commitment']) ?></strong> and we'll send them
            straight away.
          </td></tr>
        </table>
      </td>
    </tr>
    <?php endif; ?>

    <tr>
      <td style="padding:24px 28px;">
        <div style="font-size:11px;letter-spacing:1px;color:#5A5F68;margin-bottom:8px;">WHAT HAPPENS NEXT</div>
        <p style="margin:0;font-size:13px;line-height:1.7;color:#5A5F68;">
          Once your payment clears, an administrator settles the commitment and the shares are
          written to <?= e($invoice['company']['name']) ?>'s share register in your name. You'll
          be notified, and the holding will appear in your portfolio.
        </p>
      </td>
    </tr>

    <tr>
      <td style="padding:18px 28px;border-top:1px solid #E4E2DC;font-size:11px;line-height:1.6;color:#8A8F98;">
        You accepted the documents listed on your commitment when you made it. The version of
        each is recorded against
        <span style="font-family:monospace;"><?= e($invoice['commitment']) ?></span>
        and can be requested at any time.<br><br>
        This invoice relates to a subscription for shares in a private company. It is not a
        receipt &mdash; a receipt follows once payment has cleared.
      </td>
    </tr>

  </table>
</td></tr>
</table>
