<?php

declare(strict_types=1);

namespace Statik\Common\Rest\Controller;

/**
 * Class ControllerException.
 */
class ControllerException extends \Exception
{
    protected string $status;

    /**
     * ControllerException constructor.
     *
     * @param mixed $status
     * @param mixed $message
     * @param mixed $code
     */
    public function __construct($status = '', $message = '', $code = 0, \Throwable $previous = null)
    {
        $this->status = $status;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get Status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}
