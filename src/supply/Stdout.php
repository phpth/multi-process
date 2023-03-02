<?php
// +----------------------------------------------------------------------
// | multi-process
// +----------------------------------------------------------------------
// | Copyright (c) 2023
// +----------------------------------------------------------------------
// | Licensed MIT
// +----------------------------------------------------------------------
// | Author: js
// +----------------------------------------------------------------------
// | Date: 2023-02-25
// +----------------------------------------------------------------------
// | Time: 下午 03:22
// +----------------------------------------------------------------------

namespace phpth\process\supply;

use phpth\process\exception\OutputException;
use phpth\process\exception\PathException;

final class Stdout
{
    /**
     * @var int stdout redirect to file, which output str size flush to file
     */
    public static int $flushSize = 1024;

    /**
     * child process default output is stdout
     */
    public const STD = null;

    /**
     * file path name suffix
     */
    public static string $defaultFileName = 'child-%d.out';

    /**
     * @var string|null
     */
    private ?string $file;

    /**
     * @param string|null $file
     * @param int|null $processNo
     * @throws PathException
     */
    public function __construct(?string $file = null, ?int $processNo = null)
    {
        $this->file = Options::outPathFile($file, false, sprintf(self::$defaultFileName, $processNo));
    }

    /**
     * @param string|null $output
     * @param int|null $processNo
     * @return void
     * @throws OutputException
     */
    public function __invoke(?string $output, int $processNo = null): void
    {
        if ($output && !file_put_contents($this->file, $output, FILE_APPEND | LOCK_EX)) {
            throw new OutputException("can't write content to file: {$this->file}");
        }
    }
}
