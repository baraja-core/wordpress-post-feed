<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


use Nette\Caching\Cache;
use Nette\Caching\IStorage;

final class Feed
{
	private Cache $cache;

	private ImageStorage $imageStorage;


	public function __construct(
		IStorage $storage,
		?ImageStorage $imageStorage = null,
		private string $expirationTime = '2 hours',
	) {
		$this->imageStorage = $imageStorage ?? new ImageStorage;
		$this->cache = new Cache($storage, 'wordpress-post-feed');
	}


	/**
	 * @return Post[]
	 */
	public function load(string $url): array
	{
		$rss = new \DOMDocument;
		$rss->loadXML($this->getContent($url));

		$feed = [];
		foreach ($rss->getElementsByTagName('item') as $node) {
			/** @var \DOMElement $node */

			$description = $this->hydrateDescription((string) $this->hydrateValueToString($node, 'description'));
			$feed[] = (new Post(
				strip_tags((string) $this->hydrateValueToString($node, 'title')),
				(string) $description['description'],
				(string) $this->hydrateValueToString($node, 'link'),
				$this->hydrateValueToDateTime($node, 'pubDate'),
				$this->imageStorage,
			))
				->setCreator($this->hydrateValueToString($node, 'creator'))
				->setCategories($this->hydrateValue($node, 'category'))
				->setMainImageUrl($description['mainImageUrl'] ?? null);
		}

		return $feed;
	}


	public function updateCache(string $url): void
	{
		$this->getContent($url, true);
	}


	public function clearCache(): void
	{
		$this->cache->clean([Cache::ALL => true]);
	}


	private function getContent(string $url, bool $flush = false): string
	{
		$cache = $this->cache->load($url);
		if ($cache === null || $flush === true) {
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => 'UTF-8',
			]);
			$cache = curl_exec($curl);
			curl_close($curl);
			$this->cache->save($url, $cache, [
				Cache::EXPIRATION => $this->expirationTime,
			]);
		}

		return $cache;
	}


	/**
	 * @return string[]|null[]
	 */
	private function hydrateDescription(string $description): array
	{
		$mainImageUrl = null;
		if (preg_match('/<img\s[^>]*?src="([^"]+)"[^>]*?>/', $description, $imageParser)) {
			$description = str_replace($imageParser[0], '', $description);
			$mainImageUrl = trim($imageParser[1]);
			try {
				$this->imageStorage->save($mainImageUrl);
			} catch (\InvalidArgumentException $e) {
				trigger_error($e->getMessage());
			}
		}

		return [
			'description' => html_entity_decode((string) preg_replace('/^<p>(.+?)<\/p>.*/', '$1', str_replace("\n", ' ', trim($description)))),
			'mainImageUrl' => $mainImageUrl,
		];
	}


	/**
	 * @return string[]
	 */
	private function hydrateValue(\DOMElement $node, string $key): array
	{
		$return = [];
		for ($i = 0; true; $i++) {
			$item = $node->getElementsByTagName($key)->item($i);
			if ($item !== null) {
				$return[] = $item->nodeValue;
			} else {
				break;
			}
		}

		return $return;
	}


	private function hydrateValueToString(\DOMElement $node, string $key): ?string
	{
		return $this->hydrateValue($node, $key)[0] ?? null;
	}


	private function hydrateValueToDateTime(\DOMElement $node, string $key): \DateTimeImmutable
	{
		try {
			return new \DateTimeImmutable($this->hydrateValueToString($node, $key) ?? 'now');
		} catch (\Throwable $e) {
			throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}
}
