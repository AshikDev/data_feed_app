<?php

namespace App\Command;

use App\Entity\CatalogItem;
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
    private static array $propertyMap = [
        'entity_id' => ['setter' => 'setEntityId', 'type' => 'int'],
        'CategoryName' => ['setter' => 'setCategoryName', 'type' => 'string'],
        'sku' => ['setter' => 'setSku', 'type' => 'string'],
        'name' => ['setter' => 'setName', 'type' => 'string'],
        'description' => ['setter' => 'setDescription', 'type' => 'string'],
        'shortdesc' => ['setter' => 'setShortDesc', 'type' => 'string'],
        'price' => ['setter' => 'setPrice', 'type' => 'string'],
        'link' => ['setter' => 'setLink', 'type' => 'string'],
        'image' => ['setter' => 'setImage', 'type' => 'string'],
        'Brand' => ['setter' => 'setBrand', 'type' => 'string'],
        'Rating' => ['setter' => 'setRating', 'type' => 'int'],
        'CaffeineType' => ['setter' => 'setCaffeineType', 'type' => 'string'],
        'Count' => ['setter' => 'setCount', 'type' => 'int'],
        'Flavored' => ['setter' => 'setFlavored', 'type' => 'string'],
        'Seasonal' => ['setter' => 'setSeasonal', 'type' => 'string'],
        'Instock' => ['setter' => 'setInStock', 'type' => 'string'],
        'Facebook' => ['setter' => 'setFacebook', 'type' => 'string'],
        'IsKCup' => ['setter' => 'setIsKCup', 'type' => 'int']
    ];
    private static array $requiredFields = [
        'entity_id' => 'Entity ID',
        'CategoryName' => 'Category name',
        'sku' => 'Sku name',
        'name' => 'Name',
        'price' => 'Price'
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct(self::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Process feed.xml file and pushes data to a database.')
            ->addArgument('filepath', InputArgument::REQUIRED, 'The path to the XML file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $io->info('Processing XML file');
        $filepath = $input->getArgument('filepath');
        if (!$this->isValidFile($filepath)) {
            return Command::FAILURE;
        }

        $io->info('Reading XML Data');
        $elements = $this->getElements($filepath);

        $io->info('Feeding XML Data');
        if (!$this->feedElements($elements)) {
            return Command::FAILURE;
        }

        $io->success('Done');
        return Command::SUCCESS;
    }

    private function isValidFile(string $filepath): bool
    {
        // validate file path
        if (!file_exists($filepath)) {
            $this->logger->error('File not found at ' . $filepath);
            return false;
        }

        // validate file extension
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'xml') {
            $this->logger->error('Extension is not Valid. Extension should be xml, given ' . $ext);
            return false;
        }

        return true;
    }

    private function getElements(string $filepath): ?SimpleXMLElement
    {
        try {
            // get XML data
            $xmlContent = file_get_contents($filepath);
            $xml = new SimpleXMLElement($xmlContent);
        } catch (Exception $e) {
            $this->logger->error("Failed to parse XML: " . $e->getMessage());
            return null;
        }

        return $xml->children();
    }

    private function feedElements(?SimpleXMLElement $elements): bool
    {
        if ($elements === null) {
            $this->logger->error("The XML file is not accessible.");
            return false;
        }

        // process XML data
        $catalogItemRepository = $this->entityManager->getRepository(CatalogItem::class);
        foreach ($elements as $element) {
            if (!$this->validateElements($element)) {
                continue;
            }

            try {
                $catalogItem = $catalogItemRepository
                    ->findOneBy(['entity_id' => $element->entity_id]) ?: new CatalogItem();
                $catalogItemEntity = $this->setPropertiesByXMLData($catalogItem, $element);
                $this->entityManager->persist($catalogItemEntity);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        // flush all the data to the catalog_item table
        $this->entityManager->flush();
        return true;
    }

    private function validateElements(SimpleXMLElement $element): bool
    {
        try {
            // validate XML data based on defined requiredFields array
            foreach (self::$requiredFields as $field => $fieldName) {
                if (!isset($element->$field) || empty(strval($element->$field))) {
                    $this->logger->error("Entity Id: " . intval($element->entity_id) . ". $fieldName is required.");
                    return false;
                }
            }
        } catch (Exception $e) {
            $this->logger->error("Failed to parse XML: " . $e->getMessage());
            return false;
        }

        return true;
    }

    private function setPropertiesByXMLData(CatalogItem $catalogItem, SimpleXMLElement $element): CatalogItem
    {
        // set properties based on map
        foreach (self::$propertyMap as $xmlField => $info) {
            if (isset($element->$xmlField)) {
                $value = $element->$xmlField;
                $value = match ($info['type']) {
                    'int' => intval($value),
                    'decimal' => number_format((float)$value, 2, '.', ''),
                    default => strval($value)
                };
                $catalogItem->{$info['setter']}($value);
            }
        }

        return $catalogItem;
    }
}
