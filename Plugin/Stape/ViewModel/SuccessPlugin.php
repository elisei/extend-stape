<?php

namespace O2TI\ExtendedStape\Plugin\Stape\ViewModel;

use Stape\Gtm\ViewModel\Success as ViewModelSuccess;
use O2TI\ExtendedStape\Helper\Data;

class SuccessPlugin
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
     * @param ViewModelSuccess $subject
     * @param callable $proceed
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function aroundPrepareItems(ViewModelSuccess $subject, callable $proceed, \Magento\Sales\Model\Order $order)
    {
        $items = $proceed($order);

        $itemData = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {

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
