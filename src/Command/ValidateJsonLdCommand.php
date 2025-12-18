<?php

namespace Knp\DoctrineBehaviors\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class ValidateJsonLdCommand extends Command
{
    protected static $defaultName = 'json-ld:validate';

    private $httpClient;

    public function __construct(HttpClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?? HttpClient::create();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Validates JSON-LD data on pages found via sitemap.')
            ->addArgument('base-url', InputArgument::REQUIRED, 'The base URL to start crawling (e.g., https://example.com)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $baseUrl = $input->getArgument('base-url');

        // Ensure trailing slash for base URL if checking for robots.txt relative to it
        // But usually robots.txt is at root.
        $robotsUrl = rtrim($baseUrl, '/') . '/robots.txt';

        $io->title("Starting JSON-LD Validation for: $baseUrl");
        $io->text("Fetching robots.txt from: $robotsUrl");

        try {
            $response = $this->httpClient->request('GET', $robotsUrl);
            $content = $response->getContent();
        } catch (\Exception $e) {
            $io->error("Failed to fetch robots.txt: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Find Sitemap URL in robots.txt
        $sitemapUrl = null;
        foreach (explode("\n", $content) as $line) {
            if (stripos($line, 'Sitemap:') === 0) {
                $sitemapUrl = trim(substr($line, 8));
                break;
            }
        }

        if (!$sitemapUrl) {
            // Fallback: Try standard sitemap location
            $sitemapUrl = rtrim($baseUrl, '/') . '/sitemap.xml';
            $io->warning("No Sitemap directive found in robots.txt. Trying default: $sitemapUrl");
        } else {
            $io->text("Found Sitemap URL: $sitemapUrl");
        }

        // Fetch Sitemap
        try {
            $response = $this->httpClient->request('GET', $sitemapUrl);
            $sitemapXml = $response->getContent();
        } catch (\Exception $e) {
            $io->error("Failed to fetch Sitemap: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Parse Sitemap
        $crawler = new Crawler($sitemapXml);

        // Use XPath with local-name() to ignore namespaces (standard sitemaps use http://www.sitemaps.org/schemas/sitemap/0.9)
        $urls = $crawler->filterXPath('//*[local-name()="url"]/*[local-name()="loc"]')->each(function (Crawler $node) {
            return $node->text();
        });

        if (empty($urls)) {
            // Check if it is a sitemap index
            $sitemapUrls = $crawler->filterXPath('//*[local-name()="sitemap"]/*[local-name()="loc"]')->each(function (Crawler $node) {
                return $node->text();
            });

            if (!empty($sitemapUrls)) {
                $io->info("Found Sitemap Index with " . count($sitemapUrls) . " sitemaps. Fetching all...");

                foreach ($sitemapUrls as $subSitemapUrl) {
                    $io->text("Fetching sub-sitemap: $subSitemapUrl");
                    try {
                        $response = $this->httpClient->request('GET', $subSitemapUrl);
                        $subSitemapXml = $response->getContent();
                        $subCrawler = new Crawler($subSitemapXml);

                        $subUrls = $subCrawler->filterXPath('//*[local-name()="url"]/*[local-name()="loc"]')->each(function (Crawler $node) {
                            return $node->text();
                        });

                        $urls = array_merge($urls, $subUrls);
                    } catch (\Exception $e) {
                         $io->error("Failed to fetch sub-sitemap $subSitemapUrl: " . $e->getMessage());
                    }
                }
            }
        }

        $io->section("Validating " . count($urls) . " URLs");

        $validatorServiceUrl = 'http://localhost:3344/validate'; // Default for host-based execution
        // Check if running in docker (basic check, can be configured via env)
        if (getenv('VALIDATOR_URL')) {
            $validatorServiceUrl = getenv('VALIDATOR_URL');
        }

        foreach ($urls as $url) {
            $io->write("Checking $url ... ");

            try {
                $validationResponse = $this->httpClient->request('POST', $validatorServiceUrl, [
                    'json' => ['url' => $url],
                    'timeout' => 30
                ]);

                $result = $validationResponse->toArray(false); // false = don't throw on 3xx/4xx/5xx

                if ($result['status'] === 'OK') {
                    $io->write("<info>OK</info>");
                    $io->newLine();
                } else {
                    $io->write("<error>FAILED</error>");
                    $io->newLine();
                    $io->text(" Reason: " . ($result['reason'] ?? 'Unknown'));
                    if (isset($result['errors'])) {
                         $io->listing($result['errors']);
                    }
                }

            } catch (\Exception $e) {
                $io->write("<error>ERROR</error>");
                $io->newLine();
                $io->text(" Could not contact validator service: " . $e->getMessage());
            }
        }

        $io->success("Validation complete.");

        return Command::SUCCESS;
    }
}
