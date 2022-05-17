<?php

declare(strict_types=1);

namespace Spiral\Filters;

use Spiral\Core\Container;
use Spiral\Core\CoreInterface;
use Spiral\Models\SchematicEntity;

/**
 * @internal
 */
final class FilterProvider implements FilterProviderInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly CoreInterface $core
    ) {
    }

    public function createFilter(string $name, InputInterface $input): FilterInterface
    {
        $attributeMapper = $this->container->get(Schema\AttributeMapper::class);

        $filter = $this->createFilterInstance($name);
        [$mappingSchema, $errors] = $attributeMapper->map($filter, $input);

        if ($filter instanceof HasFilterDefinition) {
            $mappingSchema = \array_merge(
                $mappingSchema,
                $filter->filterDefinition()->mappingSchema()
            );
        }

        $inputMapper = $this->container->get(Schema\InputMapper::class);
        $schemaBuilder = $this->container->get(Schema\Builder::class);

        $schema = $schemaBuilder->makeSchema($name, $mappingSchema);

        [$data, $inputErrors] = $inputMapper->map($schema, $input);
        $errors = \array_merge($errors, $inputErrors);

        $entity = new SchematicEntity($data, $schema);
        return $this->core->callAction($name, 'handle', [
            'filterBag' => new FilterBag($filter, $entity, $schema, $errors),
        ]);
    }

    private function createFilterInstance(string $name): FilterInterface
    {
        $class = new \ReflectionClass($name);

        $args = [];
        if ($constructor = $class->getConstructor()) {
            $args = $this->container->resolveArguments($constructor);
        }

        return $class->newInstanceArgs($args);
    }
}
