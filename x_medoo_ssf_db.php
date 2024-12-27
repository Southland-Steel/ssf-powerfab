<?php
require_once 'vendor/autoload.php';

use Medoo\Medoo;

class TeklaMedoo extends Medoo
{
    // Override the update method
    public function update($table, $data, $where = null): ?PDOStatement
    {
        throw new Exception("Update operations are disabled.");
    }

    // Override the delete method
    public function delete($table, $where = null): ?PDOStatement
    {
        throw new Exception("Delete operations are disabled.");
    }
}

$tkdb = new TeklaMedoo([
    'database_type' => 'mysql',
    'database_name' => 'fabrication',
    'server' => '192.168.80.12',
    'username' => 'ssf.reporter',
    'password' => 'SSF.reporter251@*',
]);