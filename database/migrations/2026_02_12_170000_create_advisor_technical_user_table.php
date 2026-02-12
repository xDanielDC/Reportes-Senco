<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advisor_technical_user', function (Blueprint $table) {
            $table->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('technical_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['advisor_id', 'technical_user_id']);
        });

        $now = now();
        $rows = DB::table('users')
            ->whereNotNull('advisor_id')
            ->select('advisor_id', DB::raw('id as technical_user_id'))
            ->get()
            ->map(function ($row) use ($now) {
                return [
                    'advisor_id' => (int) $row->advisor_id,
                    'technical_user_id' => (int) $row->technical_user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->values()
            ->all();

        if (!empty($rows)) {
            DB::table('advisor_technical_user')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('advisor_technical_user');
    }
};
