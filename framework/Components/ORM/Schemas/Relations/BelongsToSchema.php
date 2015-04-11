<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\ORM\Schemas\Relations;

use Spiral\Components\ORM\Entity;
use Spiral\Components\ORM\Schemas\RelationSchema;

class BelongsToSchema extends RelationSchema
{
    /**
     * Relation type.
     */
    const RELATION_TYPE = Entity::BELONGS_TO;

    /**
     * Equivalent relationship resolved based on definition and not schema, usually polymorphic.
     */
    const EQUIVALENT_RELATION = Entity::BELONGS_TO_MORPHED;

    /**
     * Default definition parameters, will be filled if parameter skipped from definition by user.
     *
     * @var array
     */
    protected $defaultDefinition = array(
        Entity::OUTER_KEY  => '{foreign:primaryKey}',
        Entity::LOCAL_KEY  => '{foreign:roleName}_{definition:FOREIGN_KEY}',
        Entity::CONSTRAINT => true
    );

    /**
     * Create all required relation columns, indexes and constraints.
     */
    public function buildSchema()
    {
        $localSchema = $this->entitySchema->getTableSchema();

        $localKey = $localSchema->column($this->definition[Entity::LOCAL_KEY]);
        $localKey->type($this->outerEntity()->getPrimaryAbstractType());
        $localKey->nullable(true);
        $localKey->index();

        if ($this->definition[Entity::CONSTRAINT])
        {
            $localKey->foreign(
                $this->outerEntity()->getTable(),
                $this->definition[Entity::OUTER_KEY]
            )->onDelete('CASCADE')->onUpdate('CASCADE');
        }
    }
}