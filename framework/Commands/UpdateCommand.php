<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Commands;

use Spiral\Components\Console\Command;

class UpdateCommand extends Command
{
    /**
     * Command name.
     *
     * @var string
     */
    protected $name = 'update';

    /**
     * Short command description.
     *
     * @var string
     */
    protected $description = 'Update ORM and ODM schemas and render virtual documentation.';

    /**
     * Updating schemas.
     */
    public function perform()
    {
        $this->writeln("Updating ORM and ODM schemas and virtual documentations...");

        //TODO: ORM
        $this->console->command('odm:update', array(), $this->output);
    }
}