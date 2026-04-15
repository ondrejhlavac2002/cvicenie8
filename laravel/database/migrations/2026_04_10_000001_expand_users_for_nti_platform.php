<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_type', ['student', 'mentor', 'company', 'staff'])
                ->default('student')
                ->after('role');
            $table->enum('account_status', ['pending_verification', 'active', 'suspended', 'archived'])
                ->default('pending_verification')
                ->after('account_type');
            $table->string('phone', 32)->nullable()->after('email');
            $table->timestamp('gdpr_consented_at')->nullable()->after('premium_until');
            $table->timestamp('marketing_consented_at')->nullable()->after('gdpr_consented_at');
            $table->timestamp('onboarded_at')->nullable()->after('marketing_consented_at');
            $table->timestamp('last_login_at')->nullable()->after('onboarded_at');

            $table->index('account_type');
            $table->index('account_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type']);
            $table->dropIndex(['account_status']);
            $table->dropColumn([
                'account_type',
                'account_status',
                'phone',
                'gdpr_consented_at',
                'marketing_consented_at',
                'onboarded_at',
                'last_login_at',
            ]);
        });
    }
};
