<?php

/**
 * This file is part of Spiral Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Storage\File;

use Psr\Http\Message\UriInterface;
use Spiral\Distribution\UriResolverInterface;

/**
 * @mixin UriResolvableInterface
 */
trait UriResolvableTrait
{
    /**
     * {@see EntryInterface::getPathname()}
     */
    abstract public function getPathname(): string;

    /**
     * {@see UriResolvableInterface::toUri()}
     */
    public function toUri(...$args): UriInterface
    {
        $resolver = $this->getResolver();

        if ($resolver === null) {
            throw new \LogicException('Can not generate public url: File not accessible by HTTP');
        }

        return $this->toUriFrom($resolver, ...$args);
    }

    /**
     * {@see UriResolvableInterface::toUriFrom()}
     */
    public function toUriFrom(UriResolverInterface $resolver, ...$args): UriInterface
    {
        return $resolver->resolve($this->getPathname(), ...$args);
    }

    abstract protected function getResolver(): ?UriResolverInterface;
}
