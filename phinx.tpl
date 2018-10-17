<?php

use Phinx\Migration\AbstractMigration;

class InstallPlgSystem404 extends AbstractMigration
{
    public function up()
    {
        $sql = <<<EOL
{QUERIES}
EOL;

        $this->execute($sql);
    }

    public function down()
    {

    }
}