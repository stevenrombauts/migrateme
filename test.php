<?php
$mysqli = new mysqli('localhost', 'root', 'root', 'sites_example');

$queries = [];
$start   = date('Y-m-d H:i:s');

$result = $mysqli->query("SET GLOBAL log_output = 'TABLE';");
$result = $mysqli->query("SET GLOBAL general_log = 'ON';");

echo "Enabled logging, press s to quit ..";

while ($character = fread(STDIN, 1))
{
    if ($character == 's')
    {
        echo "Quitting ..\n";
        break;
    }
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

$result = $mysqli->query("SET GLOBAL log_output = 'FILE';");
$result = $mysqli->query("SET GLOBAL general_log = 'OFF';");

$now = date('Y-m-d H:i:s');
$result = $mysqli->query("DELETE FROM mysql.general_log WHERE event_time >= '$start' AND event_time <= '$now';");

if (count($queries))
{
    $template = file_get_contents('phinx.tpl');
    $string   = implode(str_pad('', 2, PHP_EOL), $queries);

    file_put_contents('output.php', str_replace('{QUERIES}', $string, $template));
}

echo sprintf("Caught %d queries\n", count($queries));

