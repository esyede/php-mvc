<?php

defined('BASE') or exit('No direct script access allowed');


class Migration_add_roles_users extends Migration
{
	public function up()
    {
        $table = $this->schema->createTable('roles_users');
        $table->addColumn('user_id')->int(11)->nullable(false)->index(true);
        $table->addColumn('role_id')->int(11)->nullable(false)->index(true);
        $table->primary(['user_id', 'role_id']);
        $table->build();
    }

    
    public function down()
    {
        $this->schema->dropTable('roles_users');
    }
}