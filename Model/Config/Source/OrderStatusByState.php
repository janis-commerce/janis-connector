<?php
declare(strict_types=1);

namespace JanisCommerce\JanisConnector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as StatusCollectionFactory;
use Magento\Framework\App\RequestInterface;

class OrderStatusByState implements OptionSourceInterface
{
    /**
     * @var StatusCollectionFactory
     */
    private $statusCollectionFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        StatusCollectionFactory $statusCollectionFactory,
        RequestInterface $request
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
        $this->request = $request;
    }

    /**
     * Devuelve los statuses filtrados por state
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [
            ['value' => '', 'label' => __('Please Select')]
        ];

        // ⚡ Ojo: el valor del state se obtiene del formulario de system.xml
        $state = $this->request->getParam('state');

        $collection = $this->statusCollectionFactory->create();

        if ($state) {
            $collection->joinStates()
                ->addStateFilter($state);
        } else {
            $collection->joinStates();
        }

        foreach ($collection as $status) {

            $state = $status->getState();
            $label = $status->getLabel();

            if (!empty($state)) {
                $label = sprintf('%s (%s)', $label, $state);
            }

            $options[] = [
                'value' => $status->getStatus(),
                'label' => $label
            ];
        }

        return $options;
    }
}
