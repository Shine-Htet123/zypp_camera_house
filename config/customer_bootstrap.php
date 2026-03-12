<?php

require_once __DIR__ . '/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
