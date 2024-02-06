<?php

namespace Demoniqus\EntityProcessor\Tests\unit;

use Demoniqus\EntityProcessor\EntityProcessorBundle;
use Psr\Log\NullLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Kernel extends \Symfony\Component\HttpKernel\Kernel
{
//region SECTION: Fields
	protected $rootDir = __DIR__;
//	protected string $bundlePrefix = '';

	private array $bundleConfig = ['framework.yml', 'services.yml'];
	private array $dummyConfig = ['services.yml'];
//	private ?string $cacheDir = null;
//	private ?string $logDir = null;
//endregion Fields

//region SECTION: Constructor

//endregion Constructor 

//region SECTION: Protected 
	protected function build(ContainerBuilder $container)
	{
		$container->register('logger', NullLogger::class);

		if (!$container->hasParameter('kernel.root_dir')) {
			$container->setParameter('kernel.root_dir', $this->getRootDir());
		}
	}

	protected function getBundleConfig(): array
	{
		return [];
	}


//endregion Protected

//region SECTION: Private
	private function load(LoaderInterface $loader, FileLocator $locator, array $listName)
	{
		foreach ($listName as $fileConfig) {
			$loader->load($locator->locate($fileConfig));
		}
	}
//endregion Private
//region SECTION: Public
	public function registerBundles()
	{
		return [
			/**
			 * Работаем в рамках Symfony, поэтому регистрируем\Symfony\Bundle\FrameworkBundle\FrameworkBundle
			 */
			new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
			/**
			 * Тестируем EntityProcessorBundle - подключаем его классы
			 */
			new EntityProcessorBundle(),

		];
	}

	public function registerContainerConfiguration(LoaderInterface $loader)
	{
		/**
		 * Загружаем конфигурации по указанным адресам
		 */
		$this->load(
			$loader,
			new FileLocator(__DIR__ . '/../../src/Resources/config'),
			$this->bundleConfig
		);
		$this->load(
			$loader,
			new FileLocator(__DIR__ . '/../Resources/config'),
			$this->dummyConfig
		);
		$this->load(
			$loader,
			new FileLocator($this->getRootDir() . '/../../src/Resources/config'),
			$this->getBundleConfig()
		);
	}
//endregion Public

//region SECTION: Getters/Setters 

//endregion Getters/Setters
}