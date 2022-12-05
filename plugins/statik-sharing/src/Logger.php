<?php

declare(strict_types=1);

namespace Statik\Sharing;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Statik\Sharing\Share\Processor\AbstractProcessor;

/**
 * Class Logger.
 */
class Logger extends \Monolog\Logger
{
    public const LOGS_DIRECTORY = PLUGIN_DIR . '/logs';

    /**
     * Logger constructor.
     */
    public function __construct(string $name)
    {
        $loggerHandler = new RotatingFileHandler(self::LOGS_DIRECTORY . '/log.txt', 30);

        parent::__construct(
            $name,
            [$loggerHandler->setFormatter(new JsonFormatter())],
            [new PsrLogMessageProcessor()]
        );

        if (false === \file_exists(self::LOGS_DIRECTORY)) {
            \mkdir(self::LOGS_DIRECTORY);
            \file_put_contents(self::LOGS_DIRECTORY . '/.gitignore', '*.txt');
        }
    }

    /**
     * Adds a log record at the INFO level.
     */
    public function infoShare(string $action, AbstractProcessor $processor): void
    {
        $this->info(
            'User {usr}, action {action}, source blog {source_blog}, source post {source_id}, '
            . 'destination blog {dest_blog}, destination post {dest_id}',
            [
                'usr'         => \get_current_user_id(),
                'action'      => $action,
                'source_blog' => $processor->getSourceBlogId(),
                'source_id'   => $processor->getSourceObject()->getId(),
                'dest_blog'   => $processor->getDestinationBlogId(),
                'dest_id'     => $processor->getDestinationObject()
                    ? $processor->getDestinationObject()->getId()
                    : '',
            ]
        );
    }

    /**
     * Adds a log record at the ERROR level.
     */
    public function errorShare(string $action, string $error, AbstractProcessor $processor): void
    {
        $this->error(
            'User {usr}, action {action}, source blog {source_blog}, source post {source_id}, '
            . 'destination blog {dest_blog}, destination post {dest_id}, error {error}',
            [
                'usr'         => \get_current_user_id(),
                'action'      => $action,
                'source_blog' => $processor->getSourceBlogId(),
                'source_id'   => $processor->getSourceObject()->getId(),
                'dest_blog'   => $processor->getDestinationBlogId(),
                'dest_id'     => $processor->getDestinationObject()
                    ? $processor->getDestinationObject()->getId()
                    : '',
                'error'       => $error,
            ]
        );
    }

    /**
     * Adds a log record at the WARNING level.
     */
    public function warningShare(string $action, string $warning, AbstractProcessor $processor): void
    {
        $this->warning(
            'User {usr}, action {action}, source blog {source_blog}, source post {source_id}, '
            . 'destination blog {dest_blog}, destination post {dest_id}, warning {warning}',
            [
                'usr'         => \get_current_user_id(),
                'action'      => $action,
                'source_blog' => $processor->getSourceBlogId(),
                'source_id'   => $processor->getSourceObject()->getId(),
                'dest_blog'   => $processor->getDestinationBlogId(),
                'dest_id'     => $processor->getDestinationObject()
                    ? $processor->getDestinationObject()->getId()
                    : '',
                'warning'     => $warning,
            ]
        );
    }
}
