<?php

declare(strict_types=1);

namespace Codeception\Module\Symfony;

use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector;
use Symfony\Component\Form\Extension\DataCollector\FormDataCollector;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\Translation\DataCollector\TranslationDataCollector;
use Symfony\Bridge\Twig\DataCollector\TwigDataCollector;
use Symfony\Component\HttpClient\DataCollector\HttpClientDataCollector;
use Symfony\Component\Mailer\DataCollector\MessageDataCollector;
use Symfony\Bundle\SecurityBundle\DataCollector\SecurityDataCollector;

/**
 * @internal
 */
enum DataCollectorName: string
{
    case EVENTS = 'events';
    case FORM = 'form';
    case HTTP_CLIENT = 'http_client';
    case LOGGER = 'logger';
    case TIME = 'time';
    case TRANSLATION = 'translation';
    case TWIG = 'twig';
    case SECURITY = 'security';
    case MAILER = 'mailer';

    /**
     * @return class-string<DataCollectorInterface>
     */
    public function collectorClass(): string
    {
        return match ($this) {
            self::EVENTS => EventDataCollector::class,
            self::FORM => FormDataCollector::class,
            self::HTTP_CLIENT => HttpClientDataCollector::class,
            self::LOGGER => LoggerDataCollector::class,
            self::TIME => TimeDataCollector::class,
            self::TRANSLATION => TranslationDataCollector::class,
            self::TWIG => TwigDataCollector::class,
            self::SECURITY => SecurityDataCollector::class,
            self::MAILER => MessageDataCollector::class,
        };
    }
}
