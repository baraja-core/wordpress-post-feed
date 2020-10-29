<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

final class WordpressPostFeedExtension extends CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'expirationTime' => Expect::string('2 hours'),
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
	}
}
