<?php

namespace O2TI\ExtendedStape\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface as ProductAttribute;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Data extends AbstractHelper
{
    /**
     * @var ProductAttribute
     */
    protected $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;

    /**
     * @param ProductAttribute $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteria
     */
    public function __construct(
        ProductAttribute $attributeRepository,
        SearchCriteriaBuilder $searchCriteria
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteria = $searchCriteria;
    }

    /**
     * Get Attribute Options.
     *
     * @param string $attributeCode
     * @param int    $attributeValue
     *
     * @return string
     */
    public function getAttributeOptions($attributeCode, $attributeValue)
    {
        $label = 'n/a';
        $attribute = null;

        $searchCriteria = $this->searchCriteria
            ->addFilter('attribute_code', $attributeCode)
            ->create();

        $attributes = $this->attributeRepository->getList($searchCriteria)->getItems();
                
        if (!empty($attributes)) {
            $attribute = reset($attributes);
        }
        
        if ($attribute) {
            $options = $attribute->getOptions();
        
            foreach ($options as $option) {
                if ($option->getValue() === $attributeValue) {
                    $label = $option->getLabel();
                    break;
                }
            }
        }
        
        return $label;
    }
}
