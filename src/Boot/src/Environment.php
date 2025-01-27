<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Boot;

final class Environment implements EnvironmentInterface
{
    private const VALUE_MAP = [
        'true'    => true,
        '(true)'  => true,
        'false'   => false,
        '(false)' => false,
        'null'    => null,
        '(null)'  => null,
        'empty'   => '',
    ];

    private ?string $id = null;
    private array $values;
    private bool $overwrite;

    public function __construct(array $values = [], bool $overwrite = true)
    {
        $this->values = $values + $_ENV + $_SERVER;
        $this->overwrite = $overwrite;
    }

    /**
     * @inheritdoc
     */
    public function getID(): string
    {
        if (empty($this->id)) {
            $this->id = md5(serialize($this->values));
        }

        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function set(string $name, $value): void
    {
        if (\array_key_exists($name, $this->values) && !$this->overwrite) {
            return;
        }

        $this->values[$name] = $_ENV[$name] = $value;
        putenv("$name=$value");

        $this->id = null;
    }

    /**
     * @inheritdoc
     */
    public function get(string $name, $default = null)
    {
        if (isset($this->values[$name])) {
            return $this->normalize($this->values[$name]);
        }

        return $default;
    }

    /**
     * Get all environment values.
     */
    public function getAll(): array
    {
        $result = [];

        foreach ($this->values as $key => $value) {
            $result[$key] = $this->normalize($value);
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function normalize($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $alias = strtolower($value);
        if (isset(self::VALUE_MAP[$alias])) {
            return self::VALUE_MAP[$alias];
        }

        return $value;
    }
}
