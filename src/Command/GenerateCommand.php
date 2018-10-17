<?php
declare(strict_types=1);

namespace MigrateMe\Command;

use MigrateMe\FileGenerator;
use MigrateMe\Logger;
use MigrateMe\MySQLConnection;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class GenerateCommand extends Command
{
    protected static $defaultName = 'generate';

    protected function configure()
    {
        $this
            ->setDescription('Create a new migration file')
            ->addOption(
                'host',
                'a',
                InputOption::VALUE_REQUIRED,
                'MySQL host',
                getenv('MIGRATEME_MYSQL_HOST') ?: 'localhost'
            )
            ->addOption(
                'username',
                'u',
                InputOption::VALUE_REQUIRED,
                'MySQL username',
                getenv('MIGRATEME_MYSQL_USER') ?: 'root'
            )
            ->setHelp(<<<EOL
This command will activate MySQL logging and waits for the user to continue. Once the user decides to continue, a new migration file will be generated.

The command will prompt for the MySQL password. You can set the environment variable MIGRATEME_MYSQL_PASSWORD to set it automatically for non-interactive usage.
EOL
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        if (getenv('MIGRATEME_MYSQL_PASSWORD') === false)
        {
            $question = new Question('MySQL database password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);

            $password = $helper->ask($input, $output, $question);
        }
        else $password = getenv('MIGRATEME_MYSQL_PASSWORD');

        $host     = $input->getOption('host');
        $username = $input->getOption('username');

        $connection = new MySQLConnection();
        $connection->connect($host, $username, $password);

        foreach (['migration', 'rollback'] as $type) {
            $queries[$type] = $this->_collectQueries($type, $connection, $input, $output);
        }

        $generator = new FileGenerator();
        $generator->write($queries['migration'], $queries['rollback']);

        foreach ($queries as $type => $lines) {
            $output->writeln(sprintf("Caught %d %s queries", count($lines), $type));
        }
    }

    protected function _collectQueries(string $type, MySQLConnection $connection, InputInterface $input, OutputInterface $output)
    {
        $logger = new Logger($connection);

        $logger->start();

        $output->writeln(sprintf("Waiting for the %s queries ..", $type));

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Press s(top) to stop logging queries: ', false, '/^s(top)?/i');

        while ($helper->ask($input, $output, $question) !== true) {
            // do nothing
        }

        return $logger->collect();
    }
}