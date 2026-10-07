<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Country list for the phone scan form, plus number normalisation.
 *
 * The list is built from the regions libphonenumber supports, so every
 * country the engine can validate is selectable, and the dialling codes
 * always match what the engine uses. Brunei is pinned to the top because
 * PhishCore is built for Brunei.
 */
class PhoneCountries
{
    public const DEFAULT_REGION = 'BN';

    private const NAMES = [
        'AC' => 'Ascension Island', 'AD' => 'Andorra', 'AE' => 'United Arab Emirates', 'AF' => 'Afghanistan',
        'AG' => 'Antigua & Barbuda', 'AI' => 'Anguilla', 'AL' => 'Albania', 'AM' => 'Armenia', 'AO' => 'Angola',
        'AR' => 'Argentina', 'AS' => 'American Samoa', 'AT' => 'Austria', 'AU' => 'Australia', 'AW' => 'Aruba',
        'AX' => 'Åland Islands', 'AZ' => 'Azerbaijan', 'BA' => 'Bosnia & Herzegovina', 'BB' => 'Barbados',
        'BD' => 'Bangladesh', 'BE' => 'Belgium', 'BF' => 'Burkina Faso', 'BG' => 'Bulgaria', 'BH' => 'Bahrain',
        'BI' => 'Burundi', 'BJ' => 'Benin', 'BL' => 'St. Barthélemy', 'BM' => 'Bermuda', 'BN' => 'Brunei',
        'BO' => 'Bolivia', 'BQ' => 'Caribbean Netherlands', 'BR' => 'Brazil', 'BS' => 'Bahamas', 'BT' => 'Bhutan',
        'BW' => 'Botswana', 'BY' => 'Belarus', 'BZ' => 'Belize', 'CA' => 'Canada', 'CC' => 'Cocos (Keeling) Islands',
        'CD' => 'Congo (DRC)', 'CF' => 'Central African Republic', 'CG' => 'Congo (Republic)', 'CH' => 'Switzerland',
        'CI' => "Côte d'Ivoire", 'CK' => 'Cook Islands', 'CL' => 'Chile', 'CM' => 'Cameroon', 'CN' => 'China',
        'CO' => 'Colombia', 'CR' => 'Costa Rica', 'CU' => 'Cuba', 'CV' => 'Cape Verde', 'CW' => 'Curaçao',
        'CX' => 'Christmas Island', 'CY' => 'Cyprus', 'CZ' => 'Czechia', 'DE' => 'Germany', 'DJ' => 'Djibouti',
        'DK' => 'Denmark', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'DZ' => 'Algeria', 'EC' => 'Ecuador',
        'EE' => 'Estonia', 'EG' => 'Egypt', 'EH' => 'Western Sahara', 'ER' => 'Eritrea', 'ES' => 'Spain',
        'ET' => 'Ethiopia', 'FI' => 'Finland', 'FJ' => 'Fiji', 'FK' => 'Falkland Islands', 'FM' => 'Micronesia',
        'FO' => 'Faroe Islands', 'FR' => 'France', 'GA' => 'Gabon', 'GB' => 'United Kingdom', 'GD' => 'Grenada',
        'GE' => 'Georgia', 'GF' => 'French Guiana', 'GG' => 'Guernsey', 'GH' => 'Ghana', 'GI' => 'Gibraltar',
        'GL' => 'Greenland', 'GM' => 'Gambia', 'GN' => 'Guinea', 'GP' => 'Guadeloupe', 'GQ' => 'Equatorial Guinea',
        'GR' => 'Greece', 'GT' => 'Guatemala', 'GU' => 'Guam', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana',
        'HK' => 'Hong Kong', 'HN' => 'Honduras', 'HR' => 'Croatia', 'HT' => 'Haiti', 'HU' => 'Hungary',
        'ID' => 'Indonesia', 'IE' => 'Ireland', 'IL' => 'Israel', 'IM' => 'Isle of Man', 'IN' => 'India',
        'IO' => 'British Indian Ocean Territory', 'IQ' => 'Iraq', 'IR' => 'Iran', 'IS' => 'Iceland', 'IT' => 'Italy',
        'JE' => 'Jersey', 'JM' => 'Jamaica', 'JO' => 'Jordan', 'JP' => 'Japan', 'KE' => 'Kenya', 'KG' => 'Kyrgyzstan',
        'KH' => 'Cambodia', 'KI' => 'Kiribati', 'KM' => 'Comoros', 'KN' => 'St. Kitts & Nevis', 'KP' => 'North Korea',
        'KR' => 'South Korea', 'KW' => 'Kuwait', 'KY' => 'Cayman Islands', 'KZ' => 'Kazakhstan', 'LA' => 'Laos',
        'LB' => 'Lebanon', 'LC' => 'St. Lucia', 'LI' => 'Liechtenstein', 'LK' => 'Sri Lanka', 'LR' => 'Liberia',
        'LS' => 'Lesotho', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'LV' => 'Latvia', 'LY' => 'Libya',
        'MA' => 'Morocco', 'MC' => 'Monaco', 'MD' => 'Moldova', 'ME' => 'Montenegro', 'MF' => 'St. Martin',
        'MG' => 'Madagascar', 'MH' => 'Marshall Islands', 'MK' => 'North Macedonia', 'ML' => 'Mali',
        'MM' => 'Myanmar (Burma)', 'MN' => 'Mongolia', 'MO' => 'Macao', 'MP' => 'Northern Mariana Islands',
        'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MS' => 'Montserrat', 'MT' => 'Malta', 'MU' => 'Mauritius',
        'MV' => 'Maldives', 'MW' => 'Malawi', 'MX' => 'Mexico', 'MY' => 'Malaysia', 'MZ' => 'Mozambique',
        'NA' => 'Namibia', 'NC' => 'New Caledonia', 'NE' => 'Niger', 'NF' => 'Norfolk Island', 'NG' => 'Nigeria',
        'NI' => 'Nicaragua', 'NL' => 'Netherlands', 'NO' => 'Norway', 'NP' => 'Nepal', 'NR' => 'Nauru', 'NU' => 'Niue',
        'NZ' => 'New Zealand', 'OM' => 'Oman', 'PA' => 'Panama', 'PE' => 'Peru', 'PF' => 'French Polynesia',
        'PG' => 'Papua New Guinea', 'PH' => 'Philippines', 'PK' => 'Pakistan', 'PL' => 'Poland',
        'PM' => 'St. Pierre & Miquelon', 'PR' => 'Puerto Rico', 'PS' => 'Palestine', 'PT' => 'Portugal', 'PW' => 'Palau',
        'PY' => 'Paraguay', 'QA' => 'Qatar', 'RE' => 'Réunion', 'RO' => 'Romania', 'RS' => 'Serbia', 'RU' => 'Russia',
        'RW' => 'Rwanda', 'SA' => 'Saudi Arabia', 'SB' => 'Solomon Islands', 'SC' => 'Seychelles', 'SD' => 'Sudan',
        'SE' => 'Sweden', 'SG' => 'Singapore', 'SH' => 'St. Helena', 'SI' => 'Slovenia', 'SJ' => 'Svalbard & Jan Mayen',
        'SK' => 'Slovakia', 'SL' => 'Sierra Leone', 'SM' => 'San Marino', 'SN' => 'Senegal', 'SO' => 'Somalia',
        'SR' => 'Suriname', 'SS' => 'South Sudan', 'ST' => 'São Tomé & Príncipe', 'SV' => 'El Salvador',
        'SX' => 'Sint Maarten', 'SY' => 'Syria', 'SZ' => 'Eswatini', 'TA' => 'Tristan da Cunha',
        'TC' => 'Turks & Caicos Islands', 'TD' => 'Chad', 'TG' => 'Togo', 'TH' => 'Thailand', 'TJ' => 'Tajikistan',
        'TK' => 'Tokelau', 'TL' => 'Timor-Leste', 'TM' => 'Turkmenistan', 'TN' => 'Tunisia', 'TO' => 'Tonga',
        'TR' => 'Türkiye', 'TT' => 'Trinidad & Tobago', 'TV' => 'Tuvalu', 'TW' => 'Taiwan', 'TZ' => 'Tanzania',
        'UA' => 'Ukraine', 'UG' => 'Uganda', 'US' => 'United States', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan',
        'VA' => 'Vatican City', 'VC' => 'St. Vincent & Grenadines', 'VE' => 'Venezuela', 'VG' => 'British Virgin Islands',
        'VI' => 'U.S. Virgin Islands', 'VN' => 'Vietnam', 'VU' => 'Vanuatu', 'WF' => 'Wallis & Futuna', 'WS' => 'Samoa',
        'XK' => 'Kosovo', 'YE' => 'Yemen', 'YT' => 'Mayotte', 'ZA' => 'South Africa', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe',
    ];

