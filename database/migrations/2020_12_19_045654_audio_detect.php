<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AudioDetect extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('audio_detect', function (Blueprint $table) {
            $table->id();
            $table->string('token')->nullable();
            $table->string('title')->nullable();
            $table->string('release_date')->nullable();
            $table->string('album')->nullable();
            $table->string('label')->nullable();
            $table->string('timecode')->nullable();
            $table->string('song_link')->nullable();
            $table->timestamps();
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
}
