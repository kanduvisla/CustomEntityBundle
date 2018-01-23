<?php

namespace Pim\Bundle\CustomEntityBundle\Reflection;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;


/**
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 */
class ReflectionClassRegistry
{
    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var array */
    protected $classes = [];

    /** @var array */
    protected $properties = [];

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param string $className
     *
     * @return \ReflectionClass
     */
    public function get(string $className)
    {
        if (!isset($this->classes[$className])) {
            $this->classes[$className] = new \ReflectionClass($className);
        }

        return $this->classes[$className];
    }

    /**
     * @param mixed $object
     *
     * @return string[]
     */
    public function getReadableProperties($object)
    {
        $className = ClassUtils::getClass($object);

        $reflectionClass = $this->get($className);
        if (!isset($this->properties[$className])) {
            $properties = $reflectionClass->getProperties();

            $readableProperties = [];
            foreach ($properties as $property) {
                if ($this->propertyAccessor->isReadable($object, $property->getName())) {
                    $readableProperties[] = $property->getName();
                }
            }

            $this->properties[$className] = $readableProperties;
        }

        return $this->properties[$className];
    }
}
