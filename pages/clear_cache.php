<?php
// Clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared<br>";
}

// Clear realpath cache
clearstatcache(true);
echo "Realpath cache cleared<br>";

echo "<br>Please refresh cash.php with Ctrl+Shift+R";
