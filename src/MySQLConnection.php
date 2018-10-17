<?php
namespace MigrateMe;

class MySQLConnection
{
    private $__connection = null;

    public function connect(string $host, string $user, string $password)
    {
        $this->__connection = new \mysqli($host, $user, $password);

        if ($this->__connection->connect_error)
        {
            throw new \RuntimeException(sprintf('MySQL connect error: %s (%d)',  $this->__connection->connect_error, $this->__connection->connect_errno));
        }
    }

    public function query(string $sql)
    {
        return $this->__connection->query($sql);
    }
}