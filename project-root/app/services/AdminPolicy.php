<?php
namespace app\services;

/**
 * What each admin role is allowed to do.
 *
 * `admin_users.role` has been an ENUM('ops','finance','super_admin') since the
 * original schema, and the value has been carried in the session since login -
 * but nothing has ever read it. Every admin controller called requireAdmin(),
 * which only checks that *someone* is logged in. In practice that meant an ops
 * account could settle commitments and a finance account could approve identity
 * documents, which is exactly the separation the three roles were created to
 * enforce.
 *
 * The split below follows the obvious lines:
 *
 *   ops          runs the listings and the compliance queue - creating and
 *                editing companies, reviewing KYC, managing documents,
 *                directors, photographs and updates.
 *   finance      moves money and files numbers - the settlement queue and
 *                financial periods.
 *   super_admin  everything, including the audit log.
 *
 * Read access to investors, the registry and the email queue is shared: it's
 * support work both roles need, and none of it changes anything.
 *
 * An unrecognised role gets read-only access rather than nothing, so a future
 * role added to the ENUM without a matching entry here degrades to harmless
 * instead of locking someone out of a page they need to see.
 */
class AdminPolicy
{
    public const SUPER_ADMIN = 'super_admin';

    private const READ_ONLY = [
        'dashboard.view',
        'company.view',
        'investor.view',
        'registry.view',
        'email.view',
    ];

    private const ROLES = [
        'ops' => [
            'dashboard.view',
            'company.view',
            'company.manage',
            'document.manage',
            'director.manage',
            'media.manage',
            'kyc.view',
            'kyc.act',
            'kyc.reveal',
            'investor.view',
            'registry.view',
            'email.view',
        ],
        'finance' => [
            'dashboard.view',
            'company.view',
            'settlement.view',
            'settlement.act',
            'financials.manage',
            'investor.view',
            'registry.view',
            'email.view',
        ],
    ];

    public static function can(?string $role, string $permission): bool
    {
        if ($role === self::SUPER_ADMIN) {
            return true;
        }

        $granted = self::ROLES[$role] ?? self::READ_ONLY;

        return in_array($permission, $granted, true);
    }

    /** Human label for the role badge in the admin bar. */
    public static function label(?string $role): string
    {
        return match ($role) {
            'ops' => 'Operations',
            'finance' => 'Finance',
            self::SUPER_ADMIN => 'Super admin',
            default => 'Restricted',
        };
    }

    /**
     * The admin navigation, filtered to what this role can actually reach.
     *
     * Kept here rather than in the layout so the menu and the gate can't drift:
     * a link only appears if the same permission that guards the controller
     * says it should.
     */
    public static function navigation(?string $role): array
    {
        $items = [
            ['label' => 'Dashboard',  'href' => '/admin',             'icon' => 'ti-layout-dashboard', 'permission' => 'dashboard.view'],
            ['label' => 'Companies',  'href' => '/admin/companies',   'icon' => 'ti-building',         'permission' => 'company.view'],
            ['label' => 'Settlement', 'href' => '/admin/settlement',  'icon' => 'ti-cash',             'permission' => 'settlement.view'],
            ['label' => 'KYC',        'href' => '/admin/kyc',         'icon' => 'ti-shield-check',     'permission' => 'kyc.view'],
            ['label' => 'Investors',  'href' => '/admin/investors',   'icon' => 'ti-users',            'permission' => 'investor.view'],
            ['label' => 'Registry',   'href' => '/admin/registry',    'icon' => 'ti-list-details',     'permission' => 'registry.view'],
            ['label' => 'Email queue','href' => '/admin/email-queue', 'icon' => 'ti-mail',             'permission' => 'email.view'],
        ];

        return array_values(array_filter(
            $items,
            static fn (array $item) => self::can($role, $item['permission'])
        ));
    }
}
