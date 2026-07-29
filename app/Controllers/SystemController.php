<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

class SystemController extends BaseController
{
    private function validateToken(): ?ResponseInterface
    {
        $configuredToken = trim((string) env('SYSTEM_MIGRATION_TOKEN', ''));
        $providedToken = trim((string) $this->request->getGet('token'));

        if ($configuredToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'message' => 'Acceso no autorizado.',
                ]);
        }

        return null;
    }

    public function migrate(): ResponseInterface
    {
        if ($response = $this->validateToken()) {
            return $response;
        }

        try {
            $migrations = Services::migrations();
            $migrations->latest();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Migraciones ejecutadas correctamente.',
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Error ejecutando migraciones: {message}', ['message' => $e->getMessage()]);

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'No fue posible ejecutar las migraciones.',
                ]);
        }
    }

    public function seed(string $seederName = 'SecuritySeeder'): ResponseInterface
    {
        if ($response = $this->validateToken()) {
            return $response;
        }

        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $seederName)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Nombre de seeder no válido.',
                ]);
        }

        try {
            $seeder = Services::seeder();
            $seeder->call($seederName);

            return $this->response->setJSON([
                'success' => true,
                'message' => "Seeder {$seederName} ejecutado correctamente.",
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Error ejecutando seeder {seeder}: {message}', [
                'seeder' => $seederName,
                'message' => $e->getMessage(),
            ]);

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => "No fue posible ejecutar el seeder {$seederName}.",
                ]);
        }
    }

    public function setup(): ResponseInterface
    {
        if ($response = $this->validateToken()) {
            return $response;
        }

        try {
            Services::migrations()->latest();
            Services::seeder()->call('SecuritySeeder');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configuración inicial completada: migraciones y SecuritySeeder ejecutados.',
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Error ejecutando configuración inicial: {message}', ['message' => $e->getMessage()]);

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'No fue posible completar la configuración inicial.',
                ]);
        }
    }
}
