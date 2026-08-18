<?php
namespace app\services;

/**
 * The controlled lists behind the account forms.
 *
 * Kept in one place rather than inline in five views: the same option set is
 * needed by the form that writes it, the validator that checks it and the
 * summary that displays it, and three copies of a list drift.
 *
 * Every list here is also the whitelist. A select element constrains a browser,
 * not a request, so `AccountController` checks submitted values against these
 * arrays before writing them.
 */
class ProfileOptions
{
    public static function titles(): array
    {
        return ['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Prof', 'Adv', 'Rev'];
    }

    public static function genders(): array
    {
        return [
            'male'        => 'Male',
            'female'      => 'Female',
            'other'       => 'Other',
            'undisclosed' => 'Prefer not to say',
        ];
    }

    /**
     * Marital status is asked because matrimonial property regime affects who
     * can bind an estate. "Married" on its own doesn't answer that, which is
     * why the two regimes are separate options.
     */
    public static function maritalStatuses(): array
    {
        return [
            'single'       => 'Single',
            'married_cop'  => 'Married in community of property',
            'married_anc'  => 'Married out of community of property (ANC)',
            'partnership'  => 'Life partnership',
            'divorced'     => 'Divorced',
            'widowed'      => 'Widowed',
        ];
    }

    public static function provinces(): array
    {
        return [
            'Eastern Cape', 'Free State', 'Gauteng', 'KwaZulu-Natal', 'Limpopo',
            'Mpumalanga', 'Northern Cape', 'North West', 'Western Cape',
        ];
    }

    /**
     * Deliberately short. A full ISO list is 249 entries of noise for a platform
     * whose assets and investors are almost entirely South African; these are
     * the countries that actually come up, with an "Other" escape.
     */
    public static function countries(): array
    {
        return [
            'South Africa', 'Botswana', 'Lesotho', 'Eswatini', 'Namibia',
            'Zimbabwe', 'Mozambique', 'Zambia', 'Malawi',
            'United Kingdom', 'United States', 'Australia', 'Germany',
            'Netherlands', 'India', 'China', 'Nigeria', 'Kenya', 'Other',
        ];
    }

    public static function callingCodes(): array
    {
        return [
            '+27'  => 'South Africa (+27)',
            '+267' => 'Botswana (+267)',
            '+266' => 'Lesotho (+266)',
            '+268' => 'Eswatini (+268)',
            '+264' => 'Namibia (+264)',
            '+263' => 'Zimbabwe (+263)',
            '+258' => 'Mozambique (+258)',
            '+260' => 'Zambia (+260)',
            '+44'  => 'United Kingdom (+44)',
            '+1'   => 'United States / Canada (+1)',
            '+61'  => 'Australia (+61)',
        ];
    }

    // ------------------------------------------------------------
    // FICA
    // ------------------------------------------------------------

    public static function incomeSources(): array
    {
        return [
            'salary'        => 'Salary or wages',
            'business'      => 'Business income',
            'savings'       => 'Savings',
            'investments'   => 'Investment returns',
            'pension'       => 'Pension or annuity',
            'rental'        => 'Rental income',
            'inheritance'   => 'Inheritance',
            'grant'         => 'Government grant',
            'commission'    => 'Commission',
            'other'         => 'Other',
        ];
    }

    /**
     * Where the money for this account comes from, which is not always the same
     * as how the person earns. Someone on a salary may be investing an
     * inheritance, and the distinction is the point of asking twice.
     */
    public static function fundSources(): array
    {
        return [
            'salary'      => 'Salary or wages',
            'savings'     => 'Savings',
            'business'    => 'Business income',
            'investments' => 'Sale of investments',
            'inheritance' => 'Inheritance',
            'property'    => 'Sale of property',
            'gift'        => 'Gift or donation',
            'loan'        => 'Loan',
            'other'       => 'Other',
        ];
    }

