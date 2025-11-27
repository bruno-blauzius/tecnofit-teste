<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Crontab;

use App\UseCase\Schedule\ScheduleUseCase;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Metric\Contract\CounterInterface;
use Hyperf\Metric\Contract\GaugeInterface;
use Hyperf\Metric\Contract\MetricFactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ProcessScheduledWithdrawsCrontab
{
    #[Inject]
    protected ScheduleUseCase $scheduleUseCase;

    protected LoggerInterface $logger;

    private CounterInterface $crontabExecutions;

    private CounterInterface $scheduledWithdrawsProcessed;

    private GaugeInterface $crontabLastExecutionTimestamp;

    public function __construct(
        LoggerFactory $loggerFactory,
        ContainerInterface $container
    ) {
        $this->logger = $loggerFactory->get('crontab');

        $factory = $container->get(MetricFactoryInterface::class);

        $this->crontabExecutions = $factory->makeCounter(
            'crontab_executions_total',
            ['status']
        );

        $this->scheduledWithdrawsProcessed = $factory->makeCounter(
            'scheduled_withdraws_processed_total',
            ['result']
        );

        $this->crontabLastExecutionTimestamp = $factory->makeGauge(
            'crontab_last_execution_timestamp',
            []
        );
    }

    public function handle(): void
    {
        $this->logger->info('[CRONTAB] Iniciando processamento de saques agendados');
        $this->crontabLastExecutionTimestamp->set(time());

        try {
            $result = $this->scheduleUseCase->execute();

            $processedCount = count($result['processed'] ?? []);
            $failedCount = count($result['failed'] ?? []);

            // Métricas
            $this->crontabExecutions->with('success')->add(1);
            $this->scheduledWithdrawsProcessed->with('success')->add($processedCount);
            $this->scheduledWithdrawsProcessed->with('failed')->add($failedCount);

            $this->logger->info(
                '[CRONTAB] Processamento concluído',
                [
                    'processed' => $processedCount,
                    'failed' => $failedCount,
                ]
            );

            if ($processedCount > 0) {
                $this->logger->info('[CRONTAB] Saques processados com sucesso: ' . $processedCount);
            }

            if ($failedCount > 0) {
                $this->logger->warning('[CRONTAB] Saques que falharam: ' . $failedCount);
            }
        } catch (Throwable $e) {
            $this->crontabExecutions->with('error')->add(1);

            $this->logger->error(
                '[CRONTAB] Erro ao processar saques agendados',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }
    }
}
