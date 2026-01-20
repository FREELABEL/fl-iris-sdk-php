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

    echo "Creating comprehensive showcase page...\n\n";
    
    $page = $iris->pages->create([
        'slug' => 'components-showcase',
        'title' => 'Page Builder Components Showcase',
        'seo_title' => 'All Page Builder Components - Interactive Demo',
        'seo_description' => 'Explore all available page builder components with live examples and styling options',
        'status' => 'draft',
        'owner_type' => 'system',
        'owner_id' => 1,
        'theme' => [
            'mode' => 'dark',
            'branding' => [
                'name' => 'IRIS Page Builder',
                'primaryColor' => '#6366f1',
                'secondaryColor' => '#8b5cf6',
            ],
        ],
        'components' => [
            // 1. Hero Section - Purple Gradient
            [
                'type' => 'Hero',
                'id' => 'hero-main',
                'props' => [
                    'title' => 'Page Builder Components Showcase',
                    'subtitle' => 'Explore all available components with granular styling controls',
                    'backgroundGradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'titleColor' => '#ffffff',
                    'subtitleColor' => 'rgba(255, 255, 255, 0.95)',
                    'textAlign' => 'center',
                    'minHeight' => '600px',
                ],
            ],
            
            // 2. TextBlock - Introduction with Markdown
            [
                'type' => 'TextBlock',
                'id' => 'intro-text',
                'props' => [
                    'content' => "## Welcome to the Component Library\n\nThis page demonstrates **all available components** in the IRIS Page Builder system. Each component is fully customizable with granular styling controls.\n\n### Features:\n\n- 🎨 **Custom Colors** - Any CSS color format supported\n- 📐 **Flexible Layouts** - Control alignment, spacing, and sizing\n- ✨ **Gradient Backgrounds** - Beautiful gradient presets\n- 📝 **Markdown Support** - Rich text formatting\n- 🔧 **Tailwind Overrides** - Direct class customization",
                    'markdown' => true,
                    'textAlign' => 'center',
                    'maxWidth' => '4xl',
                    'themeMode' => 'dark',
                ],
            ],
            
            // 3. ButtonCTA - Primary Action
            [
                'type' => 'ButtonCTA',
                'id' => 'btn-primary',
                'props' => [
                    'text' => 'Get Started Now',
                    'href' => 'https://heyiris.io/signup',
                    'variant' => 'primary',
                    'size' => 'lg',
                ],
            ],
            
            // 4. Hero - Green to Blue Gradient
            [
                'type' => 'Hero',
                'id' => 'hero-gradient-example',
                'props' => [
                    'title' => 'Custom Gradient Backgrounds',
                    'subtitle' => 'Create stunning visual effects with CSS gradients',
                    'backgroundGradient' => 'linear-gradient(135deg, #10b981 0%, #3b82f6 100%)',
                    'titleColor' => '#ffffff',
                    'subtitleColor' => '#f0f9ff',
                    'textAlign' => 'center',
                    'minHeight' => '500px',
                ],
            ],
            
            // 5. TextBlock - Gradient Information
            [
                'type' => 'TextBlock',
                'id' => 'gradient-info',
                'props' => [
                    'content' => "### Gradient Options\n\nHero components support full CSS gradient syntax:\n\n- Linear gradients\n- Radial gradients\n- Multiple color stops\n- Any angle or direction",
                    'markdown' => true,
                    'textAlign' => 'center',
                    'maxWidth' => '3xl',
                    'themeMode' => 'dark',
                ],
            ],
            
            // 6. Hero - Orange to Pink Gradient
            [
                'type' => 'Hero',
                'id' => 'hero-warm-gradient',
                'props' => [
                    'title' => 'Warm Color Palettes',
                    'subtitle' => 'Orange and pink create energy and excitement',
                    'backgroundGradient' => 'linear-gradient(135deg, #f97316 0%, #ec4899 100%)',
                    'titleColor' => '#ffffff',
                    'subtitleColor' => 'rgba(255, 255, 255, 0.9)',
                    'textAlign' => 'left',
                    'minHeight' => '450px',
                ],
            ],
            
            // 7. ButtonCTA - Secondary Style
            [
                'type' => 'ButtonCTA',
                'id' => 'btn-secondary',
                'props' => [
                    'text' => 'Learn More',
                    'href' => 'https://heyiris.io/docs',
                    'variant' => 'secondary',
                    'size' => 'md',
                ],
            ],
            
            // 8. Hero - Dark Theme
            [
                'type' => 'Hero',
                'id' => 'hero-dark',
                'props' => [
                    'title' => 'Dark Theme Support',
                    'subtitle' => 'Built-in dark mode with elegant color schemes',
                    'backgroundGradient' => 'linear-gradient(to right, #1e293b, #334155)',
                    'titleColor' => '#e2e8f0',
                    'subtitleColor' => '#cbd5e1',
                    'textAlign' => 'center',
                    'minHeight' => '500px',
                ],
            ],
            
            // 9. TextBlock - Typography Showcase
            [
                'type' => 'TextBlock',
                'id' => 'typography-showcase',
                'props' => [
                    'content' => "# Typography & Formatting\n\n## Heading Level 2\n\n### Heading Level 3\n\nRegular paragraph text with **bold** and *italic* formatting. You can also include `inline code` and create lists:\n\n1. First ordered item\n2. Second ordered item\n3. Third ordered item\n\nOr unordered lists:\n\n- Bullet point one\n- Bullet point two\n- Bullet point three\n\n> Blockquotes are also supported for highlighting important information.",
                    'markdown' => true,
                    'textAlign' => 'left',
                    'maxWidth' => '3xl',
                    'themeMode' => 'dark',
                ],
            ],
            
            // 10. Hero - Custom Text Alignment (Right)
            [
                'type' => 'Hero',
                'id' => 'hero-right-align',
                'props' => [
                    'title' => 'Flexible Text Alignment',
                    'subtitle' => 'Components can align content left, center, or right',
                    'backgroundGradient' => 'linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%)',
                    'titleColor' => '#fef3c7',
                    'subtitleColor' => '#fef9e7',
                    'textAlign' => 'right',
                    'minHeight' => '400px',
                ],
            ],
            
            // 11. TextBlock - Features List
            [
                'type' => 'TextBlock',
                'id' => 'features-list',
                'props' => [
                    'content' => "## Component Features\n\n### Hero Component\n- Custom gradient backgrounds\n- Granular text color controls\n- Flexible text alignment\n- Adjustable minimum height\n- Tailwind class overrides\n\n### TextBlock Component\n- Full markdown support\n- Custom max-width settings\n- Theme mode selection\n- Text alignment options\n- Rich typography\n\n### ButtonCTA Component\n- Multiple variants (primary, secondary)\n- Size options (sm, md, lg)\n- Custom href links\n- Accessible markup",
                    'markdown' => true,
                    'textAlign' => 'center',
                    'maxWidth' => '4xl',
                    'themeMode' => 'dark',
                ],
            ],
            
            // 12. ButtonCTA Group
            [
                'type' => 'ButtonCTA',
                'id' => 'btn-docs',
                'props' => [
                    'text' => 'View Documentation',
                    'href' => '/docs/page-builder',
                    'variant' => 'primary',
                    'size' => 'lg',
                ],
            ],
            
            // 13. Hero - Final CTA
            [
                'type' => 'Hero',
                'id' => 'hero-cta',
                'props' => [
                    'title' => 'Start Building Today',
                    'subtitle' => 'Create beautiful landing pages with our composable component system',
                    'backgroundGradient' => 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%)',
                    'titleColor' => '#ffffff',
                    'subtitleColor' => 'rgba(255, 255, 255, 0.95)',
                    'textAlign' => 'center',
                    'minHeight' => '550px',
                ],
            ],
            
            // 14. Final CTA Buttons
            [
                'type' => 'ButtonCTA',
                'id' => 'btn-final-cta',
                'props' => [
                    'text' => 'Create Your Page',
                    'href' => '/pages/create',
                    'variant' => 'primary',
                    'size' => 'lg',
                ],
            ],
        ],
    ]);

    $pageData = $page['data'] ?? $page;
    
    echo "✅ Showcase page created successfully!\n\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📄 Page Details:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "   ID:         {$pageData['id']}\n";
    echo "   Slug:       {$pageData['slug']}\n";
    echo "   Title:      {$pageData['title']}\n";
    echo "   Status:     {$pageData['status']}\n";
    echo "   Components: " . count($pageData['json_content']['components'] ?? []) . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "📦 Components Included:\n";
    echo "   • 6 Hero sections (various gradients & alignments)\n";
    echo "   • 5 TextBlock sections (markdown, typography)\n";
    echo "   • 3 ButtonCTA components (different variants)\n\n";
    
    echo "🚀 Next Steps:\n";
    echo "   1. Publish: curl -X POST \"http://localhost:8000/api/v1/pages/{$pageData['id']}/publish\" \\\n";
    echo "               -H \"Authorization: Bearer {$apiKey}\"\n\n";
    echo "   2. View:    http://localhost:7200/p/{$pageData['slug']}\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
