<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $table="users";
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->string("user_type")->nullable()->index();
            $table->unsignedBigInteger("user_type_id")->nullable()->index()->comment("Foreign key to the local table that store user_type");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->table, function (Blueprint $table) {
            $table->dropColumn("user_type");
            $table->dropColumn("user_type_id");
        });
    }
};
