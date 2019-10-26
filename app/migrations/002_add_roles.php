<?php

defined('BASE') or exit('No direct script access allowed');

class Migration_add_roles extends Migration
{
    public function up()
    {
        $table = $this->schema->createTable('roles');
        $table->addColumn('name')->varchar()->nullable(false)->index(true);
        $table->addColumn('display_name')->varchar(30)->nullable(false)->index();
        $table->addColumn('description')->varchar(255)->nullable();
        $table->addColumn('authenticated')->tinyint(1)->nullable(false)->defaults(1);
        $table->addColumn('created_at')->timestamp(true)->nullable(false);
        $table->addColumn('updated_at')->datetime();
        $table->addColumn('deleted_at')->datetime();
        $table->build();
    }

    public function down()
    {
        $this->schema->dropTable('roles');
    }
}
