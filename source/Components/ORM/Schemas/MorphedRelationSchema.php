<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\ORM\Schemas;

use Spiral\Components\ORM\ActiveRecord;
use Spiral\Components\ORM\ORMException;

abstract class MorphedRelationSchema extends RelationSchema
{
    /**
     * Check if relation points to model data from another database. We should not be creating
     * foreign keys in this case.
     *
     * @return bool
     */
    public function isOuterDatabase()
    {
        foreach ($this->getOuterRecords() as $record)
        {
            if ($this->recordSchema->getDatabase() != $record->getDatabase())
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Get morph key name.
     *
     * @return string
     */
    public function getMorphKey()
    {
        return $this->definition[ActiveRecord::MORPH_KEY];
    }

    /**
     * Option string used to populate definition template if no user value provided.
     *
     * @return array
     */
    protected function definitionOptions()
    {
        $options = parent::definitionOptions();

        foreach ($this->schemaBuilder->getRecordSchemas() as $record)
        {
            if ($record->getReflection()->isSubclassOf($this->target))
            {
                //One model will be enough
                $options['outer:primaryKey'] = $record->getPrimaryKey();
                break;
            }
        }

        return $options;
    }

    /**
     * Get all relation target classes.
     *
     * @return RecordSchema[]
     */
    public function getOuterRecords()
    {
        $entities = [];
        foreach ($this->schemaBuilder->getRecordSchemas() as $record)
        {
            if ($record->getReflection()->isSubclassOf($this->target))
            {
                //One model will be enough
                $entities[] = $record;
            }
        }

        return $entities;
    }

    /**
     * Abstract type needed to represent outer key (excluding primary keys).
     *
     * @return null|string
     */
    public function getOuterKeyType()
    {
        $outerKeyType = null;
        foreach ($this->getOuterRecords() as $record)
        {
            if (!$record->getTableSchema()->hasColumn($this->getOuterKey()))
            {
                throw new ORMException(
                    "Morphed relation requires outer key exists in every record ({$record})."
                );
            }

            $recordKeyType = $this->resolveAbstractType(
                $record->getTableSchema()->column($this->getOuterKey())
            );

            if (is_null($outerKeyType))
            {
                $outerKeyType = $recordKeyType;
            }

            //Consistency
            if ($outerKeyType != $recordKeyType)
            {
                throw new ORMException(
                    "Morphed relation requires consistent outer key type ({$record}), "
                    . "expected '{$outerKeyType}' got '{$recordKeyType}''."
                );
            }
        }

        return $outerKeyType;
    }

    /**
     * Normalize relation options.
     *
     * @return array
     */
    protected function normalizeDefinition()
    {
        $definition = parent::normalizeDefinition();

        //Packing targets
        $definition[static::RELATION_TYPE] = [];
        foreach ($this->getOuterRecords() as $record)
        {
            $definition[static::RELATION_TYPE][$record->getRoleName()] = $record->getClass();
        }

        return $definition;
    }
}