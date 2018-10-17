<?php
namespace MigrateMe\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GenerateCommand extends Command
{
    protected static $defaultName = 'generate';

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mysqli = new \mysqli('localhost', 'root', 'root', 'sites_example');

        $queries = [];
        $start   = date('Y-m-d H:i:s');

        $mysqli->query("SET GLOBAL log_output = 'TABLE';");
        $mysqli->query("SET GLOBAL general_log = 'ON';");

        $output->writeln("Waiting for database changes ..");

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Press s(top) to stop logging and create the migration file: ', false, '/^s(top)?/i');

        while ($helper->ask($input, $output, $question) !== true) {
            // do nothing
        }

        $result = $mysqli->query("SELECT * FROM mysql.general_log WHERE event_time >= '$start';");

        $database = '';

        while ($query = $result->fetch_object())
        {
            switch ($query->command_type )
            {
                case 'Init DB':
                    $database = $query->argument;
                    break;
                case 'Query':
                default:
                    //preg_match('/^[a-z]+\[([a-z]+)\] @ .+$/mi', $query->user_host, $matches);
                    //if ($matches[1] == 'sites_example') {
                    if ($database == 'sites_example' && preg_match('/^(INSERT|UPDATE|CREATE|DELETE) /mi', $query->argument)) {
                        $queries[] = $query->argument;
                    }
                    // }
                    break;
            }
        }

        $mysqli->query("SET GLOBAL log_output = 'FILE';");
        $mysqli->query("SET GLOBAL general_log = 'OFF';");

        $now = date('Y-m-d H:i:s');
        $mysqli->query("DELETE FROM mysql.general_log WHERE event_time >= '$start' AND event_time <= '$now';");

        if (count($queries))
        {
            $template = file_get_contents(MIGRATEME_PATH . '/files/phinx.tpl');
            $string   = implode(str_pad('', 2, PHP_EOL), $queries);

            file_put_contents(MIGRATEME_PATH.'/output.php', str_replace('{QUERIES}', $string, $template));
        }

        echo sprintf("Caught %d queries\n", count($queries));
    }
}