    /**
     * Every selectable country as ['code', 'name', 'dial'], Brunei first and
     * the rest alphabetical by name.
     *
     * @return array<int, array{code: string, name: string, dial: string}>
     */
    public static function all(): array
    {
        static $list = null;

        if ($list !== null) {
            return $list;
        }

        $util = PhoneNumberUtil::getInstance();
        $items = [];

        foreach ($util->getSupportedRegions() as $code) {
            $items[] = [
                'code' => $code,
                'name' => self::nameFor($code),
                'dial' => (string) $util->getCountryCodeForRegion($code),
            ];
        }

        usort($items, fn ($a, $b) => strcasecmp(self::sortKey($a['name']), self::sortKey($b['name'])));

        $home = array_values(array_filter($items, fn ($i) => $i['code'] === self::DEFAULT_REGION));
        $rest = array_values(array_filter($items, fn ($i) => $i['code'] !== self::DEFAULT_REGION));

        return $list = array_merge($home, $rest);
    }

    /** @return array<int, string> */
    public static function codes(): array
    {
        return array_column(self::all(), 'code');
    }

    public static function isValid(?string $region): bool
    {
        return $region !== null && in_array(strtoupper($region), self::codes(), true);
    }

    /**
     * Turns what the user typed into one consistent international number.
     *
     * A number that already starts with "+" (or an international prefix such
     * as "00") keeps its own country, whatever was selected. A local-style
     * number is read as belonging to the selected country, which also strips
     * local trunk prefixes (e.g. the leading 0 on a Malaysian number).
     * Anything libphonenumber cannot parse is returned as typed so the
     * engine can flag it as invalid instead of it being silently dropped.
     */
    public static function normalise(string $input, ?string $region = null): string
    {
        $input = trim($input);
        $region = self::isValid($region) ? strtoupper($region) : self::DEFAULT_REGION;
        $util = PhoneNumberUtil::getInstance();

        try {
            return $util->format($util->parse($input, $region), PhoneNumberFormat::INTERNATIONAL);
        } catch (NumberParseException) {
            return $input;
        }
    }

    private static function nameFor(string $code): string
    {
        if (isset(self::NAMES[$code])) {
            return self::NAMES[$code];
        }

        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayRegion('-'.$code, 'en');

            if ($name !== '' && $name !== $code) {
                return $name;
            }
        }

        return $code;
    }

    private static function sortKey(string $name): string
    {
        return strtr($name, [
            'Å' => 'A', 'ô' => 'o', 'é' => 'e', 'ç' => 'c', 'ã' => 'a', 'í' => 'i', 'ü' => 'u',
        ]);
    }
}
