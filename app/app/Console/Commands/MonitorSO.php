<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MonitorSO extends Command
{
    protected $signature = 'so:monitor {--once : Ejecutar una sola vez en vez de en bucle}';

    protected $description = 'Monitorea CPU, memoria y disco del sistema en tiempo real';

    protected $logPath;

    public function __construct()
    {
        parent::__construct();
        $this->logPath = storage_path('logs/monitor.log');
    }

    public function handle()
    {
        $this->info('Iniciando monitoreo del Sistema Operativo...');
        $this->info('Presiona CTRL+C para detener.');
        $this->newLine();

        if ($this->option('once')) {
            $this->monitorOnce();
            return Command::SUCCESS;
        }

        while (true) {
            $this->monitorOnce();
            sleep(5);
        }
    }

    protected function monitorOnce()
    {
        $cpu = $this->getCpuUsage();
        $memory = $this->getMemoryUsage();
        $disk = $this->getDiskUsage();

        $timestamp = now()->format('Y-m-d H:i:s');

        $this->line("<fg=cyan>[$timestamp]</>");
        $this->printBar('CPU   ', $cpu);
        $this->printBar('Memoria', $memory['percent'], $memory['label']);
        $this->printBar('Disco ', $disk['percent'], $disk['label']);
        $this->newLine();

        $logLine = sprintf(
            "[%s] CPU: %.1f%% | Memoria: %.1f%% (%s) | Disco: %.1f%% (%s)\n",
            $timestamp,
            $cpu,
            $memory['percent'],
            $memory['label'],
            $disk['percent'],
            $disk['label']
        );

        file_put_contents($this->logPath, $logLine, FILE_APPEND);
    }

    protected function printBar($label, $percent, $extra = '')
    {
        $totalBars = 20;
        $filled = (int) round(($percent / 100) * $totalBars);
        $filled = max(0, min($totalBars, $filled));
        $bar = str_repeat('#', $filled) . str_repeat('.', $totalBars - $filled);

        $color = $percent > 80 ? 'red' : ($percent > 50 ? 'yellow' : 'green');

        $extraText = $extra ? " ($extra)" : '';
        $this->line(sprintf(
            "%s [<fg=%s>%s</>] %.1f%%%s",
            str_pad($label, 8),
            $color,
            $bar,
            $percent,
            $extraText
        ));
    }

    protected function getCpuUsage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cores = (int) shell_exec('nproc') ?: 1;
            $percent = ($load[0] / $cores) * 100;
            return min(100, round($percent, 1));
        }
        return 0;
    }

    protected function getMemoryUsage()
    {
        $meminfo = @file_get_contents('/proc/meminfo');
        if (!$meminfo) {
            return ['percent' => 0, 'label' => 'N/A'];
        }

        preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
        preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);

        $totalKb = (int) ($total[1] ?? 0);
        $availableKb = (int) ($available[1] ?? 0);
        $usedKb = $totalKb - $availableKb;

        $percent = $totalKb > 0 ? ($usedKb / $totalKb) * 100 : 0;
        $label = sprintf('%.1fGB / %.1fGB', $usedKb / 1048576, $totalKb / 1048576);

        return ['percent' => round($percent, 1), 'label' => $label];
    }

    protected function getDiskUsage()
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;

        $percent = $total > 0 ? ($used / $total) * 100 : 0;
        $label = sprintf('%.1fGB / %.1fGB', $used / 1073741824, $total / 1073741824);

        return ['percent' => round($percent, 1), 'label' => $label];
    }
}
