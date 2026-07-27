<?php

namespace App\Services\Admin;

use App\Models\Banner;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Collection;

class BannerAdminService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data, User $admin): Banner
    {
        $banner = Banner::create($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.banner.created', "Created banner {$banner->title}", $banner, [], $admin->id);

        return $banner;
    }

    public function update(Banner $banner, array $data, User $admin): Banner
    {
        $banner->update($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.banner.updated', "Updated banner {$banner->title}", $banner, [], $admin->id);

        return $banner->fresh();
    }

    /** Archive-not-delete: soft-deletes so impression/click history (if ever added) stays attributable. */
    public function archive(Banner $banner, User $admin): void
    {
        $banner->update(['is_active' => false, 'updated_by' => $admin->id]);
        $this->audit->log('admin.banner.archived', "Archived banner {$banner->title}", $banner, [], $admin->id);
        $banner->delete();
    }

    public function restore(Banner $banner, User $admin): Banner
    {
        $banner->restore();
        $banner->update(['updated_by' => $admin->id]);
        $this->audit->log('admin.banner.restored', "Restored banner {$banner->title}", $banner, [], $admin->id);

        return $banner->fresh();
    }

    /**
     * Real, enforced targeting — the single evaluation point used everywhere a
     * banner is rendered (home hero, sitewide announcement strip). At most one
     * banner is returned per call so conflicting full-screen/strip banners are
     * never shown simultaneously.
     */
    public function firstVisible(Collection $banners, ?User $user): ?Banner
    {
        return $banners->first(fn (Banner $b) => $b->isVisibleTo($user));
    }
}
