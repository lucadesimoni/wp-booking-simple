<?php
/**
 * Consistency Check Script
 * Checks for naming inconsistencies in the plugin
 *
 * Development helper only. It is not part of the distributed plugin (see the
 * INCLUDE list in build.sh), but a plugin installed by cloning the repository
 * would expose it at a public URL, so refuse to run outside the command line.
 */

if ( 'cli' !== PHP_SAPI ) {
	header( 'HTTP/1.0 403 Forbidden' );
	exit;
}

$baseDir = __DIR__;
$issues = array();

// Check constants
echo "=== Checking Constants ===\n";
$files = glob($baseDir . '/**/*.php', GLOB_BRACE);
foreach ($files as $file) {
    $content = file_get_contents($file);
    // Check for old constant names
    if (preg_match('/WP_BOOKING_SYSTEM_[^L]/', $content) || 
        preg_match('/WP_Booking_System_Luca_Luca/', $content)) {
        $issues[] = "Potential duplicate 'Luca' in: " . basename($file);
    }
}

// Check function names
echo "=== Checking Function Names ===\n";
foreach ($files as $file) {
    $content = file_get_contents($file);
    // Check for wrong casing in function names
    if (preg_match('/function [A-Z][a-z_]+\(/', $content, $matches)) {
        $funcName = $matches[0];
        if (strpos($funcName, 'wp_booking_system') !== false && 
            !preg_match('/^function wp_booking_system_luca\(/', $funcName)) {
            $issues[] = "Function naming issue in: " . basename($file) . " - " . $funcName;
        }
    }
}

// Check class names
echo "=== Checking Class Names ===\n";
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (preg_match('/class WP_Booking_System/', $content)) {
        if (strpos($content, 'WP_Booking_System_Luca_Luca') !== false) {
            $issues[] = "Duplicate 'Luca' in class name: " . basename($file);
        }
    }
}

// Summary
echo "\n=== Summary ===\n";
if (empty($issues)) {
    echo "✓ No major naming inconsistencies found!\n";
} else {
    echo "Found " . count($issues) . " potential issues:\n";
    foreach ($issues as $issue) {
        echo "- $issue\n";
    }
}




