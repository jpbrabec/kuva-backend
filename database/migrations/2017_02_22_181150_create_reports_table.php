<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::create('photo_reports', function (Blueprint $table) {
          $table->increments('id');
          $table->string('message');
          $table->string('token');
          $table->integer('photo_id')->unsigned()->nullable();
          $table->foreign('photo_id')->references('id')->on('photos');
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
      Schema::dropIfExists('photo_reports');
  }
}
