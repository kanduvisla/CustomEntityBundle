<?php

namespace Pim\Bundle\CustomEntityBundle\Normalizer\Flat;

use Akeneo\Component\Localization\Model\TranslatableInterface;
use Akeneo\Component\Localization\Model\TranslationInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Pim\Bundle\CustomEntityBundle\Reflection\ClassMetadataRegistry;
use Pim\Bundle\CustomEntityBundle\Reflection\ReflectionClassRegistry;
use Pim\Component\ReferenceData\Model\ReferenceDataInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author    Romain Monceau <romain@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 */
class ReferenceDataNormalizer implements NormalizerInterface
{
    /** @var ReflectionClassRegistry */
    protected $reflectionClassRegistry;

    /** @var ClassMetadataRegistry */
    protected $classMetadataRegistry;

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var NormalizerInterface */
    protected $transNormalizer;

    /** @var array */
    protected $skippedFields = [];

    /**
     * @param ReflectionClassRegistry $reflectionClassRegistry
     * @param ClassMetadataRegistry $classMetadataRegistry
     * @param PropertyAccessorInterface $propertyAccessor
     * @param NormalizerInterface $transNormalizer
     */
    public function __construct(
        ReflectionClassRegistry $reflectionClassRegistry,
        ClassMetadataRegistry $classMetadataRegistry,
        PropertyAccessorInterface $propertyAccessor,
        NormalizerInterface $transNormalizer
    ) {
        $this->reflectionClassRegistry = $reflectionClassRegistry;
        $this->classMetadataRegistry = $classMetadataRegistry;
        $this->propertyAccessor = $propertyAccessor;
        $this->transNormalizer = $transNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $csvData = [];

        $properties = $this->reflectionClassRegistry->getReadableProperties($object);
        foreach ($properties as $property) {
            if (in_array($property, $this->skippedFields)) {
                continue;
            }

            $propertyValue = $this->propertyAccessor->getValue($object, $property);

            if (is_object($propertyValue)) {
                $targetEntityClass = $this->classMetadataRegistry->getTargetEntityClass($object, $property);
                $targetReflectionClass = $this->reflectionClassRegistry->get($targetEntityClass);

                if ($propertyValue instanceof Collection) {
                    if ($targetReflectionClass->implementsInterface(ReferenceDataInterface::class)) {
                        $values = [];
                        foreach ($propertyValue as $refData) {
                            $values[] = $refData->getCode();
                        }

                        $csvData[$property] = implode(',', $values);
                    } elseif ($targetReflectionClass->implementsInterface(TranslationInterface::class)) {
                        foreach ($propertyValue as $translation) {
                            // ReflectionClassRegistry -> get properties
                            $transProperties = $this->reflectionClassRegistry->getReadableProperties($translation);
                            $transProperties = array_diff($transProperties, ['id', 'locale', 'foreignKey']);
                            foreach ($transProperties as $transProperty) {
                                $transValue = $this->propertyAccessor->getValue($translation, $transProperty);
                                if (!is_object($transValue) && !is_array($transValue)) {
                                    $csvData[sprintf('%s-%s', $transProperty, $translation->getLocale())] = $transValue;
                                }
                            }
                        }
                    }
                } else { // Many-to-One
                    if ($targetReflectionClass->implementsInterface(ReferenceDataInterface::class)) {
                        $csvData[$property] = $propertyValue->getCode();
                    }
                }
            } else {
                if (!is_array($propertyValue)) {
                    $csvData[$property] = $propertyValue;
                }
            }

        }

        return $csvData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof ReferenceDataInterface && in_array($format, ['csv', 'flat']);
    }

    /**
     * @param array $skippedFields
     */
    public function setSkippedFields(array $skippedFields)
    {
        $this->skippedFields = $skippedFields;
    }
}