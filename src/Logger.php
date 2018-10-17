<?php
namespace MigrateMe;

class Logger
{
    protected $_mysql_connection = null;
    protected $_start            = 0;
    protected $_previous_configuration = [];

    public function __construct(MySQLConnection $connection)
    {
        $this->setMySQLConnection($connection);
    }
    
    public function setMySQLConnection(MySQLConnection $connection)
    {
        $this->_mysql_connection = $connection;
    }

    public function getMySQLConnection()
    {
        return $this->_mysql_connection;
    }

    public function start()
    {
        $this->getMySQLConnection()->query("SET GLOBAL log_output = 'TABLE';");
        $this->getMySQLConnection()->query("SET GLOBAL general_log = 'ON';");

        $this->_start = time();
    }
    
    public function stop()
    {
        $this->getMySQLConnection()->query("SET GLOBAL log_output = 'FILE';");
        $this->getMySQLConnection()->query("SET GLOBAL general_log = 'OFF';");
    }
    
    public function collect()
    {
        $sql = sprintf("SELECT * FROM mysql.general_log WHERE event_time >= '%s';", date('Y-m-d H:i:s', $this->_start));

        $result = $this->getMySQLConnection()->query($sql);

        $database = '';
        $queries  = [];

        while ($query = $result->fetch_object())
        {
            switch ($query->command_type )
            {
                case 'Init DB':
                    $database = $query->argument;
                    break;
                case 'Query':
                default:
                    if ($database == 'sites_example' && preg_match('/^(INSERT|UPDATE|CREATE|DELETE) /mi', $query->argument)) {
                        $queries[] = $query->argument;
                    }
                    break;
            }
        }

        return $queries;
    }
}