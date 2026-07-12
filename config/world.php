<?php

/*
|--------------------------------------------------------------------------
| World data: countries, currencies, languages
|--------------------------------------------------------------------------
| Single source of truth consumed by config/platform.php and the country
| seeder. FX `rate` is units of that currency per 1 base (XAF) — display only.
*/

// Languages offered in the header selector (native names).
$locales = [
    'en' => 'English', 'fr' => 'Français', 'es' => 'Español', 'pt' => 'Português',
    'ar' => 'العربية', 'zh' => '中文', 'sw' => 'Kiswahili', 'de' => 'Deutsch',
    'it' => 'Italiano', 'ru' => 'Русский', 'hi' => 'हिन्दी', 'ja' => '日本語',
    'ko' => '한국어', 'tr' => 'Türkçe', 'nl' => 'Nederlands', 'pl' => 'Polski',
    'uk' => 'Українська', 'ro' => 'Română', 'el' => 'Ελληνικά', 'sv' => 'Svenska',
    'no' => 'Norsk', 'da' => 'Dansk', 'fi' => 'Suomi', 'cs' => 'Čeština',
    'hu' => 'Magyar', 'fa' => 'فارسی', 'ur' => 'اردو', 'id' => 'Bahasa Indonesia',
    'vi' => 'Tiếng Việt', 'th' => 'ไทย', 'ms' => 'Bahasa Melayu', 'am' => 'አማርኛ',
    'ha' => 'Hausa', 'yo' => 'Yorùbá', 'ig' => 'Igbo', 'zu' => 'isiZulu',
    'af' => 'Afrikaans', 'so' => 'Soomaali', 'bn' => 'বাংলা', 'ta' => 'தமிழ்', 'he' => 'עברית',
];

