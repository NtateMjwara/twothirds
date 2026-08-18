<?php
/**
 * Account header and tabs.
 *
 * Expects: $user, $profile, $kyc, $complete, $completeCount, $sectionCount,
 *          and $currentTab as one of the keys below.
 *
 * The band is the same on every tab, so someone always knows whose account they
 * are looking at and how much of it is still outstanding - the old pages showed
 * a bare <h1> and nothing else.
 */
$tabs = [
    'profile' => ['label' => 'Personal details', 'href' => '/account/profile', 'icon' => 'ti-user'],
    'address' => ['label' => 'Address',          'href' => '/account/address', 'icon' => 'ti-map-pin'],
    'kyc'     => ['label' => 'KYC',              'href' => '/account/kyc',     'icon' => 'ti-shield-check'],
    'tax'     => ['label' => 'Tax info',         'href' => '/account/tax',     'icon' => 'ti-receipt-tax'],
    'bank'    => ['label' => 'Bank accounts',    'href' => '/account/bank',    'icon' => 'ti-building-bank'],
];

$currentTab = $currentTab ?? 'profile';

$displayName = trim(($profile['preferred_name'] ?? '') ?: ($profile['first_name'] ?? ''));
$fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));

// Initials for the avatar. Falls back to the email so a brand-new account with
// no profile still gets a mark rather than an empty circle.
$initials = '';
if ($profile) {
    $initials = \app\services\ProfileOptions::firstLetter($profile['first_name'] ?? '')
        . \app\services\ProfileOptions::firstLetter($profile['last_name'] ?? '');
}
if (trim($initials) === '') {
    $initials = \app\services\ProfileOptions::upper(substr($user['email'] ?? '?', 0, 2));
}

$kycStatus = $kyc['status'] ?? null;
$progress = $sectionCount > 0 ? round(($completeCount / $sectionCount) * 100) : 0;
?>
<section class="account-band">
    <div class="account-identity">
        <span class="account-avatar" aria-hidden="true"><?= e($initials) ?></span>
        <div>
            <h1 class="account-name"><?= e($fullName !== '' ? $fullName : ($user['email'] ?? 'Your account')) ?></h1>
            <p class="account-since muted">
                <?php if (!empty($user['created_at'])): ?>
                    Member since <?= e(date('j F Y', strtotime($user['created_at']))) ?>
                <?php else: ?>
                    <?= e($user['email'] ?? '') ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <dl class="account-meta">
        <div>
            <dt>Account</dt>
            <dd class="is-mono">TT-<?= e(str_pad((string) ($user['id'] ?? 0), 6, '0', STR_PAD_LEFT)) ?></dd>
        </div>
        <div>
            <dt>Verification</dt>
            <dd>
                <?php if ($kycStatus === 'verified'): ?>
                    <span class="verify-pill is-verified"><i class="ti ti-circle-check" aria-hidden="true"></i> Verified</span>
                <?php elseif ($kycStatus === 'pending'): ?>
                    <span class="verify-pill is-pending"><i class="ti ti-clock" aria-hidden="true"></i> In review</span>
                <?php elseif ($kycStatus === 'rejected'): ?>
                    <span class="verify-pill is-rejected"><i class="ti ti-alert-circle" aria-hidden="true"></i> Action needed</span>
                <?php else: ?>
                    <span class="verify-pill is-none"><i class="ti ti-minus" aria-hidden="true"></i> Not started</span>
                <?php endif; ?>
            </dd>
        </div>
    </dl>
</section>

<?php if ($completeCount < $sectionCount): ?>
    <div class="account-progress">
        <div class="account-progress-text">
            <strong><?= (int) $completeCount ?> of <?= (int) $sectionCount ?></strong> sections complete
            <span class="muted">&mdash; you'll need all of them before you can commit to an offering.</span>
        </div>
        <div class="funded-bar" role="progressbar" aria-valuenow="<?= (int) $progress ?>"
             aria-valuemin="0" aria-valuemax="100" aria-label="Profile completeness">
            <div class="funded-bar-fill" style="width:<?= (int) $progress ?>%;"></div>
        </div>
    </div>
<?php endif; ?>

<nav class="account-tabs" aria-label="Account sections">
    <?php foreach ($tabs as $key => $tab): ?>
        <a href="<?= e($tab['href']) ?>"
           class="account-tab<?= $key === $currentTab ? ' is-active' : '' ?><?= !empty($complete[$key]) ? ' is-done' : '' ?>"
           <?= $key === $currentTab ? 'aria-current="page"' : '' ?>>
            <i class="ti <?= e($tab['icon']) ?>" aria-hidden="true"></i>
            <span><?= e($tab['label']) ?></span>
            <?php if (!empty($complete[$key])): ?>
                <i class="ti ti-circle-check tab-check" aria-hidden="true"></i>
                <span class="sr-only">(complete)</span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>
