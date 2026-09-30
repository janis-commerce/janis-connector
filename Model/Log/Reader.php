<?php

namespace JanisCommerce\JanisConnector\Model\Log;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Reads the tail of the connector log without loading the whole file.
 *
 * The previous viewer called file() / file_get_contents() and then sliced the
 * last 200 lines, which pulls the entire log into memory. With two crons
 * logging every minute that file reaches hundreds of MB and opening the
 * configuration screen exhausts PHP's memory limit.
 */
class Reader
{
    /**
     * Base name of the log file, without the date suffix or the extension.
     */
    const LOG_BASENAME = 'janis_connector';

    const LOG_EXTENSION = 'log';

    /**
     * Bytes read per seek while walking the file backwards.
     */
    const CHUNK_SIZE = 8192;

    const DEFAULT_LINES = 200;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * Reader constructor.
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    /**
     * Last lines of the current log file.
     *
     * @param int $limit How many lines to return
     * @param bool $newestFirst Most recent entry first
     * @return string[]
     */
    public function tail($limit = self::DEFAULT_LINES, $newestFirst = true)
    {
        $file = $this->resolveCurrentFile();

        if (!$file) {
            return [];
        }

        $lines = $this->readLastLines($file, max(1, (int)$limit));

        return $newestFirst ? array_reverse($lines) : $lines;
    }

    /**
     * Path of the log file currently being written.
     *
     * Falls back to the most recent rotated file, and then to the pre-rotation
     * file name, so instances upgrading from an older version still see
     * something while the first rotated file is created.
     *
     * @return string|null
     */
    public function resolveCurrentFile()
    {
        $directory = $this->getLogDirectory();

        $candidates = [
            $directory . '/' . self::LOG_BASENAME . '-' . date('Y-m-d') . '.' . self::LOG_EXTENSION,
            $directory . '/' . self::LOG_BASENAME . '.' . self::LOG_EXTENSION
        ];

        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) {
                return $candidate;
            }
        }

        $rotated = $this->listRotatedFiles();

        return $rotated ? end($rotated) : null;
    }

    /**
     * Rotated log files present on disk, oldest first.
     *
     * @return string[]
     */
    public function listRotatedFiles()
    {
        $pattern = $this->getLogDirectory() . '/' . self::LOG_BASENAME . '-*.' . self::LOG_EXTENSION;

        $files = glob($pattern);

        if (!$files) {
            return [];
        }

        sort($files);

        return $files;
    }

    /**
     * @return string
     */
    public function getLogDirectory()
    {
        return $this->directoryList->getPath(DirectoryList::LOG);
    }

    /**
     * Walks the file backwards in chunks until it has collected enough lines.
     *
     * @param string $file
     * @param int $limit
     * @return string[]
     */
    private function readLastLines($file, $limit)
    {
        $handle = @fopen($file, 'rb');

        if (!$handle) {
            return [];
        }

        $buffer = '';

        fseek($handle, 0, SEEK_END);
        $position = ftell($handle);

        while ($position > 0 && substr_count($buffer, "\n") <= $limit) {

            $readSize = min(self::CHUNK_SIZE, $position);
            $position -= $readSize;

            fseek($handle, $position, SEEK_SET);

            $buffer = fread($handle, $readSize) . $buffer;
        }

        fclose($handle);

        $lines = preg_split('/\r\n|\n|\r/', trim($buffer));

        if ($lines === false) {
            return [];
        }

        if (count($lines) > $limit) {
            $lines = array_slice($lines, -$limit);
        }

        return $lines;
    }
}
