<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class CreateRevisionsTable extends Migration
{

    protected $table;

    public function __construct()
    {
        $this->table = Config::get('revisionable.table', 'revisions');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('action');

            $table->string('table_name');

            $table->string('revisionable_type')->nullable();
            $table->integer('revisionable_id')->unsigned();

            $table->unsignedInteger('user_id')->nullable();

            $table->binary('old')->nullable();
            $table->binary('new')->nullable();

            $table->string('ip')->nullable();
            $table->string('ip_forwarded')->nullable();

            $table->timestamps();

            $table->index('action');
            $table->index('user_id');
            $table->index(['revisionable_type', 'revisionable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
}
