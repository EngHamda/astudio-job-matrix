<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('core_jobs', function (Blueprint $table) {
            $table->id();

            // Text fields with strategic indexing
            $table->string('title')
                ->index(); // Simple index for equality and prefix searches

            $table->text('description')
                ->nullable(); // Full-text index will be added separately

            $table->string('company_name')
                ->index(); // Simple index for equality comparisons

            // Numeric salary fields with composite and individual indexes
            $table->decimal('salary_min', 10, 2)
                ->nullable()
                ->index(); // Individual index for range queries

            $table->decimal('salary_max', 10, 2)
                ->nullable()
                ->index(); // Individual index for range queries

            // Boolean field (low selectivity)
            $table->boolean('is_remote')
                ->default(false);

            // Enum fields with indexes
            $table->enum('job_type', ['full-time', 'part-time', 'contract', 'freelance'])
                ->default('full-time')
                ->index();

            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft')
                ->index();

            // Date fields with indexes
            $table->timestamp('published_at')
                ->nullable()
                ->index();

            // Standard timestamps
            $table->timestamps();

            // Composite indexes for complex filtering scenarios
            // 1. Salary range with job type
            $table->index(['salary_min', 'salary_max', 'job_type']);

            // 2. Status with job type for filtering
            $table->index(['status', 'job_type']);

            // 3. Date-based filtering with status
            $table->index(['published_at', 'status']);
        });

        // Add full-text index for description (MySQL specific)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE core_jobs ADD FULLTEXT INDEX jobs_description_fulltext (description)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_jobs', function (Blueprint $table) {
            // Drop specific indexes
            $table->dropIndex(['title']);
            $table->dropIndex(['company_name']);
            $table->dropIndex(['salary_min']);
            $table->dropIndex(['salary_max']);
            $table->dropIndex(['job_type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['published_at']);

            // Drop composite indexes
            $table->dropIndex(['salary_min', 'salary_max', 'job_type']);
            $table->dropIndex(['status', 'job_type']);
            $table->dropIndex(['published_at', 'status']);

            // Drop full-text index if it exists
            if (DB::getDriverName() === 'mysql') {
                $table->dropFulltext('jobs_description_fulltext');
            }
        });

        Schema::dropIfExists('core_jobs');
    }
};
