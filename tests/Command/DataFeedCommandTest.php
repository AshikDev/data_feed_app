<?php

namespace App\Tests\Command;

use App\Command\DataFeedCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DataFeedCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        // init kernel and container in order to provide the dependencies
        $kernel = static::bootKernel();
        $container = static::getContainer();

        // load dependencies from the container to pass in the constructor of the application
        $entityManager = $container->get(EntityManagerInterface::class);
        $logger = $container->get(LoggerInterface::class);

        // create the application with the required parameters of the constructors
        $application = new Application($kernel);
        $dataFeedCommand = new DataFeedCommand($entityManager, $logger);
        $application->add($dataFeedCommand);

        // create the command
        $command = $application->find('DataFeed');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecute()
    {
        // execute command with a valid file path
        $filePath = 'public/files/feed.xml';
        $this->commandTester->execute(['filepath' => $filePath]);

        // display and match output
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Done', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteFailure()
    {
        // execute command with an invalid file path
        $invalidFilePath = 'public/files/nonexistent.xml';
        $this->commandTester->execute(['filepath' => $invalidFilePath]);

        // display and match output for failure
        $output = $this->commandTester->getDisplay();
        $this->assertStringNotContainsString('Done', $output);
        $this->assertNotEquals(0, $this->commandTester->getStatusCode());
    }
}
