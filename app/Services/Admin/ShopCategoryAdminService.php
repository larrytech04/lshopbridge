<?php

namespace App\Services\Admin;

use App\Models\ShopCategory;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class ShopCategoryAdminService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @return array{ok:bool, errors:array<string,string>}
     */
    public function validateCategory(array $data, ?ShopCategory $existing = null): array
    {
        $errors = [];

        if (! empty($data['parent_id'])) {
            if ($existing && (int) $data['parent_id'] === $existing->id) {
                $errors['parent_id'] = 'A category cannot be its own parent.';
            } elseif ($existing && $this->isDescendant($existing, (int) $data['parent_id'])) {
                $errors['parent_id'] = 'Cannot move a category under one of its own subcategories.';
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    public function createCategory(array $data, User $admin): ShopCategory
    {
        $category = ShopCategory::create($data);
        $this->audit->log('shop.category.created', "Created category {$category->name}", $category, $data);

        return $category;
    }

    public function updateCategory(ShopCategory $category, array $data, User $admin): ShopCategory
    {
        $before = $category->only(['name', 'parent_id', 'is_active']);
        $category->update($data);
        $this->audit->log('shop.category.updated', "Updated category {$category->name}", $category, ['before' => $before, 'after' => $data]);

        return $category->fresh();
    }

    public function canArchive(ShopCategory $category): bool
    {
        return ! $category->products()->exists() && ! $category->children()->exists();
    }

    /** @return array{ok:bool, message:?string} */
    public function archive(ShopCategory $category, User $admin): array
    {
        if (! $this->canArchive($category)) {
            return ['ok' => false, 'message' => 'Reassign this category\'s products and subcategories before archiving it.'];
        }

        $category->update(['is_active' => false]);
        $this->audit->log('shop.category.archived', "Archived category {$category->name}", $category, [], $admin->id);

        return ['ok' => true, 'message' => null];
    }

    public function setActive(ShopCategory $category, bool $active, User $admin): ShopCategory
    {
        $category->update(['is_active' => $active]);
        $this->audit->log($active ? 'shop.category.activated' : 'shop.category.deactivated', ($active ? 'Activated' : 'Deactivated')." category {$category->name}", $category, [], $admin->id);

        return $category->fresh();
    }

    /** Persist a new sibling order for a set of category IDs (drag-and-drop). */
    public function reorder(array $orderedIds, User $admin): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach (array_values($orderedIds) as $index => $id) {
                ShopCategory::whereKey($id)->update(['sort' => $index]);
            }
        });

        $this->audit->log('shop.category.reordered', 'Reordered '.count($orderedIds).' category(ies)', null, ['ids' => $orderedIds], $admin->id);
    }

    private function isDescendant(ShopCategory $category, int $candidateParentId): bool
    {
        $descendants = $this->flattenDescendantIds($category);

        return in_array($candidateParentId, $descendants, true);
    }

    private function flattenDescendantIds(ShopCategory $category): array
    {
        $ids = [];

        foreach ($category->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->flattenDescendantIds($child));
        }

        return $ids;
    }
}
