<?php

namespace O2TI\ExtendedStape\Plugin\Stape\ViewModel;

use Stape\Gtm\ViewModel\Checkout as ViewModelCheckout;
use O2TI\ExtendedStape\Helper\Data;

class CheckoutPlugin
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Define class dependencies
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Plugin method to enhance prepareItems functionality
     *
     * @param ViewModelCheckout $subject
     * @param callable $proceed
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    public function aroundPrepareItems(ViewModelCheckout $subject, callable $proceed, \Magento\Quote\Model\Quote $quote)
    {
        $items = $proceed($quote);

        $itemData = [];

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($quote->getAllVisibleItems() as $item) {

            $tipoPedraValue = $item->getProduct()->getTipoDePedra();
            $tipoPedraLabel = $this->helper->getAttributeOptions('tipo_de_pedra', $tipoPedraValue);

            $formatoValue = $item->getProduct()->getFormatoDoCristal();
            $formatoLabel = $this->helper->getAttributeOptions('formato_do_cristal', $formatoValue);

            $itemData[$item->getProductId()] = [
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
