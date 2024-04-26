<?php

namespace O2TI\ExtendedStape\Plugin\Stape\ViewModel;

use Stape\Gtm\ViewModel\Product as ViewModelProduct;
use O2TI\ExtendedStape\Helper\Data;
use Magento\Framework\Registry;

class ProductPlugin
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Define class dependencies
     *
     * @param Registry $registry
     * @param Data $helper
     */
    public function __construct(
        Registry $registry,
        Data $helper
    ) {
        $this->registry = $registry;
        $this->helper = $helper;
    }

    /**
     * Plugin method to enhance getProductData functionality
     *
     * @param ViewModelProduct $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetProductData(ViewModelProduct $subject, callable $proceed)
    {
        $items = $proceed();

        if (!$product = $this->getProduct()) {
            return [];
        }

        $tipoPedraValue = $product->getTipoDePedra();
        $tipoPedraLabel = $this->helper->getAttributeOptions('tipo_de_pedra', $tipoPedraValue);

        $formatoValue = $product->getFormatoDoCristal();
        $formatoLabel = $this->helper->getAttributeOptions('formato_do_cristal', $formatoValue);

        $items['item_tipo_de_pedra'] = $tipoPedraLabel;
        $items['item_formato_do_cristal'] = $formatoLabel;

        return $items;
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

}
