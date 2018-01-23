<?php

namespace Pim\Bundle\CustomEntityBundle\Reflection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;


/**
 *
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 */
class ClassMetadataRegistry
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getTargetEntityClass($object, $property)
    {
        $className = ClassUtils::getClass($object);

        $classMetadata = $this->em->getClassMetadata($className);
        if ($classMetadata->hasAssociation($property)) {
            $targetEntity = $classMetadata->getAssociationTargetClass($property);

            return $targetEntity;
        }
    }
}
