<?php

namespace App\Command;

use App\Entity\CatalogItem;
use App\Service\DataFeedConfiguration;
use Exception;
use SimpleXMLElement;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'DataFeed'
)]
class DataFeedCommand extends Command
{
    protected static $defaultName = 'app:data-feed';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct(self::$defaultName);
    }

    /**
     * Configures the command with a description and required argument
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Process feed.xml file and pushes data to a database.')
            ->addArgument('filepath', InputArgument::REQUIRED, 'The path to the XML file.');
    }

    /**
     * Executes the command to process an XML file and push data to the database
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $io->info('Processing XML file');
        $filepath = $input->getArgument('filepath');
        if (!$this->validateXmlFile($filepath)) {
            return Command::FAILURE;
        }

        $io->info('Reading XML Data');
        $elements = $this->extractXMLElements($filepath);

        $io->info('Storing XML Data');
        if (($rows = $this->storeXMLData($elements)) < 0) {
            $io->error('No data is saved');
            return Command::FAILURE;
        }

        $io->info($rows . ' rows are saved');
        $io->success('Done');
        return Command::SUCCESS;
    }

    /**
     * Validates the given file path and extension for XML processing
     */
    private function validateXmlFile(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            $this->logger->error('File not found at ' . $filepath);
            return false;
        }

        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'xml') {
            $this->logger->error('Extension is not Valid. Extension should be xml, given ' . $ext);
            return false;
        }

        return true;
    }

    /**
     * Responsible for extracting/fetching the data from the XML file
     */
    private function extractXMLElements(string $filepath): ?SimpleXMLElement
    {
        try {
            $xmlContent = file_get_contents($filepath);
            $xml = new SimpleXMLElement($xmlContent);
        } catch (Exception $e) {
            $this->logger->error("Failed to parse XML: " . $e->getMessage());
            return null;
        }

        return $xml->children();
    }

    /**
     * Processes a collection of XML elements, validates, and store them to the database
     */
    private function storeXMLData(?SimpleXMLElement $elements): int
    {
        if ($elements === null) {
            $this->logger->error("The XML file is not accessible.");
            return 0;
        }

        $batchSize = 100;
        $processedCount = 0;
        $catalogItemRepository = $this->entityManager->getRepository(CatalogItem::class);
        foreach ($elements as $element) {
            if (!$this->validateXMLDataFields($element)) {
                continue;
            }

            try {
                $catalogItem = $catalogItemRepository
                    ->findOneBy(['entity_id' => $element->entity_id]) ?: new CatalogItem();
                $catalogItemEntity = $this->assignPropertiesFromXML($catalogItem, $element);
                $this->entityManager->persist($catalogItemEntity);

                // batch size adjusted for better performance with large data; this code block is optional
                if (++$processedCount % $batchSize === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
        return $processedCount;
    }

    /**
     * Validates XML data using the specified requiredFields array in DataFeedConfiguration
     */
    private function validateXMLDataFields(SimpleXMLElement $element): bool
    {
        try {
            foreach (DataFeedConfiguration::$requiredFields as $field => $fieldName) {
                if (!isset($element->$field) || empty(strval($element->$field))) {
                    $this->logger->error(sprintf(
                        "Entity Id: %d. %s is required.",
                        intval($element->entity_id),
                        $fieldName
                    ));
                    return false;
                }
            }
        } catch (Exception $e) {
            $this->logger->error("Failed to parse XML: " . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Assigns properties according to the map specified in DataFeedConfiguration
     */
    private function assignPropertiesFromXML(CatalogItem $catalogItem, SimpleXMLElement $element): CatalogItem
    {
        foreach (DataFeedConfiguration::$xmlDataPropertyMap as $xmlField => $info) {
            if (isset($element->$xmlField)) {
                $value = $element->$xmlField;
                $value = match ($info['type']) {
                    'int' => intval($value) ?? 0,
                    'decimal' => number_format((float)$value, 2, '.', '') ?? 0.00,
                    default => strval($value) ?? ''
                };
                $catalogItem->{$info['setter']}($value);
            }
        }

        return $catalogItem;
    }
}
