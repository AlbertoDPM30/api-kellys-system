<?php

namespace App\Console\Commands;

use App\Services\PermisoDetector;
use Illuminate\Console\Command;

class SyncPermisos extends Command
{
    protected $signature = 'permisos:sync';
    protected $description = 'Detecta nuevos permisos desde el código y los registra en BD';

    public function handle()
    {
        $nuevos = PermisoDetector::syncPermisos();
        $this->info("Sincronización completada. Nuevos permisos: " . count($nuevos));
        foreach ($nuevos as $p) {
            $this->line("- " . $p->nombre_tecnico);
        }
    }
}