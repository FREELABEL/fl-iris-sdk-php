<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client([
    'base_uri' => 'https://apiv2.heyiris.io',
    'headers' => [
        'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5ZmIwZDY3OS1hMmJjLTRmMmEtODdlZS02MDk5NzMwMmMxMjQiLCJqdGkiOiJlNDI5NTBjYTNhM2E0NzhjYzBmZTExMzliNjFkYTU2NDVhZTU3YjUyOTFkNjRlNmQyOGJjNjkwNzIyMGE0ZmMzYWU1N2FmZDJiMDQzNDNmMCIsImlhdCI6MTc2MTc3NTYxNi41OTYzNzgsIm5iZiI6MTc2MTc3NTYxNi41OTYzOCwiZXhwIjoxNzkzMzExNjE2LjU4MjM5MSwic3ViIjoiIiwic2NvcGVzIjpbXX0.XifXvOEbBtaFkyMb4mCuMJ6jnFHin5z6Rq38DL53tMuY-JARYOMh6E49l59maxbCM1dpNMBFgXUMdg6cWqcCevmduobTHUvESfWF0mdsDWn78Xio7s1uSijJ0deNzKzv6DAMBh-hTEorCbuzGlXGEgLgVSDmSjFSTpM9TA9cQNE-8yuIVg6bivS6kz9t1xrzyrB76NwsdfIdcwEpgnqV8JlOsCWh6d621-XSZVs9TousY-ou5UpVNCnuQNjZYvIJeFIDynsu26xNsosN3E7hnY6YSCU1ybgNm0aH32vpG0pmDbi5wj-DNCe0zNRgYr96schsAVkD8iSG9Jt4b81qQc-vRPj6NuaqhPbIYwiOEt5PC-qC8i7LWpQ5owgv5B2Xwq0IYUPkVYIQXFQpeVdas_IaATMX48YGpac0MfgVGkV2KHmapftbgYKSyiY5y4NNbJjzvtKLBm_BL9ucPyLunI-wTPWGwGA2Pq2kyJ4u3GhkWaEtaHfXRRW7nGSPU-ZW28o6aE6GsqdwCjV6fsZpgSRjBZyd5fhURLkRWgR7-r5-UxMjQQQXf8lrnyb8uGtfa8gPraZbLFX9Psn51GU8vE7ZJ6Fx-_RS-7ziuGtBf6z9c04sB9lP4HVTeR2cBXRHwD-AB_NoJYra5Mc',
        'Accept' => 'application/json',
    ]
]);

echo "Checking bloq 203 ownership...\n\n";

try {
    $response = $client->get('/api/v1/user/193/bloqs/203?user_id=193');
    $data = json_decode($response->getBody()->getContents(), true);
    
    echo "Bloq Details:\n";
    echo "ID: " . ($data['data']['id'] ?? 'N/A') . "\n";
    echo "Name: " . ($data['data']['name'] ?? 'N/A') . "\n";
    echo "User ID (Owner): " . ($data['data']['user_id'] ?? 'N/A') . "\n";
    echo "Type: " . ($data['data']['type'] ?? 'N/A') . "\n";
    echo "\nFull response:\n";
    print_r($data);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getResponse')) {
        echo "\nResponse body:\n";
        echo $e->getResponse()->getBody()->getContents() . "\n";
    }
}
