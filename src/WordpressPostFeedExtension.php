<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\FileSystem;

final class WordpressPostFeedExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'expirationTime' => Expect::string('2 hours'),
			'imageStoragePath' => Expect::string(),
			'imageRelativeStoragePath' => Expect::string(),
		])->castTo('array');
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var mixed[] $config */
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('feed'))
			->setFactory(Feed::class)
			->setArgument('expirationTime', $config['expirationTime'] ?? '2 hours');

		$imageStoragePaths = $this->resolveImageStoragePath($config, $builder->parameters);
		$builder->addDefinition($this->prefix('imageStorage'))
			->setFactory(ImageStorage::class)
			->setArgument('storagePath', $imageStoragePaths['storagePath'] ?? '')
			->setArgument('relativeStoragePath', $imageStoragePaths['relativeStoragePath'] ?? '');
	}


	/**
	 * @param mixed[] $config
	 * @param mixed[] $parameters
	 * @return string[]
	 */
	private function resolveImageStoragePath(array $config, array $parameters): array
	{
		if (isset($config['imageStoragePath'])) {
			$storagePath = (string) $config['imageStoragePath'];
			if (isset($config['imageRelativeStoragePath']) === false) {
				throw new \RuntimeException('Configuration option "imageRelativeStoragePath" is required when option "imageStoragePath" is declared.');
			}
			$relativeStoragePath = (string) $config['imageRelativeStoragePath'];
		} elseif (isset($parameters['wwwDir'])) {
			$relativeStoragePath = 'wordpress-post-feed';
			$storagePath = $parameters['wwwDir'] . '/' . $relativeStoragePath;
		} else {
			throw new \RuntimeException('Configuration parameter "wwwDir" does not exist. Did you install Nette correctly?');
		}
		FileSystem::createDir($storagePath);

		return [
			'storagePath' => $storagePath,
			'relativeStoragePath' => $relativeStoragePath,
		];
	}
}
