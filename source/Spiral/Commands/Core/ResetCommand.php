<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Commands\Core;

use Spiral\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Remove every file in cache directory or emulate removal.
 */
class ResetCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'core:reset';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Reset application runtime cache and invalidate configs.';

    /**
     * {@inheritdoc}
     */
    protected $options = [
        ['emulate', 'e', InputOption::VALUE_NONE, 'Only emulate removal.']
    ];

    /**
     * Perform command.
     */
    public function perform()
    {
        $this->isVerbose() && $this->writeln("<info>Clearing application runtime cache:</info>");

        foreach ($this->files->getFiles(directory('cache')) as $filename) {
            !$this->option('emulate') && $this->files->delete($filename);

            $this->isVerbose() && $this->writeln($this->files->relativePath(
                $filename, directory('cache')
            ));
        }

        $this->writeln("<info>Runtime cache has been cleared.</info>");
    }
}