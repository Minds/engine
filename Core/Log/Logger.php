<?php
/**
 * Logger
 *
 * @author edgebal
 */

namespace Minds\Core\Log;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\PHPConsoleHandler;
use Monolog\Logger as MonologLogger;
use Sentry\Monolog\Handler as SentryHandler;
use Sentry\SentrySdk;

/**
 * A PSR-3 logger tailored for Minds. Based off Monolog.
 *
 * @package Minds\Core\Log
 * @see \Monolog\Logger
 * @see \Psr\Log\LoggerInterface
 */
class Logger extends MonologLogger
{
    /**
     * Logger constructor.
     * @param string $channel
     * @param array $options
     */
    public function __construct(string $channel = 'Minds', array $options = [])
    {
        $options = array_merge([
            'isProduction' => true,
            'devToolsLogger' => '',
            'minLogLevel' => null,
        ], $options);

        $isProduction = (bool) $options['isProduction'];
        $level = $options['minLogLevel'] ?? MonologLogger::WARNING;

        $handlers = [];

        $errorLogHandler = new ErrorLogHandler(
            ErrorLogHandler::OPERATING_SYSTEM,
            $level,
            true,
            true
        );

        $errorLogHandler
            ->setFormatter(new LineFormatter(
                "%channel%.%level_name%: %message% \t%context% %extra%",
                'c',
                !$isProduction, // Allow newlines on dev mode
                true
            ));

        $handlers[] = $errorLogHandler;

        if ($isProduction) {
            // Do _NOT_ send INFO or DEBUG
            $handlers[] = new SentryHandler(SentrySdk::getCurrentHub(), max($level, MonologLogger::WARNING));
        } else {
            // Extra handlers for Development Mode

            switch ($options['devToolsLogger']) {
                case 'firephp':
                    $handlers[] = new FirePHPHandler($level);
                    break;

                case 'chromelogger':
                    $handlers[] = new ChromePHPHandler($level);
                    break;

                case 'phpconsole':
                    try {
                        $handlers[] = new PHPConsoleHandler([], null, $level);
                    } catch (Exception $exception) {
                        // If the server-side vendor package is not installed, ignore any warnings.
                    }
                    break;
            }
        }

        // Create Monolog instance

        parent::__construct($channel, $handlers);
    }
}
