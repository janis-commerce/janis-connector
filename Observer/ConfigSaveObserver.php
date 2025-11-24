<?php

namespace JanisCommerce\JanisConnector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use JanisCommerce\JanisConnector\Util\Rest;
use JanisCommerce\JanisConnector\Helper\Data;

class ConfigSaveObserver implements ObserverInterface
{
    private Rest $rest;
    private LoggerInterface $logger;
    private Data $helper;

    public function __construct(
        Rest $rest,
        LoggerInterface $logger,
        Data $helper
    ) {
        $this->rest = $rest;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function execute(Observer $observer): void
    {
        try {
            $section = $observer->getEvent()->getSection();

            // Solo reaccionamos a la sección janis_configuration_section
            if ($section !== 'janis_configuration_section') {
                return;
            }

            // Leer credenciales usando el Helper
            $client = $this->helper->getJanisClient();
            $apiKey = $this->helper->getJanisApiKey();
            $apiSecret = $this->helper->getJanisApiSecret();

            // Validar credenciales
            if (empty($client) || empty($apiKey) || empty($apiSecret)) {
                $this->logger->warning('[JanisConnector] Config guardada pero no se enviaron datos: faltan credenciales.');
                return;
            }

            // Obtener janis_account_name
            $accountName = $this->helper->getJanisAccountName();

            // Si no hay account_name, no seguimos el flujo
            if (empty($accountName)) {
                $this->logger->warning('[JanisConnector] Config guardada pero no se enviaron datos: falta janis_account_name.');
                return;
            }

            // Obtener los statuses configurados usando el Helper
            $orderCreatedStatus = $this->helper->getOrderCreatedStatus();
            $orderInvoicedStatus = $this->helper->getOrderInvoicedStatus();

            // Si no hay statuses, tampoco hacemos nada
            if (empty($orderCreatedStatus) && empty($orderInvoicedStatus)) {
                $this->logger->info('[JanisConnector] No hay statuses configurados para enviar.');
                return;
            }

            // Construir endpoint GET basado en el account_name
            $accountListEndpoint = $this->helper->getJanisCommerceAccountEndpoint(null, $accountName);

            // Hacer GET para obtener el Id
            $getResponse = $this->rest->request($accountListEndpoint, 'GET');
            $getStatusCode = $this->rest->getStatus();

            // Verificar que el GET fue exitoso (200)
            if ((int)$getStatusCode !== 200) {
                $this->logger->error('[JanisConnector] Error al obtener Id de la API. Status code: ' . $getStatusCode, [
                    'endpoint' => $accountListEndpoint,
                    'response' => $getResponse
                ]);
                return;
            }

            // Obtener el Id de la respuesta (array, tomar el primer resultado)
            $accountId = null;
            if (is_array($getResponse) && !empty($getResponse)) {
                $firstResult = $getResponse[0];
                if (is_object($firstResult) && isset($firstResult->id)) {
                    $accountId = $firstResult->id;
                } elseif (is_array($firstResult) && isset($firstResult['id'])) {
                    $accountId = $firstResult['id'];
                }
            }

            if (empty($accountId)) {
                $this->logger->error('[JanisConnector] No se pudo obtener el Id de la respuesta del GET.', [
                    'response' => $getResponse
                ]);
                return;
            }

            // Preparar payload con el Id obtenido
            $payload = [
                'orderImportStatusOnCreation' => $orderCreatedStatus,
                'orderImportStatusOnInvoice' => $orderInvoicedStatus,
            ];

            // Construir endpoint POST usando el mismo endpoint con el ID
            $postEndpoint = $this->helper->getJanisCommerceAccountEndpoint($accountId);

            // Enviar POST con el payload
            $postResponse = $this->rest->request($postEndpoint, 'POST', json_encode($payload));
            $postStatusCode = $this->rest->getStatus();

            // Verificar el status de la respuesta POST
            if ((int)$postStatusCode !== 200) {
                $this->logger->error('[JanisConnector] Error al enviar config a API externa. Status code: ' . $postStatusCode, [
                    'endpoint' => $postEndpoint,
                    'response' => $postResponse
                ]);
                return;
            }

            $this->logger->info('[JanisConnector] Config enviada a API externa exitosamente.', [
                'status' => $postStatusCode,
                'accountId' => $accountId,
                'response' => $postResponse
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[JanisConnector] Error al enviar config a API externa: ' . $e->getMessage());
        }
    }
}
