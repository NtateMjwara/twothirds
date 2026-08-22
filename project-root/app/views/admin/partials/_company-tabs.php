<?php
/**
 * Sub-navigation shared by every per-company admin screen.
 *
 * Expects: $company, and $currentTab as one of the keys below.
 *
 * These screens each used to link "back" to the *public* company page, which
 * dropped an admin out of the admin entirely in the middle of a task. The
 * breadcrumb now goes back into the admin, with the public page as an explicit
 * second link that opens in a new tab.
 */
$tabs = [
    'edit'       => ['label' => 'Details',    'icon' => 'ti-pencil'],
    'images'     => ['label' => 'Photos',     'icon' => 'ti-photo'],
    'updates'    => ['label' => 'Updates',    'icon' => 'ti-timeline'],
    'financials' => ['label' => 'Financials', 'icon' => 'ti-report-money'],
    'documents'  => ['label' => 'Documents',  'icon' => 'ti-file-text'],
    // Sits with the company's own records rather than beside the public-facing
    // tabs: it is the one screen here that never reaches an investor's eyes
    // except as a line on an invoice.
    'banking'    => ['label' => 'Banking',    'icon' => 'ti-building-bank'],
    'directors'  => ['label' => 'Directors',  'icon' => 'ti-users'],
];
$currentTab = $currentTab ?? '';
?>
<p class="admin-breadcrumb">
    <a href="/admin/companies"><i class="ti ti-chevron-left" aria-hidden="true"></i> All companies</a>
    <span aria-hidden="true">&middot;</span>
    <a href="<?= e(company_url($company)) ?>" target="_blank" rel="noopener">
        View public page <i class="ti ti-external-link" aria-hidden="true"></i>
    </a>
</p>

<div class="page-title-row">
    <h1><?= e($company['name']) ?></h1>
    <span class="ref-badge"><?= e($company['reference']) ?></span>
    <?php if (!empty($company['status'])): ?>
        <span class="status-badge status-<?= e($company['status']) ?>"><?= e($company['status']) ?></span>
    <?php endif; ?>
</div>

<nav class="company-tab-nav" aria-label="Company sections">
    <?php foreach ($tabs as $key => $tab): ?>
        <a href="/admin/companies/<?= e($company['reference']) ?>/<?= e($key) ?>"
           class="company-tab<?= $key === $currentTab ? ' is-active' : '' ?>"
           <?= $key === $currentTab ? 'aria-current="page"' : '' ?>>
            <i class="ti <?= e($tab['icon']) ?>" aria-hidden="true"></i> <?= e($tab['label']) ?>
        </a>
    <?php endforeach; ?>
</nav>
