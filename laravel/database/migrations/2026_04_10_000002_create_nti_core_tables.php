<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();
            $table->string('guard_name', 64)->default('web');
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('legal_name', 180)->nullable();
            $table->string('registration_number', 32)->nullable()->unique();
            $table->string('tax_number', 32)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('sector', 120)->nullable();
            $table->text('description')->nullable();
            $table->enum('organization_type', ['company', 'partner', 'university', 'public_institution', 'ngo', 'startup'])
                ->default('company');
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_type');
            $table->index('status');
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('membership_role', ['owner', 'admin', 'editor', 'product_owner', 'contact'])
                ->default('contact');
            $table->json('permissions')->nullable();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('study_program', 160)->nullable();
            $table->string('faculty', 160)->nullable();
            $table->unsignedTinyInteger('year_of_study')->nullable();
            $table->unsignedSmallInteger('expected_graduation_year')->nullable();
            $table->text('bio')->nullable();
            $table->json('skills')->nullable();
            $table->string('portfolio_url', 255)->nullable();
            $table->string('linkedin_url', 255)->nullable();
            $table->string('github_url', 255)->nullable();
            $table->decimal('academic_average', 4, 2)->nullable();
            $table->boolean('has_failed_courses')->default(false);
            $table->text('eligibility_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->foreignId('lead_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('vision_summary')->nullable();
            $table->text('competencies_summary')->nullable();
            $table->json('preferred_stack')->nullable();
            $table->unsignedTinyInteger('max_capacity')->nullable();
            $table->enum('status', ['draft', 'active', 'submitted', 'in_program', 'archived'])->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('membership_role', ['leader', 'member'])->default('member');
            $table->json('competencies')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 160);
            $table->enum('program_type', ['grant', 'live_practice']);
            $table->text('description')->nullable();
            $table->json('configuration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('program_type');
            $table->index('is_active');
        });

        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('technical_specification')->nullable();
            $table->enum('state', ['draft', 'published', 'matching', 'evaluation', 'assigned', 'in_delivery', 'closed', 'archived'])
                ->default('draft');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->string('evaluation_round_label', 64)->nullable();
            $table->unsignedTinyInteger('min_team_size')->nullable();
            $table->unsignedTinyInteger('max_team_size')->nullable();
            $table->json('criteria_schema')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('state');
            $table->index(['program_id', 'state']);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('primary_contact_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'draft',
                'submitted',
                'formally_checked',
                'under_review',
                'revision_requested',
                'approved',
                'rejected',
                'waitlisted',
                'onboarding',
                'active_project',
                'paused',
                'completed',
                'archived',
            ])->default('draft');
            $table->enum('decision', ['pending', 'approved', 'rejected', 'waitlisted', 'revision_requested'])
                ->default('pending');
            $table->decimal('score_total', 8, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('decision');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['onboarding', 'active', 'paused', 'completed', 'archived'])->default('onboarding');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('final_outcome')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('document_type', 64);
            $table->enum('visibility', ['public', 'internal', 'confidential'])->default('internal');
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'archived'])->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->string('file_name', 255);
            $table->string('disk', 64)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 128)->nullable();
            $table->string('extension', 16)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum', 128)->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('document_type');
            $table->index('visibility');
            $table->index('status');
        });

        Schema::create('evaluation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('call_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 180);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('evaluation_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_template_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('label', 180);
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['evaluation_template_id', 'key']);
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('evaluator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('recommendation', ['approve', 'reject', 'waitlist', 'request_revision', 'neutral'])
                ->default('neutral');
            $table->decimal('total_score', 8, 2)->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('recommendation');
        });

        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_criterion_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_id', 'evaluation_criterion_id']);
        });

        Schema::create('mentorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['planned', 'active', 'paused', 'completed', 'cancelled'])->default('planned');
            $table->string('meeting_frequency', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'blocked', 'completed', 'approved', 'archived'])
                ->default('planned');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('due_at');
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 120);
            $table->string('auditable_type', 120);
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->enum('result', ['success', 'failure', 'warning'])->default('success');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('event_type');
            $table->index('result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('mentorships');
        Schema::dropIfExists('evaluation_scores');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('evaluation_criteria');
        Schema::dropIfExists('evaluation_templates');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('calls');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('organization_user');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
