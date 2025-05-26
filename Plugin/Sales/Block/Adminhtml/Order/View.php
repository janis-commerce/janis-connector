<?php

namespace JanisCommerce\JanisConnector\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class View
{
    public function beforeSetLayout(OrderView $subject)
    {
        $subject->addButton(
            'order_custom_button',
            [
                'label' => __('Enviar a Janis'),
                'class' => __('send-to-janis-button'),
                'id' => 'order-view-send-to-janis',
                'onclick' => 'setLocation(\'' . $subject->getUrl('ordersendjanis/order') . '\')'
            ]
        );
    }
}
