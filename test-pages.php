<?php

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    // Use local credentials if environment is local
    $env = $_ENV['IRIS_ENV'] ?? 'production';
    $apiKey = $env === 'local' ? $_ENV['IRIS_LOCAL_API_KEY'] : $_ENV['IRIS_API_KEY'];
    $userId = $env === 'local' ? $_ENV['IRIS_LOCAL_USER_ID'] : $_ENV['IRIS_USER_ID'];
    
    $iris = new IRIS([
        'api_key' => $apiKey,
        'user_id' => (int) $userId,
        'debug' => true,
    ]);

    echo "Creating page...\n";
    
    $page = $iris->pages->create([
        'slug' => 'test-direct',
        'title' => 'Test Direct Page',
        'seo_title' => 'Test Direct Page',
        'seo_description' => 'A test page created directly via SDK',
        'status' => 'draft',
        'owner_type' => 'system',
        'owner_id' => 1,
        'theme' => [
            'mode' => 'dark',
            'branding' => [
                'name' => 'Test Brand',
                'primaryColor' => '#6366f1',
                'secondaryColor' => '#8b5cf6',
            ],
        ],
        'components' => [
            [
                'type' => 'Hero',
                'id' => 'hero-test',
                'props' => [
                    'title' => 'Welcome to Test Page',
                    'subtitle' => 'This is a test',
                    'backgroundGradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'titleColor' => '#ffffff',
                    'subtitleColor' => 'rgba(255, 255, 255, 0.9)',
                    'textAlign' => 'center',
                    'minHeight' => '500px',
                ],
            ],
        ],
    ]);

    echo "Page created successfully!\n";
    print_r($page);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
