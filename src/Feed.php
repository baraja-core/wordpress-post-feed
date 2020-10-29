<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


use Nette\Caching\Cache;
use Nette\Caching\IStorage;

final class Feed
{
	private Cache $cache;

	private string $expirationTime;


	public function __construct(IStorage $storage, string $expirationTime)
	{
		$this->cache = new Cache($storage, 'wordpress-post-feed');
		$this->expirationTime = $expirationTime;
	}


	/**
	 * @return Post[]
	 */
	public function load(string $url): array
	{
		$rss = new \DOMDocument();
		$rss->loadXML($this->getContent($url));

		$feed = [];
		foreach ($rss->getElementsByTagName('item') as $node) {
			/** @var \DOMElement $node */
			$feed[] = new Post(
				strip_tags($this->hydrateValueToString($node, 'title')),
				html_entity_decode(preg_replace('/^<p>(.+?)<\/p>.*/', '$1', str_replace("\n", ' ', $this->hydrateValueToString($node, 'description')))),
				$this->hydrateValueToString($node, 'link'),
				$this->hydrateValueToDateTime($node, 'pubDate'),
				$this->hydrateValueToString($node, 'creator'),
				$this->hydrateValue($node, 'category')
			);
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
		if ($flush === true || ($cache = $this->cache->load($url)) === null) {
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
	 * @return string[]
	 */
	private function hydrateValue(\DOMElement $node, string $key): array
	{
		$return = [];
		for ($i = 0; true; $i++) {
			if (($item = $node->getElementsByTagName($key)->item($i)) !== null) {
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
