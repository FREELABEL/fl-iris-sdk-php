<?php
/**
 * Create 5slow.com DJ landing page using IRIS SDK
 * Demonstrates: SiteNavigation, Hero, SplitContent, ImageGallery, EnrollmentForm, SiteFooter
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Use local credentials if environment is local
$env = $_ENV['IRIS_ENV'] ?? 'production';
$apiKey = $env === 'local' ? $_ENV['IRIS_LOCAL_API_KEY'] : $_ENV['IRIS_API_KEY'];
$userId = $env === 'local' ? $_ENV['IRIS_LOCAL_USER_ID'] : $_ENV['IRIS_USER_ID'];

$iris = new IRIS([
    'api_key' => $apiKey,
    'user_id' => (int) $userId,
]);

echo "Creating 5.Slow DJ landing page...\n\n";

try {
    $page = $iris->pages->create([
        'slug' => '5slow',
        'title' => '5.Slow DJ - Austin, TX',
        'seo_title' => '5.Slow DJ | Create Through Music | Austin, TX',
        'seo_description' => 'Let 5.Slow take your party to the next level with electrifying beats and non-stop entertainment. Professional DJ based in Austin, Texas.',
        'status' => 'draft',
        'theme' => [
            'mode' => 'dark',
            'backgroundColor' => '#000000',
            'branding' => [
                'name' => '5.Slow',
                'primaryColor' => '#ffffff',
                'secondaryColor' => '#333333',
            ],
        ],
        'components' => [
            // 1. Navigation Bar
            [
                'type' => 'SiteNavigation',
                'id' => 'nav-main',
                'props' => [
                    'logo' => [
                        'text' => '5.Slow',
                        'url' => '#',
                        'accentDot' => false,
                    ],
                    'links' => [
                        ['label' => 'About', 'url' => '#about'],
                        ['label' => 'Gallery', 'url' => '#gallery'],
                        ['label' => 'Contact', 'url' => '#contact'],
                    ],
                    'ctaButton' => [
                        'text' => 'Book Now',
                        'url' => '#contact',
                    ],
                    'themeMode' => 'dark',
                ],
            ],

            // 2. Hero Section
            [
                'type' => 'Hero',
                'id' => 'hero-main',
                'props' => [
                    'title' => 'CREATE THROUGH MUSIC',
                    'subtitle' => 'Let 5.Slow take your party to the next level with electrifying beats and non-stop entertainment',
                    'backgroundImage' => 'https://images.unsplash.com/photo-1571266028243-e4733b0f0bb0?w=1920&q=80',
                    'primaryButtonText' => 'BOOK NOW',
                    'primaryButtonUrl' => '#contact',
                    'textAlign' => 'center',
                    'minHeight' => '90vh',
                    'fullHeight' => false,
                    'overlayOpacity' => 0.55,
                    'themeMode' => 'dark',
                    'titleColor' => '#ffffff',
                    'subtitleColor' => 'rgba(255, 255, 255, 0.85)',
                ],
            ],

            // 3. About Section (Split Content)
            [
                'type' => 'SplitContent',
                'id' => 'about-section',
                'props' => [
                    'title' => 'About 5.Slow',
                    'content' => "5.Slow is a professional DJ based in Austin, Texas, offering top-notch entertainment for events, parties, and celebrations.\n\nWith years of experience and a passion for music, 5.Slow brings electrifying energy to every event. Whether it's a wedding, corporate event, or private party, expect nothing but the best beats and seamless mixing that keeps the crowd moving all night long.\n\n**Specialties:**\n- Weddings & Receptions\n- Corporate Events\n- Private Parties\n- Club Nights\n- Festival Sets",
                    'mediaUrl' => 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?w=800&q=80',
                    'mediaAlt' => '5.Slow performing at a live event',
                    'mediaPosition' => 'left',
                    'markdown' => true,
                    'verticalAlign' => 'center',
                    'themeMode' => 'dark',
                ],
            ],

            // 4. Image Gallery
            [
                'type' => 'ImageGallery',
                'id' => 'gallery-events',
                'props' => [
                    'title' => "Gallery of 5.Slow's Memorable Events",
                    'columns' => 2,
                    'gap' => 'md',
                    'rounded' => true,
                    'aspectRatio' => '4:3',
                    'themeMode' => 'dark',
                    'images' => [
                        [
                            'src' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&q=80',
                            'alt' => 'Live DJ performance with crowd',
                        ],
                        [
                            'src' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=600&q=80',
                            'alt' => 'Concert with colorful lights',
                        ],
                        [
                            'src' => 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=600&q=80',
                            'alt' => 'DJ mixing at turntables',
                        ],
                        [
                            'src' => 'https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=600&q=80',
                            'alt' => 'Crowd enjoying music at event',
                        ],
                    ],
                ],
            ],

            // 5. Contact Form
            [
                'type' => 'EnrollmentForm',
                'id' => 'contact-form',
                'props' => [
                    'title' => 'Get In Touch',
                    'subtitle' => 'Ready to book 5.Slow for your next event? Send a message and let\'s make it happen.',
                    'submitEndpoint' => '/api/form-submissions',
                    'fields' => [
                        [
                            'id' => 'name',
                            'label' => 'Name',
                            'type' => 'text',
                            'required' => true,
                            'placeholder' => 'Your name',
                        ],
                        [
                            'id' => 'email',
                            'label' => 'Email',
                            'type' => 'email',
                            'required' => true,
                            'placeholder' => 'your@email.com',
                        ],
                        [
                            'id' => 'event_type',
                            'label' => 'Event Type',
                            'type' => 'select',
                            'required' => false,
                            'options' => ['Wedding', 'Corporate Event', 'Private Party', 'Club Night', 'Festival', 'Other'],
                        ],
                        [
                            'id' => 'message',
                            'label' => 'Message',
                            'type' => 'textarea',
                            'required' => false,
                            'placeholder' => 'Tell us about your event...',
                        ],
                    ],
                    'buttonText' => 'Send Message',
                    'buttonVariant' => 'primary',
                    'successMessage' => 'Thanks for reaching out! 5.Slow will get back to you soon.',
                    'themeMode' => 'dark',
                    'maxWidth' => 'lg',
                ],
            ],

            // 6. Footer
            [
                'type' => 'SiteFooter',
                'id' => 'footer-main',
                'props' => [
                    'logo' => [
                        'text' => '5.Slow',
                        'tagline' => 'Professional DJ entertainment based in Austin, Texas.',
                        'accentDot' => false,
                    ],
                    'columns' => [
                        [
                            'title' => 'Navigation',
                            'links' => [
                                ['label' => 'About', 'url' => '#about'],
                                ['label' => 'Gallery', 'url' => '#gallery'],
                                ['label' => 'Contact', 'url' => '#contact'],
                            ],
                        ],
                        [
                            'title' => 'Services',
                            'links' => [
                                ['label' => 'Weddings', 'url' => '#contact'],
                                ['label' => 'Corporate', 'url' => '#contact'],
                                ['label' => 'Private Parties', 'url' => '#contact'],
                            ],
                        ],
                    ],
                    'socialLinks' => [
                        ['platform' => 'instagram', 'url' => 'https://instagram.com/5slow'],
                    ],
                    'copyright' => "\u{00A9} 2026 5.Slow. All rights reserved.",
                    'themeMode' => 'dark',
                ],
            ],
        ],
    ]);

    $pageData = $page['data'] ?? $page;

    echo "Page created successfully!\n";
    echo "  ID: {$pageData['id']}\n";
    echo "  Slug: {$pageData['slug']}\n";
    echo "  Status: {$pageData['status']}\n\n";

    // Publish the page
    echo "Publishing page...\n";
    $published = $iris->pages->publish($pageData['id']);
    echo "Page published!\n\n";
    echo "View at: http://localhost:7200/p/5slow\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";

    if (method_exists($e, 'getResponse')) {
        echo "Response: " . $e->getResponse()->getBody() . "\n";
    }

    exit(1);
}
