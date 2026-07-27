<?php

namespace App\Services\Admin;

use App\Models\ProcessStep;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\Audit\AuditLogger;

/**
 * Manages the structured, repeatable content blocks on the "Page content"
 * admin screen — testimonials and the How It Works process steps — both of
 * which replaced hardcoded PHP arrays in public Blade views.
 */
class ContentBlockAdminService
{
    public function __construct(private AuditLogger $audit) {}

    public function createTestimonial(array $data, User $admin): Testimonial
    {
        $testimonial = Testimonial::create($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.testimonial.created', "Added testimonial from {$testimonial->name}", $testimonial, [], $admin->id);

        return $testimonial;
    }

    public function updateTestimonial(Testimonial $testimonial, array $data, User $admin): Testimonial
    {
        $testimonial->update($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.testimonial.updated', "Updated testimonial from {$testimonial->name}", $testimonial, [], $admin->id);

        return $testimonial->fresh();
    }

    public function deleteTestimonial(Testimonial $testimonial, User $admin): void
    {
        $this->audit->log('admin.testimonial.deleted', "Removed testimonial from {$testimonial->name}", $testimonial, [], $admin->id);
        $testimonial->delete();
    }

    public function createStep(array $data, User $admin): ProcessStep
    {
        $step = ProcessStep::create($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.process_step.created', "Added {$step->group} step: {$step->title}", $step, [], $admin->id);

        return $step;
    }

    public function updateStep(ProcessStep $step, array $data, User $admin): ProcessStep
    {
        $step->update($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.process_step.updated', "Updated {$step->group} step: {$step->title}", $step, [], $admin->id);

        return $step->fresh();
    }

    public function deleteStep(ProcessStep $step, User $admin): void
    {
        $this->audit->log('admin.process_step.deleted', "Removed {$step->group} step: {$step->title}", $step, [], $admin->id);
        $step->delete();
    }
}
