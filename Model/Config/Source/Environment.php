<?php

namespace JanisCommerce\JanisConnector\Model\Config\Source;

use Magento\Framework\App\State;
use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    /**
     * @var State
     */
    private $appState;

    /**
     * @param State $appState
     */
    public function __construct(
        State $appState
    ) {
        $this->appState = $appState;
    }

    /**
     * Return array of options for the environment selector
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            ['value' => 'production', 'label' => __('Production')],
            ['value' => 'qa', 'label' => __('QA')]
        ];

        // Add Beta option only in developer mode
        try {
            if ($this->appState->getMode() === State::MODE_DEVELOPER) {
                $options[] = ['value' => 'beta', 'label' => __('Beta')];
            }
        } catch (\Exception $e) {
            // If we can't determine the mode, don't add Beta option
        }

        return $options;
    }
}
