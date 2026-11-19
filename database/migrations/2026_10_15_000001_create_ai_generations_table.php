<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per AI content request: what was asked, which provider/model answered, what it cost and what was applied.
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('batch_id')->nullable()->index();
            $table->string('provider', 20);
            $table->string('model', 100);
            $table->string('prompt_version', 20);
            $table->string('status', 20)->default('queued')->index(); // queued, running, succeeded, failed
            $table->json('fields')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_usd', 10, 5)->nullable();
            $table->decimal('similarity', 4, 3)->nullable(); // highest overlap with a sibling page, 0–1
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // New permissions are granted here (not by re-running the seeder) so custom role edits are kept.
        if (Schema::hasTable('permissions')) {
            $now = now();
            foreach (['ai.configure', 'ai.generate'] as $name) {
                DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now]);
            }
            $grants = ['Admin' => ['ai.configure', 'ai.generate'], 'SEO Manager' => ['ai.generate'], 'Content Manager' => ['ai.generate']];
            foreach ($grants as $role => $perms) {
                $roleId = DB::table('roles')->where('name', $role)->where('guard_name', 'web')->value('id');
                if (! $roleId) {
                    continue;
                }
                foreach (DB::table('permissions')->whereIn('name', $perms)->where('guard_name', 'web')->pluck('id') as $permId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permId, 'role_id' => $roleId]);
                }
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', ['ai.configure', 'ai.generate'])->delete();
        }
    }
};
