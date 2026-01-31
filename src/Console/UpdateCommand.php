<?php

declare(strict_types=1);

namespace Laravel\Boost\Console;

use Illuminate\Console\Command;
use Laravel\Boost\Support\Config;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('boost:update', 'Update the Laravel Boost guidelines & skills to the latest guidance')]
class UpdateCommand extends Command
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('path', null, InputOption::VALUE_REQUIRED, 'Store Boost guidelines in a shared file');
    }

    public function handle(Config $config): int
    {
        if (! $config->isValid() || empty($config->getAgents())) {
            $this->error('Please set up Boost with [php artisan boost:install] first.');

            return self::FAILURE;
        }

        $guidelinesPath = $this->resolvedGuidelinesPath($config);
        $guidelines = $config->getGuidelines() || $guidelinesPath !== null;
        $hasSkills = $config->hasSkills();

        if (! $guidelines && ! $hasSkills) {
            return self::SUCCESS;
        }

        $options = [
            '--no-interaction' => true,
            '--guidelines' => $guidelines,
            '--skills' => $hasSkills,
        ];

        if ($guidelinesPath !== null) {
            $options['--path'] = $guidelinesPath;
        }

        $this->callSilently(InstallCommand::class, $options);

        $this->info('Boost guidelines and skills updated successfully.');

        return self::SUCCESS;
    }

    protected function resolvedGuidelinesPath(Config $config): ?string
    {
        $path = $this->option('path');

        if (is_string($path)) {
            $path = trim($path);

            if ($path !== '') {
                return $path;
            }
        }

        return $config->getGuidelinesPath();
    }
}
