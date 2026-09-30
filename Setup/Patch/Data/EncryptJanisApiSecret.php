<?php

namespace JanisCommerce\JanisConnector\Setup\Patch\Data;

use JanisCommerce\JanisConnector\Helper\Data;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Encrypts the Janis API secret already stored in core_config_data.
 *
 * Until now the field was only declared as type="password", which hides the
 * input in the admin but stores the value in clear text. Adding the Encrypted
 * backend model fixes it from here on; this patch takes care of the values
 * saved before the change, so nobody has to re-enter their credentials.
 */
class EncryptJanisApiSecret implements DataPatchInterface
{
    /**
     * Magento stores encrypted values as "<key version>:<cipher>:<payload>".
     */
    const ENCRYPTED_VALUE_PATTERN = '/^\d+:\d+:/';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * EncryptJanisApiSecret constructor.
     * @param ResourceConnection $resourceConnection
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        EncryptorInterface $encryptor
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('core_config_data');

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($table, ['config_id', 'value'])
                ->where('path = ?', Data::JANIS_API_SECRET)
        );

        foreach ($rows as $row) {

            $value = (string)$row['value'];

            // Empty values have nothing to migrate; already encrypted ones would
            // be double encrypted and become unreadable.
            if ($value === '' || preg_match(self::ENCRYPTED_VALUE_PATTERN, $value)) {
                continue;
            }

            $connection->update(
                $table,
                ['value' => $this->encryptor->encrypt($value)],
                ['config_id = ?' => $row['config_id']]
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
