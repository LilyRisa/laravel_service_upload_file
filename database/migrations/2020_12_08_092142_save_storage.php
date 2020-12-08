<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SaveStorage extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('storage_file', function (Blueprint $table) {
            $table->id();
            $table->string('token')->nullable();
            $table->string('type')->nullable();
            //$table->binary('data')->nullable();
            $table->timestamps();
        });
        DB::statement("ALTER TABLE storage_file ADD data LONGBLOB NOT NULL");// CHỈ DÀNH CHO MYSQL CÓ LONGBLOB
        //DB::statement("ALTER TABLE storage_file ADD data MEDIUMBLOB");
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
}