// iso2 => [name, dial, currency, locale]
$c = [
    // ---- Africa ----
    'DZ' => ['Algeria', '+213', 'DZD', 'ar'], 'AO' => ['Angola', '+244', 'AOA', 'pt'],
    'BJ' => ['Benin', '+229', 'XOF', 'fr'], 'BW' => ['Botswana', '+267', 'BWP', 'en'],
    'BF' => ['Burkina Faso', '+226', 'XOF', 'fr'], 'BI' => ['Burundi', '+257', 'BIF', 'fr'],
    'CV' => ['Cabo Verde', '+238', 'CVE', 'pt'], 'CM' => ['Cameroon', '+237', 'XAF', 'fr'],
    'CF' => ['Central African Republic', '+236', 'XAF', 'fr'], 'TD' => ['Chad', '+235', 'XAF', 'fr'],
    'KM' => ['Comoros', '+269', 'KMF', 'fr'], 'CG' => ['Congo', '+242', 'XAF', 'fr'],
    'CD' => ['DR Congo', '+243', 'CDF', 'fr'], 'CI' => ["Côte d'Ivoire", '+225', 'XOF', 'fr'],
    'DJ' => ['Djibouti', '+253', 'DJF', 'fr'], 'EG' => ['Egypt', '+20', 'EGP', 'ar'],
    'GQ' => ['Equatorial Guinea', '+240', 'XAF', 'es'], 'ER' => ['Eritrea', '+291', 'ERN', 'ar'],
    'SZ' => ['Eswatini', '+268', 'SZL', 'en'], 'ET' => ['Ethiopia', '+251', 'ETB', 'am'],
    'GA' => ['Gabon', '+241', 'XAF', 'fr'], 'GM' => ['Gambia', '+220', 'GMD', 'en'],
    'GH' => ['Ghana', '+233', 'GHS', 'en'], 'GN' => ['Guinea', '+224', 'GNF', 'fr'],
    'GW' => ['Guinea-Bissau', '+245', 'XOF', 'pt'], 'KE' => ['Kenya', '+254', 'KES', 'sw'],
    'LS' => ['Lesotho', '+266', 'LSL', 'en'], 'LR' => ['Liberia', '+231', 'LRD', 'en'],
    'LY' => ['Libya', '+218', 'LYD', 'ar'], 'MG' => ['Madagascar', '+261', 'MGA', 'fr'],
    'MW' => ['Malawi', '+265', 'MWK', 'en'], 'ML' => ['Mali', '+223', 'XOF', 'fr'],
    'MR' => ['Mauritania', '+222', 'MRU', 'ar'], 'MU' => ['Mauritius', '+230', 'MUR', 'en'],
    'MA' => ['Morocco', '+212', 'MAD', 'ar'], 'MZ' => ['Mozambique', '+258', 'MZN', 'pt'],
    'NA' => ['Namibia', '+264', 'NAD', 'en'], 'NE' => ['Niger', '+227', 'XOF', 'fr'],
    'NG' => ['Nigeria', '+234', 'NGN', 'en'], 'RW' => ['Rwanda', '+250', 'RWF', 'en'],
    'ST' => ['São Tomé and Príncipe', '+239', 'STN', 'pt'], 'SN' => ['Senegal', '+221', 'XOF', 'fr'],
    'SC' => ['Seychelles', '+248', 'SCR', 'en'], 'SL' => ['Sierra Leone', '+232', 'SLL', 'en'],
    'SO' => ['Somalia', '+252', 'SOS', 'so'], 'ZA' => ['South Africa', '+27', 'ZAR', 'en'],
    'SS' => ['South Sudan', '+211', 'SSP', 'en'], 'SD' => ['Sudan', '+249', 'SDG', 'ar'],
    'TZ' => ['Tanzania', '+255', 'TZS', 'sw'], 'TG' => ['Togo', '+228', 'XOF', 'fr'],
    'TN' => ['Tunisia', '+216', 'TND', 'ar'], 'UG' => ['Uganda', '+256', 'UGX', 'en'],
    'ZM' => ['Zambia', '+260', 'ZMW', 'en'], 'ZW' => ['Zimbabwe', '+263', 'USD', 'en'],

    // ---- Europe ----
    'AL' => ['Albania', '+355', 'ALL', 'en'], 'AD' => ['Andorra', '+376', 'EUR', 'es'],
    'AT' => ['Austria', '+43', 'EUR', 'de'], 'BY' => ['Belarus', '+375', 'BYN', 'ru'],
    'BE' => ['Belgium', '+32', 'EUR', 'fr'], 'BA' => ['Bosnia and Herzegovina', '+387', 'BAM', 'en'],
    'BG' => ['Bulgaria', '+359', 'BGN', 'en'], 'HR' => ['Croatia', '+385', 'EUR', 'en'],
    'CY' => ['Cyprus', '+357', 'EUR', 'el'], 'CZ' => ['Czechia', '+420', 'CZK', 'cs'],
    'DK' => ['Denmark', '+45', 'DKK', 'da'], 'EE' => ['Estonia', '+372', 'EUR', 'en'],
    'FI' => ['Finland', '+358', 'EUR', 'fi'], 'FR' => ['France', '+33', 'EUR', 'fr'],
    'DE' => ['Germany', '+49', 'EUR', 'de'], 'GR' => ['Greece', '+30', 'EUR', 'el'],
    'HU' => ['Hungary', '+36', 'HUF', 'hu'], 'IS' => ['Iceland', '+354', 'ISK', 'en'],
    'IE' => ['Ireland', '+353', 'EUR', 'en'], 'IT' => ['Italy', '+39', 'EUR', 'it'],
    'XK' => ['Kosovo', '+383', 'EUR', 'en'], 'LV' => ['Latvia', '+371', 'EUR', 'en'],
    'LI' => ['Liechtenstein', '+423', 'CHF', 'de'], 'LT' => ['Lithuania', '+370', 'EUR', 'en'],
    'LU' => ['Luxembourg', '+352', 'EUR', 'fr'], 'MT' => ['Malta', '+356', 'EUR', 'en'],
    'MD' => ['Moldova', '+373', 'MDL', 'ro'], 'MC' => ['Monaco', '+377', 'EUR', 'fr'],
    'ME' => ['Montenegro', '+382', 'EUR', 'en'], 'NL' => ['Netherlands', '+31', 'EUR', 'nl'],
    'MK' => ['North Macedonia', '+389', 'MKD', 'en'], 'NO' => ['Norway', '+47', 'NOK', 'no'],
    'PL' => ['Poland', '+48', 'PLN', 'pl'], 'PT' => ['Portugal', '+351', 'EUR', 'pt'],
    'RO' => ['Romania', '+40', 'RON', 'ro'], 'RU' => ['Russia', '+7', 'RUB', 'ru'],
    'SM' => ['San Marino', '+378', 'EUR', 'it'], 'RS' => ['Serbia', '+381', 'RSD', 'en'],
    'SK' => ['Slovakia', '+421', 'EUR', 'cs'], 'SI' => ['Slovenia', '+386', 'EUR', 'en'],
    'ES' => ['Spain', '+34', 'EUR', 'es'], 'SE' => ['Sweden', '+46', 'SEK', 'sv'],
    'CH' => ['Switzerland', '+41', 'CHF', 'de'], 'UA' => ['Ukraine', '+380', 'UAH', 'uk'],
    'GB' => ['United Kingdom', '+44', 'GBP', 'en'], 'VA' => ['Vatican City', '+379', 'EUR', 'it'],

    // ---- Asia ----
    'AF' => ['Afghanistan', '+93', 'AFN', 'fa'], 'AM' => ['Armenia', '+374', 'AMD', 'ru'],
    'AZ' => ['Azerbaijan', '+994', 'AZN', 'tr'], 'BH' => ['Bahrain', '+973', 'BHD', 'ar'],
    'BD' => ['Bangladesh', '+880', 'BDT', 'bn'], 'BT' => ['Bhutan', '+975', 'BTN', 'en'],
    'BN' => ['Brunei', '+673', 'BND', 'ms'], 'KH' => ['Cambodia', '+855', 'KHR', 'en'],
    'CN' => ['China', '+86', 'CNY', 'zh'], 'GE' => ['Georgia', '+995', 'GEL', 'ru'],
    'IN' => ['India', '+91', 'INR', 'hi'], 'ID' => ['Indonesia', '+62', 'IDR', 'id'],
    'IR' => ['Iran', '+98', 'IRR', 'fa'], 'IQ' => ['Iraq', '+964', 'IQD', 'ar'],
    'IL' => ['Israel', '+972', 'ILS', 'he'], 'JP' => ['Japan', '+81', 'JPY', 'ja'],
    'JO' => ['Jordan', '+962', 'JOD', 'ar'], 'KZ' => ['Kazakhstan', '+7', 'KZT', 'ru'],
    'KW' => ['Kuwait', '+965', 'KWD', 'ar'], 'KG' => ['Kyrgyzstan', '+996', 'KGS', 'ru'],
    'LA' => ['Laos', '+856', 'LAK', 'en'], 'LB' => ['Lebanon', '+961', 'LBP', 'ar'],
    'MY' => ['Malaysia', '+60', 'MYR', 'ms'], 'MV' => ['Maldives', '+960', 'MVR', 'en'],
    'MN' => ['Mongolia', '+976', 'MNT', 'en'], 'MM' => ['Myanmar', '+95', 'MMK', 'en'],
    'NP' => ['Nepal', '+977', 'NPR', 'hi'], 'KP' => ['North Korea', '+850', 'KPW', 'ko'],
    'OM' => ['Oman', '+968', 'OMR', 'ar'], 'PK' => ['Pakistan', '+92', 'PKR', 'ur'],
    'PS' => ['Palestine', '+970', 'ILS', 'ar'], 'PH' => ['Philippines', '+63', 'PHP', 'en'],
    'QA' => ['Qatar', '+974', 'QAR', 'ar'], 'SA' => ['Saudi Arabia', '+966', 'SAR', 'ar'],
    'SG' => ['Singapore', '+65', 'SGD', 'en'], 'KR' => ['South Korea', '+82', 'KRW', 'ko'],
    'LK' => ['Sri Lanka', '+94', 'LKR', 'ta'], 'SY' => ['Syria', '+963', 'SYP', 'ar'],
    'TW' => ['Taiwan', '+886', 'TWD', 'zh'], 'TJ' => ['Tajikistan', '+992', 'TJS', 'ru'],
    'TH' => ['Thailand', '+66', 'THB', 'th'], 'TL' => ['Timor-Leste', '+670', 'USD', 'pt'],
    'TR' => ['Türkiye', '+90', 'TRY', 'tr'], 'TM' => ['Turkmenistan', '+993', 'TMT', 'ru'],
    'AE' => ['United Arab Emirates', '+971', 'AED', 'ar'], 'UZ' => ['Uzbekistan', '+998', 'UZS', 'ru'],
    'VN' => ['Vietnam', '+84', 'VND', 'vi'], 'YE' => ['Yemen', '+967', 'YER', 'ar'],

    // ---- Americas ----
    'AG' => ['Antigua and Barbuda', '+1268', 'XCD', 'en'], 'AR' => ['Argentina', '+54', 'ARS', 'es'],
    'BS' => ['Bahamas', '+1242', 'BSD', 'en'], 'BB' => ['Barbados', '+1246', 'BBD', 'en'],
    'BZ' => ['Belize', '+501', 'BZD', 'en'], 'BO' => ['Bolivia', '+591', 'BOB', 'es'],
    'BR' => ['Brazil', '+55', 'BRL', 'pt'], 'CA' => ['Canada', '+1', 'CAD', 'en'],
    'CL' => ['Chile', '+56', 'CLP', 'es'], 'CO' => ['Colombia', '+57', 'COP', 'es'],
    'CR' => ['Costa Rica', '+506', 'CRC', 'es'], 'CU' => ['Cuba', '+53', 'CUP', 'es'],
    'DM' => ['Dominica', '+1767', 'XCD', 'en'], 'DO' => ['Dominican Republic', '+1809', 'DOP', 'es'],
    'EC' => ['Ecuador', '+593', 'USD', 'es'], 'SV' => ['El Salvador', '+503', 'USD', 'es'],
    'GD' => ['Grenada', '+1473', 'XCD', 'en'], 'GT' => ['Guatemala', '+502', 'GTQ', 'es'],
    'GY' => ['Guyana', '+592', 'GYD', 'en'], 'HT' => ['Haiti', '+509', 'HTG', 'fr'],
    'HN' => ['Honduras', '+504', 'HNL', 'es'], 'JM' => ['Jamaica', '+1876', 'JMD', 'en'],
    'MX' => ['Mexico', '+52', 'MXN', 'es'], 'NI' => ['Nicaragua', '+505', 'NIO', 'es'],
    'PA' => ['Panama', '+507', 'USD', 'es'], 'PY' => ['Paraguay', '+595', 'PYG', 'es'],
    'PE' => ['Peru', '+51', 'PEN', 'es'], 'KN' => ['Saint Kitts and Nevis', '+1869', 'XCD', 'en'],
    'LC' => ['Saint Lucia', '+1758', 'XCD', 'en'], 'VC' => ['Saint Vincent and the Grenadines', '+1784', 'XCD', 'en'],
    'SR' => ['Suriname', '+597', 'SRD', 'nl'], 'TT' => ['Trinidad and Tobago', '+1868', 'TTD', 'en'],
    'US' => ['United States', '+1', 'USD', 'en'], 'UY' => ['Uruguay', '+598', 'UYU', 'es'],
    'VE' => ['Venezuela', '+58', 'VES', 'es'],

    // ---- Oceania ----
    'AU' => ['Australia', '+61', 'AUD', 'en'], 'FJ' => ['Fiji', '+679', 'FJD', 'en'],
    'KI' => ['Kiribati', '+686', 'AUD', 'en'], 'MH' => ['Marshall Islands', '+692', 'USD', 'en'],
    'FM' => ['Micronesia', '+691', 'USD', 'en'], 'NR' => ['Nauru', '+674', 'AUD', 'en'],
    'NZ' => ['New Zealand', '+64', 'NZD', 'en'], 'PW' => ['Palau', '+680', 'USD', 'en'],
    'PG' => ['Papua New Guinea', '+675', 'PGK', 'en'], 'WS' => ['Samoa', '+685', 'WST', 'en'],
    'SB' => ['Solomon Islands', '+677', 'SBD', 'en'], 'TO' => ['Tonga', '+676', 'TOP', 'en'],
    'TV' => ['Tuvalu', '+688', 'AUD', 'en'], 'VU' => ['Vanuatu', '+678', 'VUV', 'en'],
];

