<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


class Post
{
	private string $title;

	private string $description;

	private string $link;

	private \DateTimeImmutable $date;

	private string $creator;

	/** @var string[] */
	private array $categories;


	/**
	 * @param string[] $categories
	 */
	public function __construct(string $title, string $description, string $link, \DateTimeImmutable $date, string $creator, array $categories)
	{
		$this->title = $title;
		$this->description = $description;
		$this->link = $link;
		$this->date = $date;
		$this->creator = $creator;
		$this->categories = $categories;
	}


	public function getTitle(): string
	{
		return $this->title;
	}


	public function getDescription(): string
	{
		return $this->description;
	}


	public function getLink(): string
	{
		return $this->link;
	}


	public function getDate(): \DateTimeImmutable
	{
		return $this->date;
	}


	public function getCreator(): string
	{
		return $this->creator;
	}


	/**
	 * @return string[]
	 */
	public function getCategories(): array
	{
		return $this->categories;
	}
}
