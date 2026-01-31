<?php

declare(strict_types=1);

use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Install\AgentsDetector;
use Laravel\Boost\Install\Herd;
use Laravel\Boost\Install\Sail;
use Laravel\Boost\Support\Config;
use Laravel\Prompts\Terminal;

beforeEach(function (): void {
    (new Config)->flush();
});

afterEach(function (): void {
    (new Config)->flush();
});

test('it preserves configured guidelines path in interactive mode', function (): void {
    $config = new Config;
    $config->setGuidelinesPath('.ai/boost-guidelines.md');

    $command = new class(Mockery::mock(AgentsDetector::class), $config, Mockery::mock(Herd::class), Mockery::mock(Sail::class), Mockery::mock(Terminal::class)) extends InstallCommand
    {
        public function runStoreConfig(): void
        {
            $this->storeConfig();
        }

        protected function isExplicitFlagMode(): bool
        {
            return false;
        }

        protected function requestedGuidelinesPath(): ?string
        {
            return null;
        }
    };

    $reflection = new ReflectionClass(InstallCommand::class);
    $setPrivateProperty = function (string $name, mixed $value) use ($command, $reflection): void {
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($command, $value);
    };

    $setPrivateProperty('selectedBoostFeatures', collect(['guidelines']));
    $setPrivateProperty('selectedAgents', collect());
    $setPrivateProperty('selectedThirdPartyPackages', collect());
    $setPrivateProperty('installedSkillNames', []);

    $command->runStoreConfig();

    expect($config->getGuidelinesPath())->toBe('.ai/boost-guidelines.md');
});

test('it does not update config when guidelines path write fails', function (): void {
    $config = new Config;

    $command = new class(
        Mockery::mock(AgentsDetector::class),
        $config,
        Mockery::mock(Herd::class),
        Mockery::mock(Sail::class),
        Mockery::mock(Terminal::class),
    ) extends InstallCommand {
        public function runStoreConfig(): void
        {
            $this->storeConfig();
        }

        protected function isExplicitFlagMode(): bool
        {
            return false;
        }

        protected function requestedGuidelinesPath(): ?string
        {
            return null;
        }

        protected function resolvedGuidelinesPath(): ?string
        {
            return '.ai/boost-guidelines.md';
        }
    };

    $reflection = new ReflectionClass(InstallCommand::class);
    $setPrivateProperty = function (string $name, mixed $value) use ($command, $reflection): void {
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($command, $value);
    };

    $setPrivateProperty('selectedBoostFeatures', collect(['guidelines']));
    $setPrivateProperty('selectedAgents', collect());
    $setPrivateProperty('selectedThirdPartyPackages', collect());
    $setPrivateProperty('installedSkillNames', []);
    $setPrivateProperty('guidelinesPathWriteFailed', true);

    $command->runStoreConfig();

    expect($config->getGuidelines())->toBeFalse()
        ->and($config->getGuidelinesPath())->toBeNull();
});
