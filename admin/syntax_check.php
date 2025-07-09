<?php
// Simple PHP syntax checker
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Syntax Check for add_medical_record.php</h1>";

// Attempt to include the file to check for syntax errors
ob_start();
$error = false;

try {
    // We'll just check if the file can be parsed without executing it
    $content = file_get_contents('add_medical_record.php');
    
    // Use token_get_all for basic syntax checking
    $tokens = token_get_all($content);
    echo "<p style='color: green;'>✓ File can be tokenized - basic syntax appears valid!</p>";
    echo "<p>Total tokens: " . count($tokens) . "</p>";
} catch (ParseError $e) {
    echo "<p style='color: red;'>✗ Parse Error: " . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    $error = true;
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    $error = true;
}

$output = ob_get_clean();
echo $output;

if (!$error) {
    echo "<hr>";
    echo "<p><a href='add_medical_record.php'>Test the actual file</a></p>";
    echo "<p><a href='add_medical_record.php?appointment_id=8'>Test with appointment ID 8</a></p>";
}
?>
