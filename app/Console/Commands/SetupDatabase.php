<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SetupDatabase extends Command
{
    protected $signature = 'db:setup';
    protected $description = 'Crea la base de datos si no existe y ejecuta migraciones';

    public function handle()
    {
        $database = Config::get('database.connections.mysql.database');
        $this->info("Verificando base de datos: $database");
        DB::statement("CREATE DATABASE IF NOT EXISTS `$database`");
        $this->info("Base de datos asegurada.");
        $this->call('migrate');
        $this->call('db:seed', ['--class' => 'MasterUserSeeder']);
        $this->call('permisos:sync');
        $this->info("Entorno listo.");
    }
}
