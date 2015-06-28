<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Support\Generators\Config;

use Spiral\Components\Files\FileManager;
use Spiral\Components\Tokenizer\Tokenizer;
use Spiral\Core\Component;
use Spiral\Core\Core;
use Spiral\Core\CoreInterface;

class ConfigWriter extends Component
{
    /**
     * Config values will replace already existed config sections.
     */
    const MERGE_REPLACE = 1;

    /**
     * Already existed config sections and values will replace new values.
     */
    const MERGE_FOLLOW = 2;

    /**
     * Custom merging function will be performed.
     */
    const MERGE_CUSTOM = 3;

    /**
     * Entirely overwrite config data, do not respect any old data.
     */
    const OVERWRITE = 4;

    /**
     * FileManager component.
     *
     * @invisible
     * @var FileManager
     */
    protected $file = null;

    /**
     * Tokenizer component.
     *
     * @invisible
     * @var Tokenizer
     */
    protected $tokenizer = null;

    /**
     * Config data exporter.
     *
     * @var ConfigExporter
     */
    protected $exporter = null;

    /**
     * Config file name, should not include file extension but may have directory included.
     *
     * @var string
     */
    protected $name = '';

    /**
     * How config should be processed compared to already existed one.
     *
     * @var int|string
     */
    protected $method = self::MERGE_FOLLOW;

    /**
     * Content should be written into application config file under defined name.
     *
     * @var array
     */
    protected $content = array();

    /**
     * Config file header should include php tag declaration and may contain doc comment describing
     * config sections. Doc comment will be automatically fetched from application config if it
     * already exists.
     *
     * @var string
     */
    protected $configHeader = "<?php\n";

    /**
     * Config class used to update application configuration files with new sections, data and presets,
     * it can resolve new config data by merging already exists presets with requested setting by one
     * of specified merge methods.
     *
     * @param string      $name        Config filename, should not include extensions, may include
     *                                 directory name.
     * @param int         $method      How system should merge existed and requested config contents.
     * @param Core        $core        Core instance to fetch list of directories.
     * @param FileManager $file        FileManager component.
     * @param Tokenizer   $tokenizer   Tokenizer component.
     */
    public function __construct(
        $name,
        $method = self::MERGE_FOLLOW,
        Core $core,
        FileManager $file,
        Tokenizer $tokenizer
    )
    {
        $this->name = $name;
        $this->method = $method;

        $this->file = $file;
        $this->tokenizer = $tokenizer;

        $this->exporter = new ConfigExporter($core, $file);
    }

    /**
     * Config file name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Read configuration file from some specified directory (application or module config folder).
     *
     * @param string $directory Director where config should be located.
     * @return ConfigWriter
     * @throws ConfigWriterException
     */
    public function readConfig($directory)
    {
        $filename = $this->file->normalizePath(
            $directory . '/' . $this->name . '.' . Core::CONFIGS_EXTENSION
        );

        if (!file_exists($filename))
        {
            throw new ConfigWriterException(
                "Unable to load '{$this->name}' configuration, file not found."
            );
        }

        $this->setConfig(require $filename)->readHeader($filename);

        return $this;
    }

    /**
     * Sett configuration data. Can be readed from existed file using readConfig() method.
     *
     * @param array $content Configuration data.
     * @return static
     */
    public function setConfig($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Parse configuration doc headers from existed file.
     *
     * @param string $filename
     * @return static
     */
    protected function readHeader($filename)
    {
        $this->configHeader = '';
        foreach ($this->tokenizer->fetchTokens($filename) as $token)
        {
            if (isset($token[0]) && $token[0] == T_RETURN)
            {
                //End of header
                break;
            }

            //Generating file header
            $this->configHeader .= $token[Tokenizer::CODE];
        }

        return $this;
    }

    /**
     * Methods will be applied to merge existed and custom configuration data in merge method is
     * specified as Config::MERGE_CUSTOM. This method usually used to perform logical merge.
     *
     * @param mixed $internal Requested configuration data.
     * @param mixed $existed  Existed configuration data.
     * @return mixed
     * @throws ConfigWriterException
     */
    protected function customMerge($internal, $existed)
    {
        throw new ConfigWriterException("No merging function defined.");
    }

    /**
     * Merge requested config data with already existed one. Merge method can be defined during Config
     * class created.
     *
     * @param mixed $internal Requested configuration data.
     * @param mixed $existed  Existed configuration data.
     * @return mixed
     */
    protected function mergeConfig($internal, $existed)
    {
        $result = null;

        switch ($this->method)
        {
            case self::OVERWRITE:
                $result = $internal;
                break;

            case self::MERGE_CUSTOM:
                $result = $this->customMerge($internal, $existed);
                break;

            case self::MERGE_FOLLOW:
                $result = $existed;
                if (is_array($internal) && is_array($existed))
                {
                    $result = array_replace_recursive($existed, $internal, $existed);
                }
                break;

            case self::MERGE_REPLACE:
                $result = $internal;
                if (is_array($internal) && is_array($existed))
                {
                    $result = array_replace_recursive($internal, $existed, $internal);
                }
                break;
        }

        return $result;
    }

    /**
     * Render configuration file content with it's header and data (can be automatically merged with
     * existed config). Config content will be exported using $config->serializeConfig() method, which
     * will format data in more clear way.
     *
     * @param mixed $existed
     * @return string
     */
    protected function renderConfig($existed = null)
    {
        $config = $this->content;
        if ($existed)
        {
            $config = $this->mergeConfig($config, $existed);
        }

        return interpolate("{header}return {config};", array(
            'header' => $this->configHeader,
            'config' => $this->exporter->export($config)
        ));
    }

    /**
     * Write config data to final destination.
     *
     * @param string $directory Destination config directory.
     * @param int    $mode      File mode, use File::RUNTIME for publicly accessible files.
     * @return bool
     */
    public function writeConfig($directory = null, $mode = FileManager::READONLY)
    {
        $directory = $directory ?: directory('config');

        //Destination directory
        $filename = $this->file->normalizePath(
            $directory . '/' . $this->name . '.' . Core::CONFIGS_EXTENSION
        );

        $existed = null;
        if (file_exists($filename))
        {
            $existed = (require $filename);

            //We are going to use existed config header
            $this->readHeader($filename);
        }

        return $this->file->write($filename, $this->renderConfig($existed), $mode, true);
    }
}