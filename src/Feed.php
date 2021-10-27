<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Caching\Storages\FileStorage;
use Nette\Utils\FileSystem;

final class Feed
{
	private Cache $cache;

	private ImageStorage $imageStorage;


	public function __construct(
		?Storage $storage = null,
		?ImageStorage $imageStorage = null,
		private string $expirationTime = '2 hours',
	) {
		if ($storage === null) {
			$tempDir = sys_get_temp_dir() . '/wordpress-feed/' . substr(md5(__FILE__), 0, 8);
			FileSystem::createDir($tempDir);
			$storage = new FileStorage($tempDir);
		}
		$this->imageStorage = $imageStorage ?? new ImageStorage;
		$this->cache = new Cache($storage, 'wordpress-post-feed');
	}


	/**
	 * Load feed data by given URL and write to cache.
	 * When the method is called, the complete feed is downloaded and cached.
	 * Post images are automatically stored in the ImageStorage service,
	 * from where they will be retrieved in the future.
	 * Checking for new feed content is done once every interval set in the constructor.
	 *
	 * @return array<int, Post>
	 */
	public function load(string $url, ?int $limit = null, int $offset = 0): array
	{
		$rawFeed = $this->loadRawFeed($url);
		if (is_string($rawFeed) === false) {
			$rawFeed = $this->downloadRawFeed($url);
			$this->writeCache($url, $rawFeed);
		}

		$cacheKey = $url . '-' . $limit . '-' . $offset . '-' . md5($rawFeed);
		/** @var array<int, Post>|null $response */
		$response = $this->cache->load($cacheKey);
		if ($response === null) {
			$rss = new \DOMDocument;
			$rss->loadXML($rawFeed);

			$feed = [];
			foreach ($rss->getElementsByTagName('item') as $node) {
				/** @var \DOMElement $node */
				$description = $this->hydrateDescription((string) $this->hydrateValueToString($node, 'description'));
				$feed[] = (new Post(
					title: strip_tags((string) $this->hydrateValueToString($node, 'title')),
					description: $description['description'],
					link: (string) $this->hydrateValueToString($node, 'link'),
					date: $this->hydrateValueToDateTime($node, 'pubDate'),
				))
					->setCreator($this->hydrateValueToString($node, 'creator'))
					->setCategories($this->hydrateValue($node, 'category'))
					->setMainImageUrl($description['mainImageUrl']);
			}
			$response = array_slice($feed, $offset, $limit);
			$this->cache->save($cacheKey, $response, [
				Cache::EXPIRATION => $this->expirationTime,
			]);
		}
		foreach ($response as $responseItem) {
			$responseItem->setImageStorage($this->imageStorage);
		}

		return $response;
	}


	public function updateCache(string $url): void
	{
		$this->writeCache(
			$url,
			$this->downloadRawFeed($url),
		);
	}


	public function clearCache(): void
	{
		$this->cache->clean([Cache::ALL => true]);
	}


	private function loadRawFeed(string $url): ?string
	{
		try {
			$cache = (string) $this->cache->load($url);
		} catch (\Throwable) {
			$cache = null;
		}

		return $cache === '' ? null : $cache;
	}


	private function downloadRawFeed(string $url): string
	{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_ENCODING => 'UTF-8',
		]);
		$haystack = curl_exec($curl);
		curl_close($curl);
		if ($haystack === false) {
			trigger_error('Feed URL "' . $url . '" is broken.');
		}
		$haystack = trim((string) $haystack);
		if ($haystack === '') {
			throw new \RuntimeException('Feed response for URL "' . $url . '" is empty.');
		}

		return $haystack;
	}


	private function writeCache(string $url, string $content, ?string $expiration = null): void
	{
		if ($content === '') { // ignore empty content
			return;
		}
		$this->cache->save($url, $content, [
			Cache::EXPIRATION => $expiration ?? $this->expirationTime,
		]);
	}


	/**
	 * @return array{description: string, mainImageUrl: string|null}
	 */
	private function hydrateDescription(string $description): array
	{
		$mainImageUrl = null;
		if (preg_match('/<img\s[^>]*?src="([^"]+)"[^>]*?>/', $description, $imageParser)) {
			$description = str_replace($imageParser[0], '', $description);
			$mainImageUrl = trim($imageParser[1]);
			try {
				$this->imageStorage->save($mainImageUrl);
			} catch (\Throwable $e) {
				trigger_error(sprintf('Image "%s" is broken: %s', $mainImageUrl, $e->getMessage()));
			}
		}

		return [
			'description' => html_entity_decode((string) preg_replace('/^<p>(.+?)<\/p>.*/', '$1', str_replace("\n", ' ', trim($description)))),
			'mainImageUrl' => $mainImageUrl,
		];
	}


	/**
	 * @return array<int, string>
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
			throw new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
		}
	}
}
