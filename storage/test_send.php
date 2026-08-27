<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::first();
    if (!$user) {
        echo "NO_USER_FOUND\n";
        exit(1);
    }
    echo "Sending email to " . $user->email . "...\n";
    $user->sendPasswordResetNotification('test-token-abcdef');
    echo "EMAIL_SENT_SUCCESSFULLY\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
