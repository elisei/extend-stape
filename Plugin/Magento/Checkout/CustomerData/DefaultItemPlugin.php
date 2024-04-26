<?php

namespace O2TI\ExtendedStape\Plugin\Magento\Checkout\CustomerData;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;

/**
 * Default cart item
 */
class DefaultItemPlugin extends \Magento\Checkout\CustomerData\AbstractItem
{
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Msrp\Helper\Data
     */
    protected $msrpHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Catalog\Helper\Product\ConfigurationPool
     */
    protected $configurationPool;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * @var ItemResolverInterface
     */
    private $itemResolver;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Msrp\Helper\Data $msrpHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param \Magento\Framework\Escaper|null $escaper
     * @param ItemResolverInterface|null $itemResolver
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Msrp\Helper\Data $msrpHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductAttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\Escaper $escaper = null,
        ItemResolverInterface $itemResolver = null
    ) {
        $this->configurationPool = $configurationPool;
        $this->imageHelper = $imageHelper;
        $this->msrpHelper = $msrpHelper;
        $this->urlBuilder = $urlBuilder;
        $this->checkoutHelper = $checkoutHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->escaper = $escaper ?: ObjectManager::getInstance()->get(\Magento\Framework\Escaper::class);
        $this->itemResolver = $itemResolver ?: ObjectManager::getInstance()->get(ItemResolverInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function doGetItemData()
    {
        $imageHelper = $this->imageHelper->init($this->getProductForThumbnail(), 'mini_cart_product_thumbnail');
        $productName = $this->escaper->escapeHtml($this->item->getProduct()->getName());
        $productId = $this->item->getProduct()->getId();
        $tipoDePedraId = $this->item->getProduct()->getTipoDePedra();
        $tipoDePedra = $this->getAttributeValue('tipo_de_pedra', (int) $tipoDePedraId);
        $formatoDoCristalId = $this->item->getProduct()->getFormatoDoCristal();
        $formatoDoCristal = $this->getAttributeValue('formato_do_cristal', (int) $formatoDoCristalId);

        return [
            'options' => $this->getOptionList(),
            'qty' => $this->item->getQty() * 1,
            'item_id' => $this->item->getId(),
            'configure_url' => $this->getConfigureUrl(),
            'is_visible_in_site_visibility' => $this->item->getProduct()->isVisibleInSiteVisibility(),
            'product_tipo_de_pedra' => $this->escaper->escapeHtml($tipoDePedra),
            'product_formato_do_cristal' => $this->escaper->escapeHtml($formatoDoCristal),
            'product_id' => $this->item->getProduct()->getId(),
            'product_name' => $productName,
            'product_sku' => $this->item->getProduct()->getSku(),
            'product_url' => $this->getProductUrl(),
            'product_has_url' => $this->hasProductUrl(),
            'product_price' => $this->checkoutHelper->formatPrice($this->item->getCalculationPrice()),
            'product_price_value' => $this->item->getCalculationPrice(),
            'product_image' => [
                'src' => $imageHelper->getUrl(),
                'alt' => $imageHelper->getLabel(),
                'width' => $imageHelper->getWidth(),
                'height' => $imageHelper->getHeight(),
            ],
            'canApplyMsrp' => $this->msrpHelper->isShowBeforeOrderConfirm($this->item->getProduct())
                && $this->msrpHelper->isMinimalPriceLessMsrp($this->item->getProduct()),
            'message' => $this->item->getMessage(),
        ];
    }

    /**
     * Get list of all options for product
     *
     * @return array
     * @codeCoverageIgnore
     */
    protected function getOptionList()
    {
        return $this->configurationPool->getByProductType($this->item->getProductType())->getOptions($this->item);
    }

    /**
     * Returns product for thumbnail.
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    protected function getProductForThumbnail()
    {
        return $this->itemResolver->getFinalProduct($this->item);
    }

    /**
     * Returns product.
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    protected function getProduct()
    {
        return $this->item->getProduct();
    }

    /**
     * Get item configure url
     *
     * @return string
     */
    protected function getConfigureUrl()
    {
        return $this->urlBuilder->getUrl(
            'checkout/cart/configure',
            ['id' => $this->item->getId(), 'product_id' => $this->item->getProduct()->getId()]
        );
    }

    /**
     * Check Product has URL
     *
     * @return bool
     */
    protected function hasProductUrl()
    {
        if ($this->item->getRedirectUrl()) {
            return true;
        }

        $product = $this->item->getProduct();
        $option = $this->item->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        if ($product->isVisibleInSiteVisibility()) {
            return true;
        } else {
            if ($product->hasUrlDataObject()) {
                $data = $product->getUrlDataObject();
                if (in_array($data->getVisibility(), $product->getVisibleInSiteVisibilities())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retrieve URL to item Product
     *
     * @return string
     */
    protected function getProductUrl()
    {
        if ($this->item->getRedirectUrl()) {
            return $this->item->getRedirectUrl();
        }

        $product = $this->item->getProduct();
        $option = $this->item->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        return $product->getUrlModel()->getUrl($product);
    }

    /**
     * Get Attribute Value.
     *
     * @param string $attributeCode
     * @param int    $attributeValue
     *
     * @return string
     */
    public function getAttributeValue($attributeCode, $attributeValue)
    {
        $optionText = 'n/a';

        if ($attributeValue) {
            try {
                /** @var SearchCriteriaBuilder $searchCriteria */
                $searchCriteria = $this->searchCriteriaBuilder
                        ->addFilter('attribute_code', $attributeCode, 'eq')
                        ->create();

                $attribute = null;
                $attributes = $this->attributeRepository->getList($searchCriteria)->getItems();
                
                if (!empty($attributes)) {
                    $attribute = reset($attributes);
                }
                
                if ($attribute) {
                    $options = $attribute->getOptions();
                
                    foreach ($options as $option) {
                        if ($option->getValue() == $attributeValue) {
                            $optionText = $option->getLabel();
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                $optionText = 'error: ' . $e->getMessage();
            }
        }

        return $optionText;
    }
}
