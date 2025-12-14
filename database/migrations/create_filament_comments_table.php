<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tableName = config('filament-comments.table_name', 'filament_comments');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->text('comment');
            $table->boolean('is_approved')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        $tableName = config('filament-comments.table_name', 'filament_comments');

        Schema::dropIfExists($tableName);
    }
};
