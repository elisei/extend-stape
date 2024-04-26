<?php

namespace O2TI\ExtendedStape\Plugin\Stape\ViewModel;

use Magento\Catalog\Helper\Data;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Stape\Gtm\Model\Product\CategoryResolver;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Product implements ArgumentInterface
{
    /**
     * @var Json $json
     */
    private $json;

    /**
     * @var StoreManagerInterface $storeManager
     */
    private $storeManager;

    /**
     * @var Registry $registry
     */
    private $registry;

    /**
     * @var Data $catalogHelper
     */
    private $catalogHelper;

    /**
     * @var CategoryResolver $categoryResolver
     */
    private $categoryResolver;

    /**
     * @var PriceCurrencyInterface $priceCurrency
     */
    private $priceCurrency;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * Define class dependencies
     *
     * @param Json $json
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     * @param Data $catalogHelper
     * @param CategoryResolver $categoryResolver
     * @param PriceCurrencyInterface $priceCurrency
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        Json $json,
        StoreManagerInterface $storeManager,
        Registry $registry,
        Data $catalogHelper,
        CategoryResolver $categoryResolver,
        PriceCurrencyInterface $priceCurrency,
        ProductAttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->json = $json;
        $this->storeManager = $storeManager;
        $this->registry = $registry;
        $this->catalogHelper = $catalogHelper;
        $this->categoryResolver = $categoryResolver;
        $this->priceCurrency = $priceCurrency;
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Retrieve current product
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    private function getProduct()
    {
        if ($product = $this->registry->registry('product')) {
            return $product;
        }

        return null;
    }

    /**
     * Retrieve category name
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed|string|null
     */
    private function getCategoryName($product)
    {
        $path = $this->catalogHelper->getBreadcrumbPath();
        if ($product && count($path) == 1) {
            $category = $this->getLastCategory($product);
            return  $category ? $category->getName() : null;
        } elseif (count($path) > 1) {
            end($path);
            return prev($path)['label'] ?? null;
        }

        return '';
    }

    /**
     * Retrieve last category from product
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\Catalog\Model\Category
     */
    public function getLastCategory($product)
    {
        return $this->categoryResolver->resolve($product);
    }

    /**
     * Preparing product information
     *
     * @return array
     */
    private function getProductData()
    {
        if (!$product = $this->getProduct()) {
            return [];
        }
        $tipoPedraValue = $product->getTipoDePedra();
        $tipoPedraLabel = $this->getAttributeOptions('tipo_de_pedra', $tipoPedraValue);
        $formatoValue = $product->getFormatoDoCristal();
        $formatoLabel = $this->getAttributeOptions('formato_do_cristal', $formatoValue);
        
        return [
            'item_tipo_de_pedra' => $tipoPedraLabel,
            'item_formato_do_cristal' => $formatoLabel,
            'item_name' => $product->getName(),
            'item_id' => $product->getId(),
            'item_sku' => $product->getSku(),
            'item_category' => $this->getCategoryName($product),
            'price' => $this->priceCurrency->round($product->getFinalPrice()),
        ];
    }

    /**
     * Retrieve json
     *
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getJson()
    {
        return $this->json->serialize([
            'event' => 'view_item_stape',
            'ecommerce' => [
                'currency' => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                'items' => array_filter([
                    $this->getProductData()
                ])
            ],
        ]);
    }

    /**
     * Get Attribute Options.
     *
     * @param string $attributeCode
     * @param int    $attributeValue
     *
     * @return string
     */
    protected function getAttributeOptions($attributeCode, $attributeValue)
    {
        $label = 'n/a';
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
                if ($option->getValue() === $attributeValue) {
                    $label = $option->getLabel();
                    break;
                }
            }
        }
        
        return $label;
    }
}
