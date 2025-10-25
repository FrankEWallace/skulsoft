<?php

namespace App\Exceptions;

use Exception;

class ImportErrorException extends Exception
{
    /**
     * Import error items
     *
     * @var array
     */
    public $items = [];

    /**
     * Import error count
     *
     * @var int
     */
    public $count = 0;

    /**
     * Show error log
     *
     * @var bool
     */
    public $showLog = false;

    /**
     * The status code to use for the response.
     *
     * @var int
     */
    public $status = 422;

    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }

    public function render($request)
    {
        return response()->json([
            'error' => true,
            'message' => $this->getMessage(),
            'items' => $this->items,
            'count' => $this->count,
            'show_log' => $this->showLog,
        ], $this->status);
    }

    /**
     * Set the error items to send with the response.
     *
     * @return $this
     */
    public function withItems(array $items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Set the error count to send with the response.
     *
     * @return $this
     */
    public function withCount(int $count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Set the error log to send with the response.
     *
     * @param  int  $count
     * @return $this
     */
    public function withErrorLog(bool $showLog)
    {
        $this->showLog = $showLog;

        return $this;
    }

    /**
     * Set the HTTP status code to be used for the response.
     *
     * @param  int  $status
     * @return $this
     */
    public function withStatus($status)
    {
        $this->status = $status;

        return $this;
    }
}
