<?php

use App\Mcp\Servers\TrackerServer;
use Laravel\Mcp\Facades\Mcp;

// Same Sanctum tokens, abilities and rate limit as the REST API.
Mcp::web('/mcp', TrackerServer::class)->middleware(['auth:sanctum', 'throttle:api']);
