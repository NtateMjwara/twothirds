<?php
/**
 * Company detail.
 *
 * Reads in the order someone decides in: what is it and what does it look like,
 * what would it cost and how much is left, then the evidence — asset record,
 * trading history, governance, documents.
 *
 * The invest card sits high and stays reachable, because everything below it is
 * there to answer questions raised by it.
 */
$primaryActivity = $activities[0] ?? null;
$assetName = trim(((string) ($asset['make'] ?? '')) . ' ' . ((string) ($asset['model'] ?? '')));
?>
<?php require __DIR__ . '/partials/_gallery.php'; ?>

<section class="company-masthead">
    <div class="company-headline">
        <div class="company-title-row">
            <h1><?= e($company['name']) ?></h1>
            <span class="offer-pill tone-<?= e($offer['tone']) ?>"><?= e($offer['label']) ?></span>
        </div>

        <p class="company-ref">
            <span class="ref-badge"><?= e($company['reference']) ?></span>
            <?php if ($company['registration_number']): ?>
                <span class="muted">Reg. <?= e($company['registration_number']) ?></span>
            <?php endif; ?>
        </p>

        <?php if (!empty($company['summary'])): ?>
            <p class="company-summary"><?= nl2br(e($company['summary'])) ?></p>
        <?php else: ?>
            <p class="company-summary muted">
                A single-asset company holding <?= $assetName !== '' ? e($assetName) : 'one registered asset' ?><?php
                if ($primaryActivity): ?>, operating in <?= e($primaryActivity['activity_type']) ?><?php
                endif; ?>. Every figure on this page comes from the company's own filed records.
            </p>
        <?php endif; ?>

        <div class="company-facts">
            <?php if ($primaryActivity): ?>
                <div class="company-fact">
                    <span class="fact-icon"><i class="ti ti-briefcase" aria-hidden="true"></i></span>
                    <span>
                        <span class="fact-label">Commercial activity</span>
                        <span class="fact-value"><?= e($primaryActivity['activity_type']) ?></span>
                    </span>
                </div>
                <?php if ($primaryActivity['location']): ?>
                <div class="company-fact">
                    <span class="fact-icon"><i class="ti ti-map-pin" aria-hidden="true"></i></span>
                    <span>
                        <span class="fact-label">Operating area</span>
                        <span class="fact-value"><?= e($primaryActivity['location']) ?></span>
                    </span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($assetName !== ''): ?>
                <div class="company-fact">
                    <span class="fact-icon"><i class="ti ti-car" aria-hidden="true"></i></span>
                    <span>
                        <span class="fact-label">Asset</span>
                        <span class="fact-value"><?= e($assetName) ?><?= $asset['year'] ? ' &middot; ' . (int) $asset['year'] : '' ?></span>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($offer['opens'] || $offer['closes']): ?>
        <div class="offer-window">
            <div class="offer-window-cell">
                <span class="offer-window-icon"><i class="ti ti-lock-open" aria-hidden="true"></i></span>
                <span>
                    <span class="fact-label">Offer opens</span>
                    <span class="fact-value"><?= $offer['opens'] ? e(\app\services\OfferWindow::humanDate($offer['opens'])) : 'On listing' ?></span>
                </span>
            </div>
            <div class="offer-window-cell">
                <span class="offer-window-icon"><i class="ti ti-lock" aria-hidden="true"></i></span>
                <span>
                    <span class="fact-label">Offer closes</span>
                    <span class="fact-value"><?= $offer['closes'] ? e(\app\services\OfferWindow::humanDate($offer['closes'])) : 'When fully subscribed' ?></span>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php require __DIR__ . '/partials/_invest-card.php'; ?>
</section>

