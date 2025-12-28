#!/usr/bin/env php
<?php
/**
 * Test Agent attachKnowledge() Method - RAG Bug Fix Verification
 *
 * This script tests the new attachKnowledge() method that properly enables
 * RAG for agents by:
 * 1. Indexing content to vector store with agent_id
 * 2. Updating agent's file_attachments field
 *
 * Usage: php test-agent-attach-knowledge.php [agent_id]
 */

require __DIR__ . '/vendor/autoload.php';

use IRIS\SDK\IRIS;
use IRIS\SDK\Auth\CredentialStore;
use IRIS\SDK\Resources\Agents\AgentConfig;

// Configuration
$agentId = $argv[1] ?? null;
$createNewAgent = !$agentId;

echo "=== Agent RAG attachKnowledge() Test ===\n\n";

// 1. Initialize SDK
$store = new CredentialStore();
$config = $store->toConfigArray();
$iris = new IRIS($config);

echo "Environment: " . (getenv('IRIS_ENV') ?: 'production') . "\n";
echo "User ID: {$config['user_id']}\n\n";

// 2. Create test agent if needed
if ($createNewAgent) {
    echo "--- Creating Test Agent ---\n";
    try {
        $config = new AgentConfig(
            name: 'RAG Test Agent',
            prompt: 'You are a helpful assistant with access to specific knowledge documents.',
            model: 'gpt-4o-mini',
            type: 'assistant'
        );
        $agent = $iris->agents->create($config);
        $agentId = $agent->id;
        echo "✅ Created agent #{$agentId}: {$agent->name}\n\n";
    } catch (Exception $e) {
        echo "❌ Failed to create agent: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "Using existing agent #{$agentId}\n\n";
}

// 3. Get initial agent state
echo "--- Initial Agent State ---\n";
try {
    $agent = $iris->agents->get($agentId);
    echo "Name: {$agent->name}\n";
    echo "Bloq ID: " . ($agent->bloqId ?? 'null') . "\n";
    echo "File Attachments: " . count($agent->fileAttachments) . "\n";
    foreach ($agent->fileAttachments as $idx => $att) {
        $title = $att['title'] ?? $att['name'] ?? 'Untitled';
        $type = $att['type'] ?? 'unknown';
        $vectorCount = isset($att['vector_ids']) ? count($att['vector_ids']) : 0;
        echo "  [{$idx}] {$title} ({$type}) - {$vectorCount} vectors\n";
    }
} catch (Exception $e) {
    echo "❌ Error getting agent: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Prepare test knowledge content
echo "\n--- Preparing Test Knowledge ---\n";

$medicalInfo = <<<MEDICAL
# Dorothy's Medical Information

## Current Medications

### Morning (8:00 AM)
- **Lisinopril 10mg** - Blood pressure medication
  - Take with food
  - Monitor for dizziness
- **Metformin 500mg** - Diabetes management
  - Take with breakfast
  - May cause stomach upset initially
- **Aspirin 81mg** - Heart health (baby aspirin)
  - Daily low-dose for cardiovascular protection

### Afternoon (2:00 PM)
- **Calcium + Vitamin D** - Bone health
  - Take with lunch for better absorption

### Evening (8:00 PM)
- **Atorvastatin 20mg** - Cholesterol management
  - Take before bed
  - Avoid grapefruit juice

## Allergies
- **Penicillin** - Severe reaction (hives, difficulty breathing)
- **Latex** - Mild skin irritation

## Medical Conditions
- Type 2 Diabetes (diagnosed 2015)
- Hypertension (controlled with medication)
- Osteoarthritis (primarily knees and hands)
- High cholesterol

## Healthcare Providers
- **Primary Care**: Dr. Sarah Chen, (555) 123-4567
- **Cardiologist**: Dr. Michael Rodriguez, (555) 234-5678
- **Endocrinologist**: Dr. Lisa Park, (555) 345-6789

## Emergency Contacts
1. Daughter Sarah: (555) 111-2222
2. Son Michael: (555) 333-4444
MEDICAL;

$familyInfo = <<<FAMILY
# Dorothy's Family Information

## Children

### Sarah Thompson (Daughter)
- **Age**: 52
- **Phone**: (555) 111-2222
- **Email**: sarah.thompson@email.com
- **Address**: 456 Oak Street, Springfield
- **Relationship**: Very close, calls daily
- **Birthday**: March 15th

### Michael Wilson (Son)  
- **Age**: 48
- **Phone**: (555) 333-4444
- **Email**: mike.wilson@email.com
- **Address**: 789 Pine Avenue, Riverside
- **Relationship**: Close, visits weekly
- **Birthday**: July 22nd

## Grandchildren

### Emma (Sarah's daughter)
- **Age**: 24
- **College**: State University
- **Interest**: Medical school
- **Birthday**: December 3rd

### Jack (Sarah's son)
- **Age**: 21
- **College**: Tech Institute
- **Interest**: Computer science
- **Birthday**: August 10th

### Olivia (Michael's daughter)
- **Age**: 18
- **High School**: Senior year
- **Interest**: Art and design
- **Birthday**: May 28th

## Important Dates & Traditions
- **Family dinner**: Every Sunday at 5:00 PM
- **Holiday gatherings**: Always at Dorothy's house
- **Grandma's birthday**: November 12th (big celebration planned)
FAMILY;

echo "📄 Medical Information: " . strlen($medicalInfo) . " bytes\n";
echo "📄 Family Information: " . strlen($familyInfo) . " bytes\n";

// 5. Attach knowledge using new method
echo "\n--- Attaching Knowledge (Medical Info) ---\n";
try {
    $result1 = $iris->agents->attachKnowledge($agentId, $medicalInfo, [
        'title' => 'Medical Information',
        'type' => 'medical_record',
        'description' => 'Dorothy\'s medications, allergies, and healthcare providers',
    ]);
    
    echo "✅ Attached medical information\n";
    echo "   Vector ID: {$result1['vector_id']}\n";
    echo "   Agent attachments: " . count($result1['agent']->fileAttachments) . "\n";
} catch (Exception $e) {
    echo "❌ Failed to attach medical info: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n--- Attaching Knowledge (Family Info) ---\n";
try {
    $result2 = $iris->agents->attachKnowledge($agentId, $familyInfo, [
        'title' => 'Family Information',
        'type' => 'family_contact',
        'description' => 'Dorothy\'s children, grandchildren, and important dates',
    ]);
    
    echo "✅ Attached family information\n";
    echo "   Vector ID: {$result2['vector_id']}\n";
    echo "   Agent attachments: " . count($result2['agent']->fileAttachments) . "\n";
} catch (Exception $e) {
    echo "❌ Failed to attach family info: " . $e->getMessage() . "\n";
    exit(1);
}

// 6. Verify agent was updated
echo "\n--- Updated Agent State ---\n";
try {
    $agent = $iris->agents->get($agentId);
    echo "Name: {$agent->name}\n";
    echo "Bloq ID: " . ($agent->bloqId ?? 'null') . "\n";
    echo "File Attachments: " . count($agent->fileAttachments) . "\n";
    foreach ($agent->fileAttachments as $idx => $att) {
        $title = $att['title'] ?? $att['name'] ?? 'Untitled';
        $type = $att['type'] ?? 'unknown';
        $vectorCount = isset($att['vector_ids']) ? count($att['vector_ids']) : 0;
        echo "  [{$idx}] {$title} ({$type}) - {$vectorCount} vectors\n";
    }
} catch (Exception $e) {
    echo "❌ Error getting updated agent: " . $e->getMessage() . "\n";
    exit(1);
}

// 7. Test RAG with specific questions
echo "\n=== RAG Verification Tests ===\n";

$testQuestions = [
    [
        'question' => 'What medications does Dorothy take in the morning?',
        'expected_terms' => ['Lisinopril', 'Metformin', 'Aspirin', '8:00', 'morning'],
        'category' => 'Medical - Medications',
    ],
    [
        'question' => 'What is Dorothy allergic to?',
        'expected_terms' => ['Penicillin', 'Latex', 'allergic'],
        'category' => 'Medical - Allergies',
    ],
    [
        'question' => 'Who is Dorothy\'s daughter and what is her phone number?',
        'expected_terms' => ['Sarah', 'Thompson', '555', '111-2222'],
        'category' => 'Family - Contact',
    ],
    [
        'question' => 'When is Emma\'s birthday?',
        'expected_terms' => ['December', '3', 'Emma'],
        'category' => 'Family - Birthdays',
    ],
];

$passedTests = 0;
$totalTests = count($testQuestions);

foreach ($testQuestions as $idx => $test) {
    echo "\n--- Test " . ($idx + 1) . "/{$totalTests}: {$test['category']} ---\n";
    echo "Q: {$test['question']}\n";
    
    try {
        $response = $iris->chat->execute([
            'agentId' => $agentId,
            'query' => $test['question'],
        ]);
        
        // Extract response content
        $content = is_array($response) ? ($response['summary'] ?? '') : ($response->summary ?? $response->content ?? '');
        
        echo "A: " . substr($content, 0, 200) . (strlen($content) > 200 ? '...' : '') . "\n";
        
        // Check for expected terms
        $foundTerms = [];
        foreach ($test['expected_terms'] as $term) {
            if (stripos($content, $term) !== false) {
                $foundTerms[] = $term;
            }
        }
        
        $foundCount = count($foundTerms);
        $expectedCount = count($test['expected_terms']);
        $passRate = $expectedCount > 0 ? ($foundCount / $expectedCount) * 100 : 0;
        
        if ($passRate >= 60) {
            echo "✅ PASS ({$foundCount}/{$expectedCount} terms found: " . implode(', ', $foundTerms) . ")\n";
            $passedTests++;
        } else {
            echo "❌ FAIL ({$foundCount}/{$expectedCount} terms found)\n";
            echo "   Expected: " . implode(', ', $test['expected_terms']) . "\n";
            echo "   Found: " . ($foundTerms ? implode(', ', $foundTerms) : 'none') . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    // Small delay between requests
    sleep(1);
}

// 8. Summary
echo "\n=== Test Summary ===\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100) . "%\n";

if ($passedTests >= ($totalTests * 0.75)) {
    echo "\n✅ RAG IS WORKING! The attachKnowledge() method successfully enabled RAG.\n";
} else {
    echo "\n⚠️  RAG may need more investigation. Check the API logs.\n";
}

echo "\nAgent ID: {$agentId}\n";
echo "Vector IDs: {$result1['vector_id']}, {$result2['vector_id']}\n";

echo "\n=== Test Complete ===\n";
