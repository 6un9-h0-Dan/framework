<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Session;

use Spiral\Session\Exception\SessionException;

/**
 * Singular session section (session data isolator).
 */
interface SessionSectionInterface extends \IteratorAggregate, \ArrayAccess
{
    /**
     * Section name.
     */
    public function getName(): string;

    /**
     * All section data in a form of array.
     */
    public function getAll(): array;

    /**
     * Set data in session.
     *
     * @param mixed  $value
     * @return mixed
     * @throws SessionException
     */
    public function set(string $name, $value);

    /**
     * Check if value presented in session.
     *
     * @return bool
     * @throws SessionException
     */
    public function has(string $name);

    /**
     * Get value stored in session.
     *
     * @param mixed  $default
     * @return mixed
     * @throws SessionException
     */
    public function get(string $name, $default = null);

    /**
     * Read item from session and delete it after.
     *
     * @param mixed  $default Default value when no such item exists.
     * @return mixed
     * @throws SessionException
     */
    public function pull(string $name, $default = null);

    /**
     * Delete data from session.
     *
     *
     * @throws SessionException
     */
    public function delete(string $name);

    /**
     * Clear all session section data.
     */
    public function clear();
}
