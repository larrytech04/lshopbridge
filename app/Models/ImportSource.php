<?php

namespace App\Models;

use App\Enums\ImportSourceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One row per "connector slot" from the Product Import Center — native/manual,
 * file-based, store-platform, marketplace, dropship, China-sourcing, digital-
 * service, or generic-supplier. Most rows never get real credentials; they
 * exist so admins can see every source the platform is *capable* of
 * connecting, honestly labeled "Not connected" until real API access exists.
 */
class ImportSource extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'status' => ImportSourceStatus::class,
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'last_import_at' => 'datetime',
            'last_sync_at' => 'datetime',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(ProductImport::class);
    }

    public function categoryMappings(): HasMany
    {
        return $this->hasMany(ShopCategoryMapping::class);
    }

    /** True only for sources that need no external credentials to function today. */
    public function isUsableWithoutCredentials(): bool
    {
        return in_array($this->group, ['native', 'file'], true);
    }

    public static function groupLabels(): array
    {
        return [
            'native' => 'Native & Manual Sources',
            'file' => 'File Imports',
            'store_platform' => 'Store Platform Connectors',
            'marketplace' => 'Marketplace Connectors',
            'dropship' => 'Dropshipping & Print-on-Demand',
            'china_sourcing' => 'China Sourcing Connectors',
            'digital_service' => 'Digital-Service Providers',
            'generic_supplier' => 'Generic Supplier Connections',
        ];
    }

    /**
     * Every connector slot named in the Commerce Operations spec, grouped
     * A–H. Only native/csv/json have a real connector_class today — every
     * other row is honestly seeded as not_connected with no credentials.
     *
     * @return array<int, array{code:string, name:string, group:string, connector_class:?string}>
     */
    public static function defaultCatalog(): array
    {
        $none = fn (string $code, string $name, string $group) => ['code' => $code, 'name' => $name, 'group' => $group, 'connector_class' => null];
        $csv = \App\Services\Import\Connectors\CsvConnector::class;
        $json = \App\Services\Import\Connectors\JsonConnector::class;
        $native = \App\Services\Import\Connectors\NativeConnector::class;

        return [
            ['code' => 'native', 'name' => 'Native / Manual', 'group' => 'native', 'connector_class' => $native],

            ['code' => 'csv', 'name' => 'CSV', 'group' => 'file', 'connector_class' => $csv],
            ['code' => 'xlsx', 'name' => 'XLSX', 'group' => 'file', 'connector_class' => null],
            ['code' => 'json', 'name' => 'JSON', 'group' => 'file', 'connector_class' => $json],
            $none('xml', 'XML', 'file'),
            $none('zip', 'ZIP package (data + images)', 'file'),
            $none('google_sheets', 'Google Sheets', 'file'),
            $none('remote_url', 'Remote file URL', 'file'),
            $none('scheduled_feed', 'Scheduled feed URL', 'file'),
            $none('sftp_feed', 'SFTP feed', 'file'),
            $none('ftp_feed', 'FTP feed', 'file'),
            $none('cloud_storage', 'Secure cloud-storage file', 'file'),

            $none('woocommerce', 'WooCommerce', 'store_platform'),
            $none('shopify', 'Shopify', 'store_platform'),
            $none('magento', 'Adobe Commerce / Magento', 'store_platform'),
            $none('bigcommerce', 'BigCommerce', 'store_platform'),
            $none('prestashop', 'PrestaShop', 'store_platform'),
            $none('opencart', 'OpenCart', 'store_platform'),
            $none('wix', 'Wix Stores', 'store_platform'),
            $none('ecwid', 'Ecwid', 'store_platform'),
            $none('square_online', 'Square Online', 'store_platform'),
            $none('squarespace', 'Squarespace Commerce', 'store_platform'),
            $none('custom_laravel', 'Custom Laravel store', 'store_platform'),
            $none('custom_rest', 'Custom REST API', 'store_platform'),
            $none('custom_graphql', 'Custom GraphQL API', 'store_platform'),

            $none('amazon', 'Amazon Seller', 'marketplace'),
            $none('ebay', 'eBay Seller', 'marketplace'),
            $none('etsy', 'Etsy Shop', 'marketplace'),
            $none('walmart', 'Walmart Marketplace', 'marketplace'),
            $none('tiktok_shop', 'TikTok Shop', 'marketplace'),
            $none('meta_commerce', 'Meta Commerce catalogue', 'marketplace'),

            $none('cjdropshipping', 'CJdropshipping', 'dropship'),
            $none('printful', 'Printful', 'dropship'),
            $none('printify', 'Printify', 'dropship'),

            $none('alibaba', 'Alibaba.com', 'china_sourcing'),
            $none('aliexpress', 'AliExpress', 'china_sourcing'),
            $none('1688', '1688', 'china_sourcing'),
            $none('taobao', 'Taobao', 'china_sourcing'),
            $none('tmall', 'Tmall', 'china_sourcing'),
            $none('jd', 'JD.com', 'china_sourcing'),
            $none('made_in_china', 'Made-in-China', 'china_sourcing'),
            $none('global_sources', 'Global Sources', 'china_sourcing'),
            $none('china_agents', 'LshopBridge verified sourcing agents', 'china_sourcing'),

            ['code' => 'esim_providers', 'name' => 'eSIM providers (Airalo)', 'group' => 'digital_service', 'connector_class' => \App\Services\Esim\Connectors\AiraloConnector::class],
            $none('airtime_providers', 'Airtime / mobile data providers', 'digital_service'),
            $none('bill_payment_aggregators', 'Bill-payment aggregators', 'digital_service'),
            $none('giftcard_providers', 'Gift-card providers', 'digital_service'),
            $none('software_licence_providers', 'Software-licence providers', 'digital_service'),
            $none('vpn_providers', 'VPN subscription providers', 'digital_service'),
            $none('digital_content_providers', 'Digital-content providers', 'digital_service'),
            $none('custom_service_api', 'Custom service APIs', 'digital_service'),

            $none('supplier_rest', 'Supplier REST API', 'generic_supplier'),
            $none('supplier_graphql', 'Supplier GraphQL API', 'generic_supplier'),
            $none('supplier_xml', 'Supplier XML feed', 'generic_supplier'),
            $none('supplier_json', 'Supplier JSON feed', 'generic_supplier'),
            $none('supplier_csv', 'Supplier CSV feed', 'generic_supplier'),
            $none('supplier_sftp', 'Supplier SFTP', 'generic_supplier'),
            $none('supplier_webhook', 'Supplier webhook', 'generic_supplier'),
            $none('manual_supplier_catalogue', 'Manual supplier catalogue', 'generic_supplier'),
            $none('email_attachment_import', 'Email attachment import (admin-reviewed)', 'generic_supplier'),
        ];
    }

    public static function ensureSeeded(): void
    {
        foreach (self::defaultCatalog() as $row) {
            self::firstOrCreate(['code' => $row['code']], [
                'name' => $row['name'],
                'group' => $row['group'],
                'connector_class' => $row['connector_class'],
                'status' => 'not_connected',
                'auto_sync' => 'manual',
                'is_active' => false,
            ]);
        }
    }
}
