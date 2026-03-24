<?php
// One-time OPcache reset script — DELETE AFTER USE
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo json_encode(['ok' => true, 'msg' => 'OPcache cleared']);
} else {
    echo json_encode(['ok' => false, 'msg' => 'OPcache not available']);
}