<div class="tabs company-tabs">
    <button class="tab-btn active" data-tab="overview"><i class="ti ti-list-details" aria-hidden="true"></i> Overview</button>
    <button class="tab-btn" data-tab="asset"><i class="ti ti-car" aria-hidden="true"></i> The asset</button>
    <button class="tab-btn" data-tab="financials"><i class="ti ti-report-money" aria-hidden="true"></i> Financials</button>
    <button class="tab-btn" data-tab="governance"><i class="ti ti-users" aria-hidden="true"></i> Governance</button>
    <button class="tab-btn" data-tab="documents"><i class="ti ti-file-text" aria-hidden="true"></i> Documents</button>
</div>

<!-- ============================================================
     Overview
     ============================================================ -->
<div id="tab-overview" class="tab-panel">
    <div class="panel-card">
        <div class="panel-split">
            <div class="panel-main">
                <h2>About this company</h2>

                <?php if (!empty($company['investment_case'])): ?>
                    <div class="prose"><?= nl2br(e($company['investment_case'])) ?></div>
                <?php else: ?>
                    <p class="muted">
                        No written case has been published for this company yet. The asset record,
                        trading history and documents below are the full disclosure.
                    </p>
                <?php endif; ?>

                <h3>How the asset earns</h3>
                <?php if ($activities): ?>
                    <ul class="activity-history">
                    <?php foreach ($activities as $a): ?>
                        <li>
                            <span class="activity-name"><?= e($a['activity_type']) ?></span>
                            <span class="muted">
                                <?= e($a['location']) ?> &middot; since <?= e($a['start_date']) ?><?= $a['end_date'] ? ', ended ' . e($a['end_date']) : '' ?>
                                <?php if ($a['operator']): ?> &middot; operated by <?= e($a['operator']) ?><?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                    <?php if (count($activities) > 1): ?>
                        <p class="muted small-note">
                            More than one entry means the asset has changed the kind of work it does.
                            Earlier periods are kept rather than overwritten.
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="muted">No commercial activity recorded yet.</p>
                <?php endif; ?>

                <div class="share-summary">
                    <h3>Share structure</h3>
                    <table class="detail-table">
                        <tbody>
                            <tr><th scope="row">Shares issued</th><td><?= number_format((int) $company['shares_issued']) ?></td></tr>
                            <tr><th scope="row">Committed or held</th><td><?= number_format($sharesTaken) ?></td></tr>
                            <tr><th scope="row">Available now</th><td><?= number_format($sharesAvailable) ?></td></tr>
                            <tr><th scope="row">NAV per share</th><td>R<?= number_format((float) $company['nav_per_share'], 2) ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel-aside">
                <h3>Company updates</h3>
                <?php if ($updates): ?>
                    <ol class="timeline">
                    <?php foreach ($updates as $u): ?>
                        <li class="timeline-item">
                            <span class="timeline-dot" aria-hidden="true"></span>
                            <p class="timeline-title"><?= e($u['title']) ?></p>
                            <p class="timeline-date muted"><?= e(date('j F Y', strtotime($u['happened_on']))) ?></p>
                            <?php if ($u['body']): ?>
                                <p class="timeline-body muted"><?= nl2br(e($u['body'])) ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p class="muted">
                        Nothing recorded yet. Milestones appear here as they happen &mdash; the
                        offer opening, the asset being delivered and licensed, each trading
                        period filed.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     The asset
     ============================================================ -->