    public static function occupations(): array
    {
        return [
            'employed_full'  => 'Employed full-time',
            'employed_part'  => 'Employed part-time',
            'self_employed'  => 'Self-employed / consultant / entrepreneur',
            'director'       => 'Company director',
            'student'        => 'Student',
            'retired'        => 'Retired',
            'unemployed'     => 'Not currently employed',
            'homemaker'      => 'Homemaker',
        ];
    }

    /** Bands, and the top one is open-ended rather than pretending to a ceiling. */
    public static function incomeBands(): array
    {
        return [
            '0-95750'        => 'R0 – R95,750',
            '95751-189880'   => 'R95,751 – R189,880',
            '189881-370500'  => 'R189,881 – R370,500',
            '370501-512800'  => 'R370,501 – R512,800',
            '512801-673000'  => 'R512,801 – R673,000',
            '673001-1817000' => 'R673,001 – R1,817,000',
            '1817001+'       => 'More than R1,817,000',
        ];
    }

    public static function industries(): array
    {
        return [
            'agriculture'    => 'Agriculture, forestry and fishing',
            'mining'         => 'Mining and quarrying',
            'manufacturing'  => 'Manufacturing',
            'utilities'      => 'Electricity, gas and water',
            'construction'   => 'Construction',
            'retail'         => 'Wholesale and retail trade',
            'transport'      => 'Transport and logistics',
            'hospitality'    => 'Hospitality and tourism',
            'ict'            => 'Information and communications technology',
            'financial'      => 'Financial services',
            'insurance'      => 'Insurance',
            'realestate'     => 'Real estate',
            'professional'   => 'Professional and technical services',
            'public'         => 'Government and public sector',
            'education'      => 'Education',
            'health'         => 'Health and social work',
            'arts'           => 'Arts, entertainment and media',
            'ngo'            => 'Non-profit and NGO',
            'domestic'       => 'Domestic and personal services',
            'other'          => 'Other',
        ];
    }

    /**
     * South African universal branch codes, used to prefill the branch field.
     *
     * These are a convenience, not a source of truth: the field stays editable
     * because some accounts still use a physical branch code, and a bank can
     * change its universal code. Worth confirming against each bank's current
     * published code before go-live.
     */
    public static function banks(): array
    {
        return [
            'Absa'                 => '632005',
            'African Bank'         => '430000',
            'Bidvest Bank'         => '462005',
            'Capitec Bank'         => '470010',
            'Discovery Bank'       => '679000',
            'First National Bank'  => '250655',
            'Investec Bank'        => '580105',
            'Nedbank'              => '198765',
            'Standard Bank'        => '051001',
            'TymeBank'             => '678910',
            'Other'                => '',
        ];
    }

    public static function accountTypes(): array
    {
        return [
            'cheque'  => 'Cheque / current',
            'savings' => 'Savings',
        ];
    }

    /** How many accounts a user may hold per currency. */
    public const MAX_ACCOUNTS_PER_CURRENCY = 2;

    /**
     * Uppercase without needing mbstring.
     *
     * strtoupper() leaves multi-byte characters alone rather than corrupting
     * them, which is the right degradation for initials: "Ötto" stays "Ö"
     * instead of turning into a broken byte.
     */
    public static function upper(string $value): string
    {
        return function_exists('mb_strtoupper') ? mb_strtoupper($value) : strtoupper($value);
    }

    /**
     * The first character of a string, UTF-8 safe.
     *
     * substr() would cut a multi-byte character in half; the /u regex takes one
     * whole character without requiring an extension to be installed.
     */
    public static function firstLetter(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        return preg_match('/^./u', $value, $m) ? self::upper($m[0]) : '';
    }

    /**
     * True when $value is a key of $options, or $options is a plain list and
     * $value is one of its entries. Used everywhere a submitted select value is
     * written to the database.
     */
    public static function isValid(?string $value, array $options): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        return array_is_list($options)
            ? in_array($value, $options, true)
            : array_key_exists($value, $options);
    }
}
