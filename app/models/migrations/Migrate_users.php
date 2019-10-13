<?php

defined('BASE') or exit('No direct script access allowed');

class Migrate_users extends Model
{
    private $schema;
    private $table = 'users';

    public function __construct()
    {
        parent::__construct();
        $this->schema = $this->schema();
        $this->load->plugin('faker');
    }

    public function up()
    {
        $table = $this->schema->createTable($this->table);

        $table->addColumn('fullname')->varchar(100)->nullable(false)->index();
        $table->addColumn('email')->varchar(100)->nullable(false)->index();
        $table->addColumn('username')->varchar(50)->nullable(false)->index();
        $table->addColumn('password')->varchar(100)->nullable(false);
        $table->addColumn('address')->varchar();
        $table->addColumn('phone')->varchar(20);
        $table->addColumn('banned')->tinyint(1)->nullable(false)->defaults(0);
        $table->addColumn('ip_address')->varchar(20);

        $table->addColumn('created_at')->timestamp(true)->nullable(false);
        $table->addColumn('updated_at')->dateTime();
        $table->addColumn('deleted_at')->dateTime();

        $table->engine('InnoDB');
        $table->charset('latin1', 'latin1_general_ci');
        $table->build();
    }

    public function down()
    {
        $this->schema->dropTable($this->table);
    }

    public function seed()
    {
        $data = [];
        $count = 100;

        for ($i = 0; $i < 100; ++$i) {
            $data[$i] = [
                'fullname'   => $this->faker->randomName(),
                'email'      => $this->faker->email(),
                'username'   => $this->faker->makeRandomWord(4).mt_rand(1, 99),
                'password'   => password_hash('password', PASSWORD_DEFAULT),
                'address'    => $this->faker->address(),
                'phone'      => $this->faker->phone(false),
                'banned'     => mt_rand(0, 1),
                'ip_address' => mt_rand(0, 255).'.'.mt_rand(0, 255).'.'.mt_rand(0, 255),
            ];
        }

        $this->db->from($this->table);
        for ($i = 0; $i < $count; ++$i) {
            $this->db->insert($data[$i])->execute();
        }
    }
}
