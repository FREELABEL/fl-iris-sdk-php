<?php

namespace IRIS\SDK\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use IRIS\SDK\IRIS;
use IRIS\SDK\Config;

/**
 * CLI command for invoking Neuron AI tools.
 *
 * Usage:
 *   ./bin/iris tools                                    # List available tools
 *   ./bin/iris tools recruitment --file=job.pdf         # Generate recruitment queries
 *   ./bin/iris tools recruitment --job-description="..." # From text
 *   ./bin/iris tools candidate-score --data='[...]'     # Score candidates
 */
class ToolsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('tools')
            ->setDescription('Invoke Neuron AI tools (recruitment, candidate scoring, lead enrichment, demand packages, YouTube audio)')
            ->setHelp(<<<'HELP'
Usage:
  tools                                              List available tools
  tools recruitment [options]                        Generate recruitment queries
  tools candidate-score [options]                    Score candidates against requirements
  tools lead-enrich [options]                        Enrich a lead with contact info
  tools demand-package [options]                     Generate legal demand packages
  tools youtube-audio [options]                      Download YouTube audio as MP3

Examples:
  ./bin/iris tools
  ./bin/iris tools recruitment --file=/path/to/job.pdf --platform=linkedin
  ./bin/iris tools recruitment --job-description="Senior Engineer..." --location="Austin, TX"
  ./bin/iris tools candidate-score --data='[{"name":"Jane",...}]' --requirements='{"must_have_skills":[...]}'
  ./bin/iris tools lead-enrich --lead-id=510 --goal=email
  ./bin/iris tools demand-package --case-id="Richard Ramos" --ai-model=gpt-5-nano
  ./bin/iris tools youtube-audio --url="https://www.youtube.com/watch?v=abc123" --agent-id=11
HELP
            )
            ->addArgument('tool', InputArgument::OPTIONAL, 'Tool name: recruitment, candidate-score, lead-enrich, article, demand-package, youtube-audio')
            // Common options
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON')
            ->addOption('api-key', null, InputOption::VALUE_REQUIRED, 'API key (overrides .env)')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'User ID (overrides .env)')
            // Recruitment options
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to PDF/DOCX job description file')
            ->addOption('job-description', 'd', InputOption::VALUE_REQUIRED, 'Job description text')
            ->addOption('platform', 'p', InputOption::VALUE_REQUIRED, 'Target platform: linkedin, github, twitter', 'linkedin')
            ->addOption('location', 'l', InputOption::VALUE_REQUIRED, 'Target location (e.g., "Austin, TX")')
            ->addOption('experience', 'e', InputOption::VALUE_REQUIRED, 'Experience level: entry, mid, senior, lead, executive')
            // Candidate scoring options
            ->addOption('data', null, InputOption::VALUE_REQUIRED, 'Candidate data JSON array')
            ->addOption('requirements', null, InputOption::VALUE_REQUIRED, 'Job requirements JSON object')
            // Lead enrichment options
            ->addOption('lead-id', null, InputOption::VALUE_REQUIRED, 'Lead ID to enrich')
            ->addOption('goal', null, InputOption::VALUE_REQUIRED, 'Enrichment goal: email, phone, website, all')
            // Article generation options
            ->addOption('url', 'u', InputOption::VALUE_REQUIRED, 'YouTube URL or webpage URL')
            ->addOption('topic', 't', InputOption::VALUE_REQUIRED, 'Topic for research-based article')
            ->addOption('source-type', 's', InputOption::VALUE_REQUIRED, 'Source type: video, topic, webpage, rss', 'video')
            ->addOption('length', null, InputOption::VALUE_REQUIRED, 'Article length: short, medium, long', 'medium')
            ->addOption('style', null, InputOption::VALUE_REQUIRED, 'Article style: informative, editorial, newsletter, analysis', 'informative')
            ->addOption('profile-id', null, InputOption::VALUE_REQUIRED, 'Profile ID for publishing')
            ->addOption('publish', null, InputOption::VALUE_NONE, 'Publish to Freelabel')
            ->addOption('no-publish', null, InputOption::VALUE_NONE, 'Do not publish (dry run)')
            // Demand package options
            ->addOption('case-id', 'c', InputOption::VALUE_REQUIRED, 'Case ID or patient name (e.g., "Richard Ramos", "CAS12345")')
            ->addOption('ai-model', 'm', InputOption::VALUE_REQUIRED, 'AI model to use: gpt-4o, gpt-5-nano, claude-3-5-sonnet', 'gpt-5-nano')
            ->addOption('upload-to-gcs', null, InputOption::VALUE_NONE, 'Upload to Google Cloud Storage (default: true)')
            ->addOption('use-cache', null, InputOption::VALUE_NONE, 'Use cached results if available')
            // YouTube audio options
            ->addOption('agent-id', 'a', InputOption::VALUE_REQUIRED, 'Agent ID for YouTube audio download', '11')
            ->addOption('output-filename', 'o', InputOption::VALUE_REQUIRED, 'Custom output filename (without .mp3 extension)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $toolName = $input->getArgument('tool');

        try {
            // Build config from CLI flags
            $configOptions = [];
            if ($apiKey = $input->getOption('api-key')) {
                $configOptions['api_key'] = $apiKey;
            }
            if ($userId = $input->getOption('user-id')) {
                $configOptions['user_id'] = (int) $userId;
            }

            $iris = new IRIS($configOptions);

            // If no tool specified, list available tools
            if (!$toolName) {
                return $this->listTools($iris, $io, $input);
            }

            // Dispatch to specific tool handler
            return match ($toolName) {
                'recruitment', 'recruit' => $this->runRecruitment($iris, $input, $output, $io),
                'candidate-score', 'score' => $this->runCandidateScore($iris, $input, $output, $io),
                'lead-enrich', 'enrich' => $this->runLeadEnrich($iris, $input, $output, $io),
                'article', 'generate-article' => $this->runArticleGeneration($iris, $input, $output, $io),
                'demand-package', 'demand' => $this->runDemandPackage($iris, $input, $output, $io),
                'youtube-audio', 'yt-audio' => $this->runYouTubeAudio($iris, $input, $output, $io),
                default => $this->unknownTool($toolName, $io),
            };
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    private function listTools(IRIS $iris, SymfonyStyle $io, InputInterface $input): int
    {
        $io->title('Available Neuron AI Tools');

        try {
            $tools = $iris->tools->list();

            if ($input->getOption('json')) {
                $io->writeln(json_encode($tools, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return Command::SUCCESS;
            }

            // Display tools in a formatted list
            if (isset($tools['tools']) && is_array($tools['tools'])) {
                foreach ($tools['tools'] as $tool) {
                    $name = $tool['name'] ?? 'unknown';
                    $description = $tool['description'] ?? '';
                    $io->writeln("<fg=cyan>{$name}</>");
                    if ($description) {
                        $io->writeln("  {$description}");
                    }
                    $io->newLine();
                }
            } else {
                // Fallback to hardcoded list if API doesn't return tools
                $io->listing([
                    '<fg=cyan>recruitment</> - Generate search queries from job descriptions',
                    '<fg=cyan>candidate-score</> - Score candidates against requirements',
                    '<fg=cyan>lead-enrich</> - Enrich leads with contact information',
                    '<fg=cyan>article</> - Generate articles from YouTube videos, topics, or webpages',
                    '<fg=cyan>demand-package</> - Generate legal demand packages from case data',
                    '<fg=cyan>youtube-audio</> - Download YouTube audio as MP3 (320kbps)',
                ]);
            }

            $io->section('Quick Examples');
            $io->text([
                './bin/iris tools recruitment --file=job.pdf --platform=linkedin',
                './bin/iris tools recruitment --job-description="Senior Engineer at..." --location="Austin, TX"',
                './bin/iris tools candidate-score --data=\'[...]\' --requirements=\'[...]\'',
                './bin/iris tools lead-enrich --lead-id=510 --goal=email',
                './bin/iris tools article --url="https://www.youtube.com/watch?v=abc123" --length=medium',
                './bin/iris tools article --topic="AI trends 2025" --style=analysis',
                './bin/iris tools demand-package --case-id="Richard Ramos" --ai-model=gpt-5-nano',
                './bin/iris tools youtube-audio --url="https://www.youtube.com/watch?v=R2ZsTB09kb4" --agent-id=11',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            // If API fails, show hardcoded list
            $io->listing([
                '<fg=cyan>recruitment</> - Generate search queries from job descriptions',
                '<fg=cyan>candidate-score</> - Score candidates against requirements',
                '<fg=cyan>lead-enrich</> - Enrich leads with contact information',
                '<fg=cyan>demand-package</> - Generate legal demand packages from case data',
                '<fg=cyan>youtube-audio</> - Download YouTube audio as MP3 (320kbps)',
            ]);
            return Command::SUCCESS;
        }
    }

    private function runRecruitment(IRIS $iris, InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $file = $input->getOption('file');
        $jobDescription = $input->getOption('job-description');
        $platform = $input->getOption('platform') ?: 'linkedin';
        $location = $input->getOption('location');
        $experience = $input->getOption('experience');

        // Validate inputs
        if (!$file && !$jobDescription) {
            $io->error('Please provide either --file or --job-description');
            return Command::FAILURE;
        }

        if ($file && !file_exists($file)) {
            $io->error("File not found: {$file}");
            return Command::FAILURE;
        }

        $io->text('Generating recruitment queries...');
        $io->newLine();

        // Build params
        $params = [
            'platform' => $platform,
        ];

        if ($jobDescription) {
            $params['job_description'] = $jobDescription;
        }
        if ($file) {
            // Read file and send content (SDK will handle via API)
            $params['job_description_file'] = $file;
        }
        if ($location) {
            $params['location'] = $location;
        }
        if ($experience) {
            $params['experience_level'] = $experience;
        }

        $result = $iris->tools->recruitment($params);

        // JSON output
        if ($input->getOption('json')) {
            $output->writeln(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        // Formatted output
        $this->displayRecruitmentResult($result, $output, $io);
        return Command::SUCCESS;
    }

    private function displayRecruitmentResult($result, OutputInterface $output, SymfonyStyle $io): void
    {
        // Title
        if ($result->title) {
            $io->success("Job Title: {$result->title}");
        }

        // Requirements
        if ($result->requirements) {
            $io->section('Extracted Requirements');

            $reqs = $result->requirements;

            if (!empty($reqs['must_have_skills'])) {
                $io->writeln('<fg=green>Must-Have Skills:</>');
                foreach ($reqs['must_have_skills'] as $skill) {
                    $io->writeln("  • {$skill}");
                }
            }

            if (!empty($reqs['nice_to_have_skills'])) {
                $io->newLine();
                $io->writeln('<fg=yellow>Nice-to-Have Skills:</>');
                foreach ($reqs['nice_to_have_skills'] as $skill) {
                    $io->writeln("  • {$skill}");
                }
            }

            if (!empty($reqs['title_keywords'])) {
                $io->newLine();
                $io->writeln('<fg=cyan>Title Keywords:</>');
                $keywords = $reqs['title_keywords'];
                if (is_array($keywords)) {
                    $io->writeln('  ' . implode(', ', $keywords));
                } else {
                    $io->writeln("  {$keywords}");
                }
            }

            if (isset($reqs['experience_years'])) {
                $io->newLine();
                $exp = $reqs['experience_years'];
                if (is_array($exp)) {
                    $min = $exp['min'] ?? null;
                    $max = $exp['max'] ?? null;
                    if ($min && $max) {
                        $io->writeln("Experience: {$min}-{$max} years");
                    } elseif ($min) {
                        $io->writeln("Experience: {$min}+ years");
                    } elseif ($max) {
                        $io->writeln("Experience: Up to {$max} years");
                    }
                } else {
                    $io->writeln("Experience: {$exp} years");
                }
            }
        }

        // Search URLs
        if (!empty($result->searchUrls)) {
            $io->newLine();
            $io->section('Search URLs');
            foreach ($result->searchUrls as $urlData) {
                $label = $urlData['label'] ?? 'Search';
                $url = $urlData['url'] ?? '';
                $io->writeln("<fg=blue>{$label}:</>");
                $io->writeln("  {$url}");
                $io->newLine();
            }
        }

        // Boolean Queries
        if (!empty($result->booleanQueries)) {
            $io->section('Boolean Queries');
            foreach ($result->booleanQueries as $queryData) {
                $label = $queryData['label'] ?? 'Query';
                $query = $queryData['query'] ?? '';
                $io->writeln("<fg=magenta>{$label}:</>");
                $io->writeln("  {$query}");
                $io->newLine();
            }
        }

        // Extraction Script (truncated)
        if ($result->extractionScript) {
            $io->section('Browser Extraction Script');
            $io->writeln('<fg=gray>Copy this JavaScript into browser console on LinkedIn search results:</>');
            $io->newLine();
            $script = $result->extractionScript;
            if (strlen($script) > 500) {
                $io->writeln(substr($script, 0, 500) . '...');
                $io->writeln('<fg=gray>(Script truncated. Use --json for full output)</>');
            } else {
                $io->writeln($script);
            }
        }

        // Instructions
        if ($result->instructions) {
            $io->section('Instructions');
            $io->writeln($result->instructions);
        }
    }

    private function runCandidateScore(IRIS $iris, InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $dataJson = $input->getOption('data');
        $requirementsJson = $input->getOption('requirements');

        if (!$dataJson) {
            $io->error('Please provide --data with candidate JSON array');
            return Command::FAILURE;
        }

        $data = json_decode($dataJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Invalid JSON in --data: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        $params = ['candidate_data' => $dataJson];

        if ($requirementsJson) {
            $requirements = json_decode($requirementsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $io->error('Invalid JSON in --requirements: ' . json_last_error_msg());
                return Command::FAILURE;
            }
            $params['requirements'] = $requirements;
        }

        $io->text('Scoring candidates...');
        $io->newLine();

        $result = $iris->tools->scoreCandidates($params);

        // JSON output
        if ($input->getOption('json')) {
            $output->writeln(json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        // Formatted output
        $this->displayCandidateScoreResult($result, $output, $io);
        return Command::SUCCESS;
    }

    private function displayCandidateScoreResult($result, OutputInterface $output, SymfonyStyle $io): void
    {
        $io->success('Candidate Scoring Complete');

        // Summary
        if ($result->strongMatches) {
            $io->writeln('<fg=green>Strong Matches:</> ' . count($result->strongMatches));
        }
        if ($result->moderateMatches) {
            $io->writeln('<fg=yellow>Moderate Matches:</> ' . count($result->moderateMatches));
        }
        if ($result->weakMatches) {
            $io->writeln('<fg=gray>Weak Matches:</> ' . count($result->weakMatches));
        }

        // Ranked candidates
        if (!empty($result->rankedCandidates)) {
            $io->newLine();
            $io->section('Ranked Candidates');
            foreach ($result->rankedCandidates as $candidate) {
                $rank = $candidate['rank'] ?? '?';
                $name = $candidate['name'] ?? 'Unknown';
                $score = $candidate['overall_score'] ?? 0;
                $io->writeln("<fg=cyan>{$rank}.</> {$name} - <fg=green>{$score}%</>");
            }
        }

        // Report
        if ($result->report) {
            $io->newLine();
            $io->section('Analysis Report');
            $io->writeln($result->report);
        }
    }

    private function runLeadEnrich(IRIS $iris, InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $leadId = $input->getOption('lead-id');
        $goal = $input->getOption('goal') ?: 'email';

        if (!$leadId) {
            $io->error('Please provide --lead-id');
            return Command::FAILURE;
        }

        $io->text("Enriching lead #{$leadId} (goal: {$goal})...");
        $io->newLine();

        $result = $iris->tools->enrichLead((int) $leadId, ['goal' => $goal]);

        // JSON output
        if ($input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        // Formatted output
        if (isset($result['success']) && $result['success']) {
            $io->success('Lead enriched successfully!');
            if (isset($result['data'])) {
                foreach ($result['data'] as $key => $value) {
                    $io->writeln("<fg=cyan>{$key}:</> {$value}");
                }
            }
        } else {
            $io->warning('Enrichment completed with limited results');
            if (isset($result['message'])) {
                $io->writeln($result['message']);
            }
        }

        return Command::SUCCESS;
    }

    private function runArticleGeneration(IRIS $iris, InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $url = $input->getOption('url');
        $topic = $input->getOption('topic');
        $sourceType = $input->getOption('source-type') ?: 'video';
        $length = $input->getOption('length') ?: 'medium';
        $style = $input->getOption('style') ?: 'informative';
        $profileId = $input->getOption('profile-id');
        $publish = $input->getOption('publish');
        $noPublish = $input->getOption('no-publish');

        // Determine source
        $source = $url ?: $topic;
        if (!$source) {
            $io->error('Please provide either --url (for video/webpage) or --topic (for research)');
            return Command::FAILURE;
        }

        // Determine source type based on input if not explicit
        if (!$url && $topic) {
            $sourceType = 'topic';
        }

        // Build params
        $params = [
            'source_type' => $sourceType,
            'source' => $source,
            'article_length' => $length,
            'article_style' => $style,
            'publish_to_fl' => $noPublish ? false : ($publish ? true : true), // Default to publish
            'publish_to_social' => false,
        ];

        if ($profileId) {
            $params['profile_id'] = (int) $profileId;
        }

        $io->title('Article Generation');
        $io->writeln("<fg=cyan>Source Type:</> {$sourceType}");
        $io->writeln("<fg=cyan>Source:</> {$source}");
        $io->writeln("<fg=cyan>Length:</> {$length}");
        $io->writeln("<fg=cyan>Style:</> {$style}");
        $io->writeln("<fg=cyan>Publish:</> " . ($params['publish_to_fl'] ? 'Yes' : 'No (dry run)'));
        $io->newLine();

        $io->text('Dispatching article generation job...');
        $io->newLine();

        try {
            $result = $iris->articles->generate($params);

            // JSON output
            if ($input->getOption('json')) {
                $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return Command::SUCCESS;
            }

            // Success output
            $io->success('Article generation job dispatched!');
            $io->writeln('<fg=yellow>The article is being generated in the background.</>');
            $io->newLine();
            $io->writeln('Job Details:');

            if (isset($result['message'])) {
                $io->writeln("  <fg=green>Message:</> {$result['message']}");
            }
            if (isset($result['queue'])) {
                $io->writeln("  <fg=cyan>Queue:</> {$result['queue']}");
            }
            if (isset($result['source'])) {
                $io->writeln("  <fg=cyan>Source:</> {$result['source']}");
            }

            $io->newLine();
            $io->writeln('<fg=gray>Note: Article generation takes 1-3 minutes. Check your dashboard for the result.</>');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to dispatch article generation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function runDemandPackage(IRIS $iris, InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $caseId = $input->getOption('case-id');
        $aiModel = $input->getOption('ai-model') ?: 'gpt-5-nano';
        $uploadToGcs = !$input->getOption('no-publish'); // Default true unless no-publish
        $useCache = $input->getOption('use-cache');

        // Validate inputs
        if (!$caseId) {
            $io->error('Please provide --case-id (patient name or case number)');
            $io->text('Example: ./bin/iris tools demand-package --case-id="Richard Ramos"');
            return Command::FAILURE;
        }

        $io->section('Generating Legal Demand Package');
        $io->text([
            "Case ID: {$caseId}",
            "AI Model: {$aiModel}",
            "Upload to GCS: " . ($uploadToGcs ? 'Yes' : 'No'),
            "Use Cache: " . ($useCache ? 'Yes' : 'No'),
        ]);
        $io->newLine();
        $io->text('⏳ Generating demand package via ServisAI...');
        $io->newLine();

        $startTime = microtime(true);

        try {
            // Call ServisAI integration's create_demand_package function
            $result = $iris->integrations->execute('servis-ai', 'create_demand_package', [
                'case_id' => $caseId,
                'options' => [
                    'ai_model' => $aiModel,
                    'upload_to_gcs' => $uploadToGcs,
                    'use_cache' => $useCache,
                ],
            ]);

            $elapsedTime = round(microtime(true) - $startTime, 1);

            if ($input->getOption('json')) {
                $io->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return Command::SUCCESS;
            }

            // Format output
            if (isset($result['success']) && $result['success']) {
                $io->success('Demand package generated successfully!');
                
                $io->section('Results');
                $io->definitionList(
                    ['Case ID' => $result['case_id'] ?? 'N/A'],
                    ['Output Type' => $result['output_type'] ?? 'demand_package'],
                    ['AI Model' => $result['ai_model'] ?? $aiModel],
                    ['Execution Time' => $elapsedTime . 's'],
                    ['Total Billing' => '$' . ($result['total_billing'] ?? '0.00')],
                );

                if (isset($result['gcs_url'])) {
                    $io->section('Download');
                    $io->writeln("📄 <href={$result['gcs_url']}>{$result['gcs_url']}</>");
                }

                if (isset($result['components'])) {
                    $io->section('Components Generated');
                    $components = [];
                    if ($result['components']['summary'] ?? false) $components[] = '✓ Case Summary';
                    if ($result['components']['chronology'] ?? false) $components[] = '✓ Medical Chronology';
                    if ($result['components']['patient_details'] ?? false) $components[] = '✓ Patient Details';
                    if ($result['components']['services'] ?? false) $components[] = '✓ Medical Services';
                    $io->listing($components);
                }

                if (isset($result['markdown']) && strlen($result['markdown']) > 0) {
                    $io->section('Preview (First 500 chars)');
                    $io->text(substr($result['markdown'], 0, 500) . '...');
                    $io->text("Full length: " . number_format(strlen($result['markdown'])) . ' characters');
                }

                return Command::SUCCESS;
            } else {
                $io->error('Demand package generation failed');
                if (isset($result['error'])) {
                    $io->text('Error: ' . $result['error']);
                }
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('Failed to generate demand package: ' . $e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    private function runYouTubeAudio(IRIS $iris, InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $youtubeUrl = $input->getOption('url');
        $agentId = $input->getOption('agent-id') ?: 11;
        $outputFilename = $input->getOption('output-filename');

        // Validate inputs
        if (!$youtubeUrl) {
            $io->error('Please provide --url with a YouTube URL');
            $io->text('Example: ./bin/iris tools youtube-audio --url="https://www.youtube.com/watch?v=abc123"');
            return Command::FAILURE;
        }

        if (!preg_match('/youtube\.com|youtu\.be/', $youtubeUrl)) {
            $io->error('Invalid YouTube URL format');
            return Command::FAILURE;
        }

        $io->text("🎵 Downloading YouTube audio...");
        $io->text("URL: {$youtubeUrl}");
        $io->text("Agent ID: {$agentId}");
        $io->newLine();

        try {
            // Build params
            $params = [
                'youtube_url' => $youtubeUrl,
                'upload_to_gcs' => false, // Default to local storage
            ];

            if ($outputFilename) {
                $params['output_filename'] = $outputFilename;
            }

            // Call agent integration
            $result = $iris->agents->callIntegration($agentId, 'copycat-ai', 'download_youtube_audio', $params);

            // JSON output
            if ($input->getOption('json')) {
                $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return Command::SUCCESS;
            }

            // Formatted output
            if (isset($result['result']) && ($result['result']['success'] ?? false)) {
                $data = $result['result'];
                
                $io->success('YouTube audio downloaded successfully!');
                $io->newLine();
                
                $io->definitionList(
                    ['Title' => $data['title'] ?? 'N/A'],
                    ['Download URL' => $data['download_url'] ?? 'N/A'],
                    ['File Name' => $data['file_name'] ?? 'N/A'],
                    ['File Size' => ($data['file_size'] ?? 'N/A') . ' MB'],
                    ['Format' => $data['format'] ?? 'mp3'],
                    ['Quality' => $data['quality'] ?? '320kbps'],
                    ['Storage' => $data['storage_provider'] ?? 'local'],
                );

                $io->newLine();
                $io->text('🎧 You can access your file at:');
                $io->text('  • Web: ' . ($data['download_url'] ?? 'N/A'));
                $io->text('  • Local: fl-api/storage/app/public/' . ($data['file_name'] ?? ''));
                
                return Command::SUCCESS;
            } else {
                $io->error('YouTube audio download failed');
                if (isset($result['result']['error'])) {
                    $io->text('Error: ' . $result['result']['error']);
                }
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('Failed to download YouTube audio: ' . $e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    private function unknownTool(string $toolName, SymfonyStyle $io): int
    {
        $io->error("Unknown tool: {$toolName}");
        $io->text('Available tools: recruitment, candidate-score, lead-enrich, article, demand-package, youtube-audio');
        $io->text('Run "./bin/iris tools" to see all available tools with descriptions.');
        return Command::FAILURE;
    }
}
