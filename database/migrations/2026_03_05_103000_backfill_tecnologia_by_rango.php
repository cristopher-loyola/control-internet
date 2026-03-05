<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // INA: 1000–4200
        DB::statement("
            UPDATE `usuarios`
            SET `tecnologia` = 'ina'
            WHERE (`tecnologia` IS NULL OR `tecnologia` = '' OR `tecnologia` = '-')
              AND `numero_servicio` BETWEEN 1000 AND 4200
        ");

        // FOI: 4800–5400, 5500–5999
        DB::statement("
            UPDATE `usuarios`
            SET `tecnologia` = 'foi'
            WHERE (`tecnologia` IS NULL OR `tecnologia` = '' OR `tecnologia` = '-')
              AND (
                    (`numero_servicio` BETWEEN 4800 AND 5400)
                 OR (`numero_servicio` BETWEEN 5500 AND 5999)
              )
        ");

        // FOD: 5401–5499, >= 6000
        DB::statement("
            UPDATE `usuarios`
            SET `tecnologia` = 'fod'
            WHERE (`tecnologia` IS NULL OR `tecnologia` = '' OR `tecnologia` = '-')
              AND (
                    (`numero_servicio` BETWEEN 5401 AND 5499)
                 OR (`numero_servicio` >= 6000)
              )
        ");
    }

    public function down(): void
    {
        // Revert only values we set by rule back to NULL (keep explicit values)
        DB::statement("
            UPDATE `usuarios`
            SET `tecnologia` = NULL
            WHERE `tecnologia` IN ('ina', 'foi', 'fod')
        ");
    }
};

