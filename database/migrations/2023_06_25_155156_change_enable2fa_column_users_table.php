<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('enable2fa', 'temp_is_verifiedkey');
            // Add a new boolean column with a default value of false
            $table->boolean('is_verifiedkey')->default(false);
            // Remove the temporary column
            $table->dropColumn('temp_is_verifiedkey');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
