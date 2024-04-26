<?php

namespace O2TI\ExtendedStape\ConfigProvider;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Configs.
     *
     * @return array
     */
    public function getConfig()
    {
        $optionsTipoDePedra = $this->getAttributeOptions('tipo_de_pedra');
        $optionsFormato = $this->getAttributeOptions('formato_do_cristal');

        return [
            'productAttributeOptions' => [
                'tipo_de_pedra' => $optionsTipoDePedra,
                'formato_do_cristal' => $optionsFormato,
            ],
        ];
    }

    /**
     * Get Attribute Options.
     *
     * @param string $attributeCode
     *
     * @return array
     */
    protected function getAttributeOptions($attributeCode)
    {
        $options = [];
        $attribute = null;

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('attribute_code', $attributeCode)
            ->create();

        $attributes = $this->attributeRepository->getList($searchCriteria)->getItems();
                
        if (!empty($attributes)) {
            $attribute = reset($attributes);
        }
        
        if ($attribute) {
            $options = $attribute->getOptions();
        
            foreach ($options as $option) {
                $options[$option->getValue()] = $option->getLabel();
            }
        }
        
        return $options;
    }
}
