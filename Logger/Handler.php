<?php

namespace JanisCommerce\JanisConnector\Logger;

use JanisCommerce\JanisConnector\Model\Log\Reader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Monolog\Handler\RotatingFileHandler;

/**
 * Writes one log file per day and keeps a rolling window of the last N days.
 *
 * The previous handler always appended to a single file that nothing ever
 * rotated or pruned, so it grew without bound. RotatingFileHandler deletes the
 * oldest file once the window is full, which is a moving 30 day window rather
 * than a calendar month cut.
 */
class Handler extends RotatingFileHandler
{
    /**
     * Days of history kept on disk.
     */
    const MAX_FILES = 30;

    /**
     * @param DirectoryList $directoryList
     * @param int $maxFiles Days of log history to keep
     */
    public function __construct(
        DirectoryList $directoryList,
        $maxFiles = self::MAX_FILES
    ) {
        $file = $directoryList->getPath(DirectoryList::LOG)
            . '/' . Reader::LOG_BASENAME . '.' . Reader::LOG_EXTENSION;

        parent::__construct($file, (int)$maxFiles, JanisConnectorLogger::INFO);

        // janis_connector-2026-09-30.log
        $this->setFilenameFormat('{filename}-{date}', self::FILE_PER_DAY);
    }
}
