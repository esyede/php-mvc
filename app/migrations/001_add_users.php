<?php

defined('BASE') or exit('No direct script access allowed');


class Migration_add_users extends Migration
{
	public function up()
	{
	    $table = $this->schema->createTable('users');
	    $table->addColumn('name')->varchar()->nullable(false);
	    $table->addColumn('username')->varchar()->nullable(false)->index(true);
	    $table->addColumn('password')->varchar()->nullable(false);
	    $table->addColumn('authenticated')->tinyint(1)->nullable(false)->defaults(1);
	    $table->addColumn('created_at')->timestamp(true)->nullable(false);
	    $table->addColumn('updated_at')->datetime();
	    $table->addColumn('deleted_at')->datetime();
	    $table->build();
	}

	
	public function down()
	{
	    $this->schema->dropTable('users');
	}
}