<div id="tab-asset" class="tab-panel" style="display:none;">
    <div class="panel-card">
        <?php if ($asset): ?>
            <h2><?= $assetName !== '' ? e($assetName) : 'Registered asset' ?></h2>

            <div class="asset-detail-grid">
                <table class="detail-table">
                    <tbody>
                        <tr><th scope="row">Make and model</th><td><?= e($assetName) ?: '&mdash;' ?></td></tr>
                        <tr><th scope="row">Year</th><td><?= $asset['year'] ? (int) $asset['year'] : '&mdash;' ?></td></tr>
                        <tr><th scope="row">VIN</th><td class="is-mono"><?= e($asset['vin']) ?: '&mdash;' ?></td></tr>
                        <tr><th scope="row">Registration</th><td class="is-mono"><?= e($asset['registration_number']) ?: '&mdash;' ?></td></tr>
                        <tr><th scope="row">Mileage</th><td><?= $asset['mileage'] ? number_format((int) $asset['mileage']) . ' km' : '&mdash;' ?></td></tr>
                    </tbody>
                </table>

                <table class="detail-table">
                    <tbody>
                        <tr><th scope="row">Purchase price</th><td>R<?= number_format((float) $asset['purchase_price'], 0) ?></td></tr>
                        <tr><th scope="row">Purchase date</th><td><?= $asset['purchase_date'] ? e(date('j M Y', strtotime($asset['purchase_date']))) : '&mdash;' ?></td></tr>
                        <tr><th scope="row">Current valuation</th><td>R<?= number_format((float) $asset['current_valuation'], 0) ?></td></tr>
                        <tr><th scope="row">Valued on</th><td><?= $asset['valuation_date'] ? e(date('j M Y', strtotime($asset['valuation_date']))) : '&mdash;' ?></td></tr>
                        <tr><th scope="row">Status</th><td><span class="status-badge status-<?= e($asset['asset_status']) ?>"><?= e($asset['asset_status']) ?></span></td></tr>
                    </tbody>
                </table>
            </div>

            <h3>Compliance</h3>
            <div class="compliance-row">
                <div class="compliance-item">
                    <span class="compliance-label">Insurance</span>
                    <span class="compliance-value"><?= e($asset['insurance_status']) ?: 'Not recorded' ?></span>
                </div>
                <div class="compliance-item">
                    <span class="compliance-label">Roadworthy</span>
                    <span class="compliance-value"><?= e($asset['roadworthy_status']) ?: 'Not recorded' ?></span>
                </div>
            </div>

            <?php if ($asset['valuation_date'] && strtotime($asset['valuation_date']) < strtotime('-12 months')): ?>
                <p class="stale-note">
                    <i class="ti ti-alert-triangle" aria-hidden="true"></i>
                    This valuation is more than a year old. NAV per share is derived from it, so
                    treat the figure as indicative until it's refreshed.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p class="muted"><i class="ti ti-car-off" aria-hidden="true"></i> No asset on record yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     Financials
     ============================================================ -->
