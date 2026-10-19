<?php

// On Vercel only /tmp is writable
foreach (['app', 'logs', 'framework/views', 'framework/cache/data', 'framework/sessions'] as $dir) {
    @mkdir("/tmp/storage/$dir", 0777, true);
}

require __DIR__ . '/../public/index.php';