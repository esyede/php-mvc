<?php

defined('BASE') or exit('No direct script access allowed');


class Migration_add_permission_roles extends Migration
{
	public function up()
    {
        $table = $this->schema->createTable('permission_roles');
        $table->addColumn('role_id')->int(11)->nullable(false)->index(true);
        $table->addColumn('permission_id')->int(11)->nullable(false)->index(true);
        $table->primary(['role_id', 'permission_id']);
        $table->build();
    }

    
    public function down()
    {
        $this->schema->dropTable('permission_roles');
    }
}