<div id="tab-financials" class="tab-panel" style="display:none;">
    <div class="panel-card">
        <?php if ($latestPeriod): ?>
            <h2>Trading history</h2>

            <div class="figure-row">
                <div class="figure">
                    <span class="figure-value">R<?= number_format((float) $ttm['revenue'], 0) ?></span>
                    <span class="figure-label">Revenue, last 12 months</span>
                </div>
                <div class="figure">
                    <span class="figure-value">R<?= number_format((float) $ttm['profit'], 0) ?></span>
                    <span class="figure-label">Net operating income, last 12 months</span>
                </div>
                <div class="figure">
                    <span class="figure-value">R<?= number_format((float) $company['arf_balance'], 0) ?></span>
                    <span class="figure-label">
                        Replacement fund
                        <?php if ((float) $company['arf_target'] > 0): ?>
                            <span class="muted">of R<?= number_format((float) $company['arf_target'], 0) ?></span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <?php if ((float) $company['arf_target'] > 0): ?>
                <?php $arfPct = min(100, round(((float) $company['arf_balance'] / (float) $company['arf_target']) * 100)); ?>
                <div class="funded-bar" role="progressbar" aria-valuenow="<?= $arfPct ?>" aria-valuemin="0" aria-valuemax="100"
                     aria-label="Replacement fund progress">
                    <div class="funded-bar-fill is-arf" style="width:<?= $arfPct ?>%;"></div>
                </div>
                <p class="muted small-note"><?= $arfPct ?>% of the replacement target set aside.</p>
            <?php endif; ?>

            <h3>Filed periods</h3>
            <div class="table-scroll">
                <table class="detail-table periods-table">
                    <thead>
                        <tr>
                            <th scope="col">Period</th>
                            <th scope="col">Revenue</th>
                            <th scope="col">Operating costs</th>
                            <th scope="col">Net operating income</th>
                            <th scope="col">To replacement fund</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($periods as $p): ?>
                        <tr>
                            <th scope="row"><?= e(date('M Y', strtotime($p['period_start']))) ?></th>
                            <td>R<?= number_format((float) $p['gross_revenue'], 0) ?></td>
                            <td>R<?= number_format((float) $p['operating_costs'], 0) ?></td>
                            <td class="<?= (float) $p['net_operating_income'] < 0 ? 'is-negative' : '' ?>">
                                R<?= number_format((float) $p['net_operating_income'], 0) ?>
                            </td>
                            <td>R<?= number_format((float) $p['arf_allocation'], 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="muted small-note">
                These are reported figures, not forecasts. Past periods say what the asset has
                earned; they don't promise what it will.
            </p>
        <?php else: ?>
            <h2>Trading history</h2>
            <p class="muted">
                <i class="ti ti-report-money" aria-hidden="true"></i>
                No financial periods filed yet. That's normal for a company whose asset hasn't
                started trading &mdash; but it does mean there's no earnings record to judge this
                one on.
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     Governance
     ============================================================ -->
<div id="tab-governance" class="tab-panel" style="display:none;">
    <div class="panel-card">
        <h2>The corporate record</h2>
        <table class="detail-table">
            <tbody>
                <tr><th scope="row">Registered name</th><td><?= e($company['name']) ?></td></tr>
                <tr><th scope="row">Registration number</th><td class="is-mono"><?= e($company['registration_number']) ?: '&mdash;' ?></td></tr>
                <tr><th scope="row">Incorporated</th><td><?= $company['incorporation_date'] ? e(date('j F Y', strtotime($company['incorporation_date']))) : '&mdash;' ?></td></tr>
                <tr><th scope="row">Registered address</th><td><?= e($company['registered_address']) ?: '&mdash;' ?></td></tr>
                <tr><th scope="row">Corporate secretary</th><td><?= e($company['corporate_secretary']) ?: '&mdash;' ?></td></tr>
            </tbody>
        </table>

        <h3>Board</h3>
        <?php if ($directors): ?>
            <ul class="director-list">
            <?php foreach ($directors as $d): ?>
                <li>
                    <span class="director-avatar"><i class="ti ti-user" aria-hidden="true"></i></span>
                    <span>
                        <span class="director-name"><?= e($d['full_name']) ?></span>
                        <span class="muted"><?= e($d['role']) ?><?= $d['appointed_date'] ? ' &middot; appointed ' . e(date('M Y', strtotime($d['appointed_date']))) : '' ?></span>
                    </span>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted"><i class="ti ti-users" aria-hidden="true"></i> No directors on record yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     Documents
     ============================================================ -->
<div id="tab-documents" class="tab-panel" style="display:none;">
    <div class="panel-card">
        <h2>Documents</h2>
        <?php if ($documents): ?>
            <ul class="document-list">
            <?php foreach ($documents as $d): ?>
                <li>
                    <a href="/documents/<?= (int) $d['id'] ?>">
                        <span class="doc-icon"><i class="ti ti-file-text" aria-hidden="true"></i></span>
                        <span class="doc-body">
                            <span class="doc-name"><?= e(ucwords(str_replace('_', ' ', $d['doc_type']))) ?></span>
                            <span class="muted">Uploaded <?= e(date('j M Y', strtotime($d['created_at']))) ?></span>
                        </span>
                        <?php if ($d['verified']): ?>
                            <span class="doc-verified"><i class="ti ti-circle-check" aria-hidden="true"></i> Verified</span>
                        <?php endif; ?>
                        <i class="ti ti-download doc-download" aria-hidden="true"></i>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted"><i class="ti ti-file-off" aria-hidden="true"></i> No documents uploaded yet.</p>
        <?php endif; ?>
    </div>
</div>

<script src="/assets/js/company-tabs.js"></script>
<script src="/assets/js/company-gallery.js" defer></script>
