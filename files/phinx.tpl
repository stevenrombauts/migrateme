<?php

use Phinx\Migration\AbstractMigration;

class InstallPlgSystem404 extends AbstractMigration
{
    public function up()
    {
        $sql = <<<EOL
{MIGRATION}
EOL;

        $this->execute($sql);
    }

    public function down()
    {
        $sql = <<<EOL
{ROLLBACK}
EOL;

        $this->execute($sql);
    }
}