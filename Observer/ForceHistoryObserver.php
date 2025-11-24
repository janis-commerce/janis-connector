<?php

namespace JanisCommerce\JanisConnector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Psr\Log\LoggerInterface;

class ForceHistoryObserver implements ObserverInterface
{
    private LoggerInterface $logger;
    private HistoryFactory $historyFactory;


    public function __construct(
        LoggerInterface $logger,
        HistoryFactory $historyFactory
    ) {
        $this->logger = $logger;
        $this->historyFactory = $historyFactory;
    }

    /**
     * Forzar que se cree un registro en el historial cuando cambia el status.
     */
    public function execute(Observer $observer): void
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order || !$order->getId()) {
            return;
        }

        $origStatus = $order->getOrigData('status');
        $newStatus  = $order->getStatus();

        // Verificamos si hubo cambio real de status
        if ($newStatus && $newStatus !== $origStatus) {
            try {
                $this->logger->info(sprintf(
                    '[ForceHistoryObserver] Cambio detectado: %s → %s (Order #%s)',
                    $origStatus ?? 'N/A',
                    $newStatus,
                    $order->getIncrementId()
                ));

                // Creamos el registro de historial explícitamente
                $history = $this->historyFactory->create()
                    ->setParentId((int)$order->getId())
                    ->setStatus($newStatus)
                    ->setEntityName('order')
                    ->setComment('') // sin comentario
                    ->setIsCustomerNotified(false)
                    ->setIsVisibleOnFront(false);

                // Guardamos el historial directamente
                $history->save();

                $this->logger->info(sprintf(
                    '[ForceHistoryObserver] Historial guardado para #%s con status "%s"',
                    $order->getIncrementId(),
                    $newStatus
                ));
            } catch (\Exception $e) {
                $this->logger->error('[ForceHistoryObserver] Error al guardar historial: ' . $e->getMessage());
            }
        }
    }
}
