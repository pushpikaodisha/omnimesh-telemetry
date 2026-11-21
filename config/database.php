<?php

declare(strict_types=1);

/**
 * OmniMesh Telemetry Orchestrator
 * Database Configuration & Schema Migration Helper
 */

return [
    'driver' => 'sqlite',
    'database' => dirname(__DIR__) . '/database/omnimesh.sqlite',
    'schema' => dirname(__DIR__) . '/database/schema.sql',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
    ],
    'auto_migrate' => true,
];
