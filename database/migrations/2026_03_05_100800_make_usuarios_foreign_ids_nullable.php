<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `usuarios` DROP FOREIGN KEY `usuarios_estado_id_foreign`');
            DB::statement('ALTER TABLE `usuarios` DROP FOREIGN KEY `usuarios_estatus_servicio_id_foreign`');
            DB::statement('ALTER TABLE `usuarios` DROP FOREIGN KEY `usuarios_servicio_id_foreign`');
            DB::statement('ALTER TABLE `usuarios` MODIFY `estado_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `usuarios` MODIFY `estatus_servicio_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `usuarios` MODIFY `servicio_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `usuarios` ADD CONSTRAINT `usuarios_estado_id_foreign` FOREIGN KEY (`estado_id`) REFERENCES `estados`(`id`)');
            DB::statement('ALTER TABLE `usuarios` ADD CONSTRAINT `usuarios_estatus_servicio_id_foreign` FOREIGN KEY (`estatus_servicio_id`) REFERENCES `estatus_servicios`(`id`)');
            DB::statement('ALTER TABLE `usuarios` ADD CONSTRAINT `usuarios_servicio_id_foreign` FOREIGN KEY (`servicio_id`) REFERENCES `servicios`(`id`)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `usuarios` DROP FOREIGN KEY `usuarios_estado_id_foreign`');
            DB::statement('ALTER TABLE `usuarios` DROP FOREIGN KEY `usuarios_estatus_servicio_id_foreign`');
            DB::statement('ALTER TABLE `usuarios` DROP FOREIGN KEY `usuarios_servicio_id_foreign`');
            DB::statement('ALTER TABLE `usuarios` MODIFY `estado_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `usuarios` MODIFY `estatus_servicio_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `usuarios` MODIFY `servicio_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `usuarios` ADD CONSTRAINT `usuarios_estado_id_foreign` FOREIGN KEY (`estado_id`) REFERENCES `estados`(`id`)');
            DB::statement('ALTER TABLE `usuarios` ADD CONSTRAINT `usuarios_estatus_servicio_id_foreign` FOREIGN KEY (`estatus_servicio_id`) REFERENCES `estatus_servicios`(`id`)');
            DB::statement('ALTER TABLE `usuarios` ADD CONSTRAINT `usuarios_servicio_id_foreign` FOREIGN KEY (`servicio_id`) REFERENCES `servicios`(`id`)');
        }
    }
};

