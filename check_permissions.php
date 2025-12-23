<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'initiator1@example.com')->first();
if ($user) {
    echo "User: " . $user->email . "\n";
    echo "Roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    echo "Permissions: " . $user->getAllPermissions()->pluck('name')->implode(', ') . "\n";
} else {
    echo "User not found\n";
}
