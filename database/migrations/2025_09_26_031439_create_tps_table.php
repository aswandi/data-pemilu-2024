<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tps', function (Blueprint $table) {
            $table->id();
            $table->string('pro', 100);
            $table->string('kab', 100);
            $table->string('kec', 100);
            $table->string('kel', 100);
            $table->string('kode_kel', 20);
            $table->integer('no_tps');
            $table->integer('dpt_l');
            $table->integer('dpt_p');
            $table->integer('total_dpt');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tps');
    }
};
