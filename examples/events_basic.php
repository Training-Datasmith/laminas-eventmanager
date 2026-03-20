<?php

declare(strict_types=1);

/**
 * Example: attaching listeners and triggering events with laminas-eventmanager.
 *
 * Run from the laminas-eventmanager project root:
 *   php examples/events_basic.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Laminas\EventManager\EventManager;
use Laminas\EventManager\Event;

$events = new EventManager();

// --- Basic listener ---
$events->attach('user.created', function (Event $e): void {
    $params = $e->get_params();
    echo "Listener 1 — user created: " . $params['name'] . "\n";
});

// --- Priority (higher runs first) ---
$events->attach('user.created', function (Event $e): void {
    echo "Listener 2 (priority 100) — runs first\n";
}, 100);

$events->attach('user.created', function (Event $e): void {
    echo "Listener 3 (priority -10) — runs last\n";
}, -10);

echo "--- Triggering user.created ---\n";
$events->trigger('user.created', null, ['name' => 'Alice', 'email' => 'alice@example.com']);

// --- Short-circuit: stop propagation ---
$events->attach('order.checkout', function (Event $e) {
    $params = $e->get_params();
    if ($params['total'] < 0) {
        echo "Invalid total — stopping propagation.\n";
        $e->stop_propagation(true);
        return false;
    }
    echo "Processing order for total: " . $params['total'] . "\n";
});

$events->attach('order.checkout', function (Event $e): void {
    echo "This runs only if propagation was not stopped.\n";
});

echo "\n--- Triggering order.checkout (valid) ---\n";
$events->trigger('order.checkout', null, ['total' => 99.95]);

echo "\n--- Triggering order.checkout (invalid) ---\n";
$events->trigger('order.checkout', null, ['total' => -5]);

// --- Collecting return values ---
$events->attach('calc', fn(Event $e): int => $e->get_params()['a'] + $e->get_params()['b']);
$events->attach('calc', fn(Event $e): int => $e->get_params()['a'] * $e->get_params()['b']);

echo "\n--- Collecting calc results ---\n";
$results = $events->trigger('calc', null, ['a' => 3, 'b' => 4]);
foreach ($results as $result) {
    echo "Result: $result\n";
}
