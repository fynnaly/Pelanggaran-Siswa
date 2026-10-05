<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sinkronisasi schema ↔ model Phase 2: discipline_cases, achievement_records,
 * violation_categories, achievement_categories.
 *
 * Idempotent: MySQL DDL tidak transactional, migrasi yang gagal di tengah
 * meninggalkan sebagian kolom terpasang. Penambahan kolom dikunci hasColumn.
 *
 * Tidak menghapus kolom lama (report_by, validated_by, valid_from, valid_until)
 * supaya data existing tidak hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. discipline_cases: alur 6 tahap + snapshot akademik ──────────
        // Idempotent: lewati kolom yang sudah ada.
        // Semua kolom nullable — raw ALTER, karena Blueprint nullable gagal
        // di MySQL strict mode pada row yang sudah ada ('0000-00-00').
        $add = function (string $table, array $columns): void {
            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn($table, $column)) {
                    DB::statement("ALTER TABLE `{$table}` ADD `{$column}` {$definition}");
                }
            }
        };

        $add('discipline_cases', [
            'academic_year_id'    => 'BIGINT UNSIGNED NULL',
            'severity_override'   => 'VARCHAR(20) NULL DEFAULT NULL',
            'points_override'     => 'INT UNSIGNED NULL DEFAULT NULL',
            'incident_date'       => 'DATE NULL',
            'incident_time'       => 'TIME NULL',
            'clarification_notes' => 'TEXT NULL',
            'classification_notes' => 'TEXT NULL',
            'validation_notes'    => 'TEXT NULL',
            'handling_notes'      => 'TEXT NULL',
            'monitoring_notes'    => 'TEXT NULL',
            'assigned_to'         => 'BIGINT UNSIGNED NULL',
            'validator_id'        => 'BIGINT UNSIGNED NULL',
            'closed_by'           => 'BIGINT UNSIGNED NULL',
            'closed_at'           => 'TIMESTAMP NULL',
            'reporter_id'         => 'BIGINT UNSIGNED NULL',
        ]);

        // Status lama ENUM → VARCHAR(20) supaya alur 6 tahap (clarifying … closed) muat.
        // Guard: hanya ubah bila kolom masih ENUM (fresh DB lama).
        try {
            $col = collect(DB::select("SHOW COLUMNS FROM `discipline_cases` WHERE Field = 'status'"))->first();
            if ($col && str_contains((string) $col->Type, 'enum')) {
                DB::statement("ALTER TABLE `discipline_cases` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'found'");
            }
        } catch (Throwable) {
            // Lewati bila SHOW COLUMNS gagal (mis. SQLite test) — tidak kritikal.
        }

        // ── 2. achievement_records: snapshot + detail event ───────────────
        // enrollment_id & class_id sudah ada dari migrasi snapshot sebelumnya.
        $add('achievement_records', [
            'academic_year_id'  => 'BIGINT UNSIGNED NULL',
            'event_name'        => 'VARCHAR(150) NULL',
            'event_date'        => 'DATE NULL',
            'event_period'      => 'VARCHAR(20) NULL',
            'achievement_level' => 'VARCHAR(50) NULL',
            'points_awarded'    => 'INT UNSIGNED NULL DEFAULT NULL',
            'evidence_path'     => 'VARCHAR(255) NULL',
            'rejection_reason'  => 'TEXT NULL',
        ]);

        // ── 3. violation_categories: field aturan ───────────────────────
        $add('violation_categories', [
            'is_zero_tolerance' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'effective_date'    => 'DATE NULL',
            'expiry_date'       => 'DATE NULL',
        ]);

        // ── 4. achievement_categories: deskripsi + group ────────────────
        $add('achievement_categories', [
            'description'         => 'TEXT NULL',
            'group_type'          => "VARCHAR(50) NOT NULL DEFAULT 'akademik'",
            'max_points_per_event' => 'INT UNSIGNED NULL DEFAULT NULL',
            'effective_date'      => 'DATE NULL',
            'expiry_date'         => 'DATE NULL',
        ]);
    }

    public function down(): void
    {
        Schema::table('discipline_cases', function (Blueprint $table) {
            $table->dropColumn([
                'academic_year_id', 'severity_override', 'points_override',
                'incident_date', 'incident_time', 'clarification_notes',
                'classification_notes', 'validation_notes', 'handling_notes',
                'monitoring_notes', 'assigned_to', 'validator_id', 'closed_by', 'closed_at',
                'reporter_id',
            ]);
        });

        Schema::table('achievement_records', function (Blueprint $table) {
            $table->dropColumn([
                'academic_year_id',
                'event_name', 'event_date', 'event_period',
                'achievement_level', 'points_awarded', 'evidence_path', 'rejection_reason',
            ]);
        });

        Schema::table('violation_categories', function (Blueprint $table) {
            $table->dropColumn(['is_zero_tolerance', 'effective_date', 'expiry_date']);
        });

        Schema::table('achievement_categories', function (Blueprint $table) {
            $table->dropColumn(['description', 'group_type', 'max_points_per_event', 'effective_date', 'expiry_date']);
        });
    }
};
