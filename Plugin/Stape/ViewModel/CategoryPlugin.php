<?php

namespace O2TI\ExtendedStape\Plugin\Stape\ViewModel;

use Stape\Gtm\ViewModel\Category as ViewModelCategory;
use Magento\Framework\View\Layout;
use O2TI\ExtendedStape\Helper\Data;

class CategoryPlugin
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Layout
     */
    protected $layout;

    /**
     * Define class dependencies
     *
     * @param Data $helper
     * @param Layout $layout
     */
    public function __construct(
        Data $helper,
        Layout $layout
    ) {
        $this->helper = $helper;
        $this->layout = $layout;
    }

    /**
     * Plugin method to enhance prepareItems functionality
     *
     * @param ViewModelCategory $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundPrepareItems(ViewModelCategory $subject, callable $proceed)
    {
        $items = $proceed();

        $itemData = [];

         /** @var \Magento\Catalog\Block\Product\ListProduct $productList */
        $productList = $this->layout->createBlock(\Magento\Catalog\Block\Product\ListProduct::class);
        $collection = $productList->getLoadedProductCollection();
        $productList->getToolbarBlock()->setCollection($productList->getLoadedProductCollection());

        foreach ($collection as $product) {

            $tipoPedraValue = $product->getTipoDePedra();
            $tipoPedraLabel = $this->helper->getAttributeOptions('tipo_de_pedra', $tipoPedraValue);

            $formatoValue = $product->getFormatoDoCristal();
            $formatoLabel = $this->helper->getAttributeOptions('formato_do_cristal', $formatoValue);

            $itemData[$product->getId()] = [
                'item_tipo_de_pedra' => $tipoPedraLabel,
                'item_formato_do_cristal' => $formatoLabel,
            ];
        }

        foreach ($items as $key => $item) {
            $itemId = $item['item_id'];

            if (isset($itemData[$itemId])) {
                $items[$key] = array_merge($item, $itemData[$itemId]);
            }
        }

        return $items;
    }
}
