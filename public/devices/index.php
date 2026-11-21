<?php

declare(strict_types=1);

/**
 * OmniMesh Proxy Handler for Built-in PHP Web Server (/devices)
 */

$_SERVER['REQUEST_URI'] = '/devices';
require_once dirname(__DIR__) . '/index.php';
