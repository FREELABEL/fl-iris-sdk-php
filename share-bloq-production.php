<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Production configuration
$iris = new IRIS([
    'api_key' => getenv('IRIS_API_KEY') ?: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5ZmIwZDY3OS1hMmJjLTRmMmEtODdlZS02MDk5NzMwMmMxMjQiLCJqdGkiOiJlNDI5NTBjYTNhM2E0NzhjYzBmZTExMzliNjFkYTU2NDVhZTU3YjUyOTFkNjRlNmQyOGJjNjkwNzIyMGE0ZmMzYWU1N2FmZDJiMDQzNDNmMCIsImlhdCI6MTc2MTc3NTYxNi41OTYzNzgsIm5iZiI6MTc2MTc3NTYxNi41OTYzOCwiZXhwIjoxNzkzMzExNjE2LjU4MjM5MSwic3ViIjoiIiwic2NvcGVzIjpbXX0.XifXvOEbBtaFkyMb4mCuMJ6jnFHin5z6Rq38DL53tMuY-JARYOMh6E49l59maxbCM1dpNMBFgXUMdg6cWqcCevmduobTHUvESfWF0mdsDWn78Xio7s1uSijJ0deNzKzv6DAMBh-hTEorCbuzGlXGEgLgVSDmSjFSTpM9TA9cQNE-8yuIVg6bivS6kz9t1xrzyrB76NwsdfIdcwEpgnqV8JlOsCWh6d621-XSZVs9TousY-ou5UpVNCnuQNjZYvIJeFIDynsu26xNsosN3E7hnY6YSCU1ybgNm0aH32vpG0pmDbi5wj-DNCe0zNRgYr96schsAVkD8iSG9Jt4b81qQc-vRPj6NuaqhPbIYwiOEt5PC-qC8i7LWpQ5owgv5B2Xwq0IYUPkVYIQXFQpeVdas_IaATMX48YGpac0MfgVGkV2KHmapftbgYKSyiY5y4NNbJjzvtKLBm_BL9ucPyLunI-wTPWGwGA2Pq2kyJ4u3GhkWaEtaHfXRRW7nGSPU-ZW28o6aE6GsqdwCjV6fsZpgSRjBZyd5fhURLkRWgR7-r5-UxMjQQQXf8lrnyb8uGtfa8gPraZbLFX9Psn51GU8vE7ZJ6Fx-_RS-7ziuGtBf6z9c04sB9lP4HVTeR2cBXRHUhuO1X97XdZ69r585F5rnbKwgzBHwD-AB_NoJYra5Mc',
    'user_id' => 193, // Owner of bloq 203 (Team Ayala)
    'fl_api_url' => 'https://apiv2.heyiris.io',
    'iris_url' => 'https://iris-api.freelabel.net'
]);

echo "Sharing bloq 203 (Team Ayala) with user 5137 (john.ayala@aec-hq.com)...\n\n";

try {
    $result = $iris->bloqs->share(
        203,    // Bloq ID: Team Ayala
        5137,   // User ID: john.ayala@aec-hq.com
        'owner' // Permission level
    );
    
    echo "✅ SUCCESS! Bloq shared successfully!\n\n";
    echo "Details:\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nFull error:\n";
    echo $e->getTraceAsString() . "\n";
}
