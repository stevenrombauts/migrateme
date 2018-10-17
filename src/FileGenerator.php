<?php
namespace MigrateMe;

class FileGenerator
{
    public function write(array $migration, array $rollback = [])
    {
        $template = file_get_contents(MIGRATEME_PATH . '/files/phinx.tpl');

        foreach (['MIGRATION' => $migration, 'ROLLBACK' => $rollback] as $type => $queries)
        {
            $string = implode(str_pad('', 2, PHP_EOL), $queries);

            $template = str_replace(sprintf('{%s}', $type), $string, $template);
        }

        file_put_contents(MIGRATEME_PATH.'/output/output.php', $template);
    }
}