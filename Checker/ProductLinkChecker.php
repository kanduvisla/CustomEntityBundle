<?php

namespace Pim\Bundle\CustomEntityBundle\Checker;

use Akeneo\Component\StorageUtils\Exception\InvalidPropertyException;
use Doctrine\ORM\EntityManagerInterface;
use Pim\Bundle\CustomEntityBundle\Repository\AttributeRepository;
use Pim\Component\Catalog\Query\Filter\Operators;
use Pim\Component\Catalog\Query\ProductQueryBuilderFactoryInterface;
use Pim\Component\ReferenceData\Model\ReferenceDataInterface;

/**
 * @author    JM Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductLinkChecker implements ProductLinkCheckerInterface
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var ProductQueryBuilderFactoryInterface */
    protected $productQueryBuilderFactory;

    /** @var AttributeRepository */
    protected $attributeRepository;

    /**
     * @param EntityManagerInterface              $em
     * @param ProductQueryBuilderFactoryInterface $productQueryBuilderFactory
     * @param AttributeRepository                 $attributeRepository
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductQueryBuilderFactoryInterface $productQueryBuilderFactory,
        AttributeRepository $attributeRepository
    ) {
        $this->em = $em;
        $this->productQueryBuilderFactory = $productQueryBuilderFactory;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function isLinkedToProduct(ReferenceDataInterface $entity)
    {
        $attributesCodes = $this->attributeRepository->findReferenceDataAttributeCodes();

        foreach ($attributesCodes as $attributeCode) {
            $pqb = $this->productQueryBuilderFactory->create();
            try {
                $pqb->addFilter($attributeCode, Operators::IN_LIST, [$entity->getCode()]);
                $result = $pqb->execute()->count();
                if ($result > 0) {
                    return true;
                }
            } catch (InvalidPropertyException $e) {
                if ($e->getCode() !== InvalidPropertyException::VALID_ENTITY_CODE_EXPECTED_CODE) {
                    throw $e;
                }
            }
        }

        return false;
    }
}
