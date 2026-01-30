<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * NOTE: This migration is deprecated as of v1.1.0
     * 
     * Checkpoint metadata is now stored in a JSON file 
     * (storage/tyro-checkpoints/checkpoints.json) instead of the database
     * to prevent loss of checkpoint history when restoring.
     * 
     * This migration is kept for backward compatibility but does nothing.
     */
    public function up(): void
    {
        // Skip - checkpoints are now stored in JSON file
        // If you have an old installation, the table will remain but won't be used
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tyro_checkpoints');
    }
};