// currency => [symbol, rate (per 1 XAF), decimals]
$currencies = [
    'XAF' => ['FCFA', 1, 0], 'XOF' => ['CFA', 1, 0], 'USD' => ['$', 0.00165, 2], 'EUR' => ['€', 0.0015, 2],
    'GBP' => ['£', 0.0013, 2], 'CAD' => ['C$', 0.0023, 2], 'CNY' => ['¥', 0.012, 2], 'NGN' => ['₦', 2.6, 0],
    'GHS' => ['GH₵', 0.025, 2], 'KES' => ['KSh', 0.213, 0], 'ZAR' => ['R', 0.03, 2], 'DZD' => ['دج', 0.221, 0],
    'AOA' => ['Kz', 1.5, 0], 'BWP' => ['P', 0.022, 2], 'BIF' => ['FBu', 4.74, 0], 'CVE' => ['$', 0.167, 0],
    'KMF' => ['CF', 0.74, 0], 'CDF' => ['FC', 4.45, 0], 'DJF' => ['Fdj', 0.294, 0], 'EGP' => ['E£', 0.079, 2],
    'ERN' => ['Nfk', 0.025, 2], 'SZL' => ['E', 0.03, 2], 'ETB' => ['Br', 0.094, 2], 'GMD' => ['D', 0.112, 2],
    'GNF' => ['FG', 14.2, 0], 'LRD' => ['L$', 0.31, 0], 'LYD' => ['ل.د', 0.0079, 2], 'MGA' => ['Ar', 7.4, 0],
    'MWK' => ['MK', 2.85, 0], 'MRU' => ['UM', 0.064, 2], 'MUR' => ['₨', 0.076, 2], 'MAD' => ['DH', 0.0163, 2],
    'MZN' => ['MT', 0.105, 2], 'NAD' => ['N$', 0.03, 2], 'RWF' => ['FRw', 2.14, 0], 'STN' => ['Db', 0.036, 2],
    'SCR' => ['₨', 0.022, 2], 'SLL' => ['Le', 36.3, 0], 'SOS' => ['Sh', 0.94, 0], 'SSP' => ['£', 2.14, 0],
    'SDG' => ['ج.س', 0.99, 0], 'TZS' => ['TSh', 4.29, 0], 'TND' => ['د.ت', 0.0051, 2], 'UGX' => ['USh', 6.1, 0],
    'ZMW' => ['ZK', 0.043, 2], 'ALL' => ['L', 0.153, 0], 'BYN' => ['Br', 0.0055, 2], 'BAM' => ['KM', 0.003, 2],
    'BGN' => ['лв', 0.003, 2], 'CZK' => ['Kč', 0.038, 2], 'DKK' => ['kr', 0.0114, 2], 'HUF' => ['Ft', 0.594, 0],
    'ISK' => ['kr', 0.228, 0], 'MDL' => ['L', 0.029, 2], 'MKD' => ['ден', 0.094, 2], 'NOK' => ['kr', 0.0177, 2],
    'PLN' => ['zł', 0.0065, 2], 'RON' => ['lei', 0.0076, 2], 'RUB' => ['₽', 0.152, 2], 'RSD' => ['дин', 0.178, 0],
    'SEK' => ['kr', 0.0173, 2], 'CHF' => ['Fr', 0.00145, 2], 'UAH' => ['₴', 0.068, 2], 'AFN' => ['؋', 0.117, 0],
    'AMD' => ['֏', 0.64, 0], 'AZN' => ['₼', 0.0028, 2], 'BHD' => ['.د.ب', 0.00062, 3], 'BDT' => ['৳', 0.196, 0],
    'BTN' => ['Nu', 0.139, 0], 'BND' => ['$', 0.0022, 2], 'KHR' => ['៛', 6.76, 0], 'GEL' => ['₾', 0.0045, 2],
    'INR' => ['₹', 0.139, 2], 'IDR' => ['Rp', 26, 0], 'IRR' => ['﷼', 69, 0], 'IQD' => ['ع.د', 2.16, 0],
    'ILS' => ['₪', 0.0061, 2], 'JPY' => ['¥', 0.247, 0], 'JOD' => ['د.ا', 0.0012, 2], 'KZT' => ['₸', 0.79, 0],
    'KWD' => ['د.ك', 0.0005, 3], 'KGS' => ['с', 0.142, 2], 'LAK' => ['₭', 35.5, 0], 'LBP' => ['ل.ل', 147, 0],
    'MYR' => ['RM', 0.0073, 2], 'MVR' => ['Rf', 0.025, 2], 'MNT' => ['₮', 5.6, 0], 'MMK' => ['K', 3.46, 0],
    'NPR' => ['₨', 0.221, 0], 'KPW' => ['₩', 1.48, 0], 'OMR' => ['ر.ع.', 0.00063, 3], 'PKR' => ['₨', 0.459, 0],
    'PHP' => ['₱', 0.096, 2], 'QAR' => ['ر.ق', 0.006, 2], 'SAR' => ['﷼', 0.0062, 2], 'SGD' => ['S$', 0.0022, 2],
    'KRW' => ['₩', 2.28, 0], 'LKR' => ['₨', 0.487, 0], 'SYP' => ['£', 21.4, 0], 'TWD' => ['NT$', 0.053, 0],
    'TJS' => ['ЅМ', 0.018, 2], 'THB' => ['฿', 0.056, 2], 'TRY' => ['₺', 0.056, 2], 'TMT' => ['m', 0.0058, 2],
    'AED' => ['د.إ', 0.006, 2], 'UZS' => ['лв', 21, 0], 'VND' => ['₫', 41, 0], 'YER' => ['﷼', 0.41, 0],
    'XCD' => ['$', 0.0045, 2], 'ARS' => ['$', 1.65, 0], 'BSD' => ['$', 0.00165, 2], 'BBD' => ['$', 0.0033, 2],
    'BZD' => ['$', 0.0033, 2], 'BOB' => ['Bs', 0.0114, 2], 'BRL' => ['R$', 0.0094, 2], 'CLP' => ['$', 1.6, 0],
    'COP' => ['$', 7.1, 0], 'CRC' => ['₡', 0.84, 0], 'CUP' => ['$', 0.04, 2], 'DOP' => ['RD$', 0.099, 2],
    'GTQ' => ['Q', 0.0127, 2], 'GYD' => ['$', 0.345, 0], 'HTG' => ['G', 0.218, 2], 'HNL' => ['L', 0.041, 2],
    'JMD' => ['J$', 0.259, 2], 'MXN' => ['$', 0.033, 2], 'NIO' => ['C$', 0.061, 2], 'PYG' => ['₲', 12.9, 0],
    'PEN' => ['S/', 0.0063, 2], 'SRD' => ['$', 0.058, 2], 'TTD' => ['TT$', 0.0112, 2], 'UYU' => ['$U', 0.069, 2],
    'VES' => ['Bs', 0.066, 2], 'AUD' => ['A$', 0.0025, 2], 'FJD' => ['FJ$', 0.0037, 2], 'NZD' => ['NZ$', 0.0027, 2],
    'PGK' => ['K', 0.0064, 2], 'WST' => ['T', 0.0045, 2], 'SBD' => ['SI$', 0.0139, 2], 'TOP' => ['T$', 0.0039, 2],
    'VUV' => ['VT', 0.196, 0],
];

$countries = [];
$country_currency = [];
$country_locale = [];
foreach ($c as $iso => $d) {
    $countries[$iso] = ['name' => $d[0], 'dial' => $d[1]];
    $country_currency[$iso] = $d[2];
    $country_locale[$iso] = $d[3];
}

return compact('locales', 'countries', 'currencies', 'country_currency', 'country_locale');
