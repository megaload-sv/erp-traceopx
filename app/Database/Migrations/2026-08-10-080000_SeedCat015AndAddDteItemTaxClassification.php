<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SeedCat015AndAddDteItemTaxClassification extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('fiscal_classification', 'dte_document_items')) {
            $this->forge->addColumn('dte_document_items', [
                'fiscal_classification' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'taxed',
                    'after' => 'item_type',
                ],
            ]);
        }

        $rows = [
            ['20', 'Impuesto al Valor Agregado 13%', 'summary'],
            ['C3', 'Impuesto al Valor Agregado (exportaciones) 0%', 'summary'],
            ['59', 'Turismo: por alojamiento (5%)', 'summary'],
            ['71', 'Turismo: salida del país por vía aérea $7.00', 'summary'],
            ['D1', 'FOVIAL ($0.20 Ctvs. por galón)', 'summary'],
            ['C8', 'COTRANS ($0.10 Ctvs. por galón)', 'summary'],
            ['D5', 'Otras tasas casos especiales', 'summary'],
            ['D4', 'Otros impuestos casos especiales', 'summary'],
            ['A8', 'Impuesto Especial al Combustible (0%, 0.5%, 1%)', 'body'],
            ['57', 'Impuesto industria de Cemento', 'body'],
            ['90', 'Impuesto especial a la primera matrícula', 'body'],
            ['D4', 'Otros impuestos casos especiales', 'body'],
            ['D5', 'Otras tasas casos especiales', 'body'],
            ['A6', 'Impuesto ad-valorem, armas de fuego, municiones explosivas y artículos similares', 'body'],
            ['C5', 'Impuesto ad-valorem por diferencial de precios de bebidas alcohólicas (8%)', 'informative'],
            ['C6', 'Impuesto ad-valorem por diferencial de precios al tabaco cigarrillos (39%)', 'informative'],
            ['C7', 'Impuesto ad-valorem por diferencial de precios al tabaco cigarros (100%)', 'informative'],
            ['19', 'Fabricante de Bebidas Gaseosas, Isotónicas, Deportivas, Fortificantes, Energizante o Estimulante', 'informative'],
            ['28', 'Importador de Bebidas Gaseosas, Isotónicas, Deportivas, Fortificantes, Energizante o Estimulante', 'informative'],
            ['31', 'Detallistas o Expendedores de Bebidas Alcohólicas', 'informative'],
            ['32', 'Fabricante de Cerveza', 'informative'],
            ['33', 'Importador de Cerveza', 'informative'],
            ['34', 'Fabricante de Productos de Tabaco', 'informative'],
            ['35', 'Importador de Productos de Tabaco', 'informative'],
            ['36', 'Fabricante de Armas de Fuego, Municiones y Artículos Similares', 'informative'],
            ['37', 'Importador de Arma de Fuego, Munición y Artículos Similares', 'informative'],
            ['38', 'Fabricante de Explosivos', 'informative'],
            ['39', 'Importador de Explosivos', 'informative'],
            ['42', 'Fabricante de Productos Pirotécnicos', 'informative'],
            ['43', 'Importador de Productos Pirotécnicos', 'informative'],
            ['44', 'Productor de Tabaco', 'informative'],
            ['50', 'Distribuidor de Bebidas Gaseosas, Isotónicas, Deportivas, Fortificantes, Energizante o Estimulante', 'informative'],
            ['51', 'Bebidas Alcohólicas', 'informative'],
            ['52', 'Cerveza', 'informative'],
            ['53', 'Productos del Tabaco', 'informative'],
            ['54', 'Bebidas Carbonatadas o Gaseosas Simples o Endulzadas', 'informative'],
            ['55', 'Otros Específicos', 'informative'],
            ['58', 'Alcohol', 'informative'],
            ['77', 'Importador de Jugos, Néctares, Bebidas con Jugo y Refrescos', 'informative'],
            ['78', 'Distribuidor de Jugos, Néctares, Bebidas con Jugo y Refrescos', 'informative'],
            ['79', 'Sobre Llamadas Telefónicas Provenientes del Exterior', 'informative'],
            ['85', 'Detallista de Jugos, Néctares, Bebidas con Jugo y Refrescos', 'informative'],
            ['86', 'Fabricante de Preparaciones Concentradas o en Polvo para la Elaboración de Bebidas', 'informative'],
            ['91', 'Fabricante de Jugos, Néctares, Bebidas con Jugo y Refrescos', 'informative'],
            ['92', 'Importador de Preparaciones Concentradas o en Polvo para la Elaboración de Bebidas', 'informative'],
            ['A1', 'Específicos y Ad-Valorem', 'informative'],
            ['A5', 'Bebidas Gaseosas, Isotónicas, Deportivas, Fortificantes, Energizantes o Estimulantes', 'informative'],
            ['A7', 'Alcohol Etílico', 'informative'],
            ['A9', 'Sacos Sintéticos', 'informative'],
        ];

        $now = date('Y-m-d H:i:s');
        $order = 1;
        foreach ($rows as [$code, $name, $scope]) {
            $existing = $this->db->table('mh_catalog_values')
                ->where('catalog_code', 'CAT-015')
                ->where('parent_code', $scope)
                ->where('code', $code)
                ->get()->getRowArray();

            $data = [
                'name' => $name,
                'display_order' => $order++,
                'status' => 1,
                'modify_user' => 'migration',
                'modify_date' => $now,
            ];

            if ($existing === null) {
                $this->db->table('mh_catalog_values')->insert($data + [
                    'catalog_code' => 'CAT-015',
                    'code' => $code,
                    'parent_code' => $scope,
                    'entry_user' => 'migration',
                    'entry_date' => $now,
                ]);
            } else {
                $this->db->table('mh_catalog_values')->where('id', (int) $existing['id'])->update($data);
            }
        }
    }

    public function down(): void
    {
        $this->db->table('mh_catalog_values')->where('catalog_code', 'CAT-015')->delete();
        if ($this->db->fieldExists('fiscal_classification', 'dte_document_items')) {
            $this->forge->dropColumn('dte_document_items', 'fiscal_classification');
        }
    }
}
