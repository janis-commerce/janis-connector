<?php

namespace JanisCommerce\JanisConnector\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use \Magento\Cms\Model\BlockFactory;


/**
 * Class UpdateBlockData
 * @package Techflarestudio\Content\Setup\Patch\Data
 */
class JanisErrorBlock implements DataPatchInterface, PatchRevertableInterface
{
    const BLOCK_IDENTIFIER = 'janis-error-block';
    /**
     * @var BlockFactory
     */
    protected $blockFactory;

    /**
     * UpdateBlockData constructor.
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        BlockFactory $blockFactory
    ) {
        $this->blockFactory = $blockFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $janisErrorData = [
            'title' => 'Janis connection failed!',
            'identifier' => self::BLOCK_IDENTIFIER,
            'content' => '<div><h3>Oups, algo salió mal. Por favor intente más tarde...</h3></div>',
            'stores' => [0],
            'is_active' => 1,
        ];
        $janisErrorNoticeBlock = $this->blockFactory
            ->create()
            ->load($janisErrorData['identifier'], 'identifier');

        /**
         * Create the block if it does not exists, otherwise update the content
         */
        if (!$janisErrorNoticeBlock->getId()) {
            $janisErrorNoticeBlock->setData($janisErrorData)->save();
        } else {
            $janisErrorNoticeBlock->setContent($janisErrorData['content'])->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        /**
         * No dependencies for this
         */
        return [];
    }

    /**
     * Delete the block
     */
    public function revert()
    {
        $janisErrorNoticeBlock = $this->blockFactory
            ->create()
            ->load(self::BLOCK_IDENTIFIER, 'identifier');

        if($janisErrorNoticeBlock->getId()) {
            $janisErrorNoticeBlock->delete();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        /**
         * Aliases are useful if we change the name of the patch until then we do not need any
         */
        return [];
    }
}
