<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CortesExcelExporter
{
    public function download(Collection $usuarios)
    {
        $filename = 'usuarios-por-cortar-' . now()->format('Y-m-d') . '.xls';

        return response()->stream(function () use ($usuarios) {
            echo "\xEF\xBB\xBF";
            echo '<html><head><meta charset="UTF-8"><style>';
            echo 'table{border-collapse:collapse;font-family:Calibri,sans-serif;font-size:11pt}';
            echo 'th,td{border:1px solid #b7b7b7;padding:4px 7px;white-space:nowrap}';
            echo 'th{background:#1f4e78;color:#fff;font-weight:bold}';
            echo '.varios-meses td{background:#ffc7ce;color:#9c0006;font-weight:bold}';
            echo '</style></head><body><table><thead><tr>';

            foreach (['ID', 'Nombre Cliente', 'Zona', 'IP', 'MAC', 'Cortador Asignado', 'Estado Corte'] as $titulo) {
                echo '<th>' . $titulo . '</th>';
            }

            echo '</tr></thead><tbody>';
            foreach ($usuarios as $usuario) {
                $clase = ($usuario->resaltar_adeudo_corte ?? false) ? ' class="varios-meses"' : '';
                echo '<tr' . $clase . '>';
                foreach ([
                    $usuario->numero_servicio,
                    $usuario->nombre_cliente,
                    $usuario->zona ?? '-',
                    $usuario->ip ?? '-',
                    $usuario->mac ?? '-',
                    $usuario->cortador?->nombre ?? '-',
                    $usuario->estado_corte ?? '-',
                ] as $valor) {
                    echo '<td>' . htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table></body></html>';
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
