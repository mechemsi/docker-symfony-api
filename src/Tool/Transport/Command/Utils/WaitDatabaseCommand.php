<?php

declare(strict_types=1);

namespace App\Tool\Transport\Command\Utils;

use App\General\Transport\Command\Traits\SymfonyStyleTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @package App\Tool
 */
#[AsCommand(
    name: 'db:wait',
    description: 'Waits for database availability.',
)]
class WaitDatabaseCommand extends Command
{
    use SymfonyStyleTrait;

    /**
     * Wait sleep time for db connection in seconds
     */
    private const int WAIT_SLEEP_TIME = 2;

    /**
     * @throws LogicException
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        for ($i = 0; $i < 60; $i += self::WAIT_SLEEP_TIME) {
            try {
                $connection = $this->em->getConnection();
                $statement = $connection->prepare('SHOW TABLES');
                $statement->executeQuery();
                $io->success('Connection to the database is ok!');

                return 0;
            } catch (Throwable) {
                $io->comment('Trying to connect to the database seconds:' . $i);
                sleep(self::WAIT_SLEEP_TIME);

                continue;
            }
        }

        $io->error('Can not connect to the database');

        return 1;
    }
}
