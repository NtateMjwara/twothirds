<?php
namespace app\services;

/**
 * The offer window.
 *
 * Both dates are optional and mean different things when absent:
 *   no open date    - the offer was open the moment it was listed
 *   no close date   - it stays open until the shares run out
 *
 * That's exactly how listings behaved before the window existed, so companies
 * created before this feature keep working untouched.
 *
 * Availability still has the final say. A fully subscribed company is closed
 * whatever its dates claim, because there is nothing left to sell.
 */
class OfferWindow
{
    public const SCHEDULED = 'scheduled';
    public const OPEN = 'open';
    public const CLOSING = 'closing';
    public const CLOSED = 'closed';
    public const SUBSCRIBED = 'subscribed';

    /** Inside this many hours of the close date, the offer reads as closing. */
    private const CLOSING_SOON_HOURS = 72;

    /**
     * @return array{status: string, label: string, tone: string, opens: ?\DateTimeImmutable,
     *               closes: ?\DateTimeImmutable, can_commit: bool, countdown: ?string}
     */
    public static function for(array $company, int $sharesAvailable): array
    {
        $now = new \DateTimeImmutable('now');
        $opens = self::parse($company['offer_opens_at'] ?? null);
        $closes = self::parse($company['offer_closes_at'] ?? null);

        // Note: array_merge below, not the + union operator. Union keeps the
        // LEFT operand on duplicate keys, so a default here would silently
        // override every computed countdown.
        $state = [
            'opens'     => $opens,
            'closes'    => $closes,
            'countdown' => null,
        ];

        if ($sharesAvailable <= 0) {
            return array_merge($state, [
                'status'     => self::SUBSCRIBED,
                'label'      => 'Fully subscribed',
                'tone'       => 'neutral',
                'can_commit' => false,
            ]);
        }

        if ($opens && $now < $opens) {
            return array_merge($state, [
                'status'     => self::SCHEDULED,
                'label'      => 'Opens ' . self::humanDate($opens),
                'tone'       => 'pending',
                'can_commit' => false,
                'countdown'  => 'Opens in ' . self::distance($now, $opens),
            ]);
        }

        if ($closes && $now >= $closes) {
            return array_merge($state, [
                'status'     => self::CLOSED,
                'label'      => 'Offer closed',
                'tone'       => 'neutral',
                'can_commit' => false,
            ]);
        }

        if ($closes) {
            $hoursLeft = ($closes->getTimestamp() - $now->getTimestamp()) / 3600;
            if ($hoursLeft <= self::CLOSING_SOON_HOURS) {
                return array_merge($state, [
                    'status'     => self::CLOSING,
                    'label'      => 'Closing soon',
                    'tone'       => 'urgent',
                    'can_commit' => true,
                    'countdown'  => 'Closes in ' . self::distance($now, $closes),
                ]);
            }

            return array_merge($state, [
                'status'     => self::OPEN,
                'label'      => 'Open to invest',
                'tone'       => 'positive',
                'can_commit' => true,
                'countdown'  => 'Closes in ' . self::distance($now, $closes),
            ]);
        }

        return array_merge($state, [
            'status'     => self::OPEN,
            'label'      => 'Open to invest',
            'tone'       => 'positive',
            'can_commit' => true,
        ]);
    }

    private static function parse(?string $value): ?\DateTimeImmutable
    {
        if (!$value || str_starts_with($value, '0000')) {
            return null;
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }

    public static function humanDate(\DateTimeImmutable $when): string
    {
        return $when->format('j M Y') . ' at ' . $when->format('H:i');
    }

    /**
     * Rounded to the largest useful unit. "6 days" is more readable than
     * "5 days, 22 hours and 14 minutes", and the exact date is printed
     * alongside for anyone who needs precision.
     */
    private static function distance(\DateTimeImmutable $from, \DateTimeImmutable $to): string
    {
        $seconds = max(0, $to->getTimestamp() - $from->getTimestamp());

        // Always round DOWN. This is a deadline, and telling someone they have
        // more time than they do is the one error with a real cost. Forty hours
        // reads as "1 day", not "2 days"; the exact closing time is printed in
        // the offer window block for anyone who needs it to the minute.
        if ($seconds < 3600) {
            $minutes = max(1, (int) floor($seconds / 60));
            return $minutes . ' minute' . ($minutes === 1 ? '' : 's');
        }
        if ($seconds < 86400) {
            $hours = (int) floor($seconds / 3600);
            return $hours . ' hour' . ($hours === 1 ? '' : 's');
        }

        $days = (int) floor($seconds / 86400);
        return $days . ' day' . ($days === 1 ? '' : 's');
    }
}
