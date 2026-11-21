<?php

declare(strict_types=1);

/**
 * OmniMesh Proxy Handler for Built-in PHP Web Server (/rules)
 */

$_SERVER['REQUEST_URI'] = '/rules';
require_once dirname(__DIR__) . '/index.php';
