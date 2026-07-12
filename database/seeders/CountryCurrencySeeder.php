<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CountryCurrencySeeder extends Seeder
{
    public function run(): void
    {
        // Currencies (from config/world.php via platform config).
        foreach (config('platform.currencies', []) as $code => $c) {
            Currency::updateOrCreate(['code' => $code], [
                'name' => $code,
                'symbol' => $c['symbol'],
                'decimals' => $c['decimals'],
            ]);
        }

        // Countries — every country worldwide.
        $cc = config('platform.country_currency', []);
        $countries = config('platform.countries', []);
        uasort($countries, fn ($a, $b) => strcmp($a['name'], $b['name'])); // alphabetical

        $sort = 0;
        foreach ($countries as $iso => $d) {
            Country::updateOrCreate(['iso2' => $iso], [
                'name' => $d['name'],
                'dial_code' => $d['dial'],
                'currency_code' => $cc[$iso] ?? 'USD',
                'flag_emoji' => self::flagEmoji($iso),
                'is_active' => true,
                'sort' => $sort++,
            ]);
        }
    }

    /** Build the regional-indicator flag emoji from an ISO2 code. */
    private static function flagEmoji(string $iso): string
    {
        return collect(str_split(strtoupper($iso)))
            ->map(fn ($ch) => mb_chr(0x1F1E6 + (ord($ch) - ord('A'))))
            ->implode('');
    }
}
