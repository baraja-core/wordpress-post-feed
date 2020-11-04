<?php

declare(strict_types=1);

namespace Baraja\WordPressPostFeed;


class Post
{
	private string $title;

	private string $description;

	private string $link;

	private \DateTimeImmutable $date;

	private ?string $creator = null;

	/** @var string[] */
	private array $categories = [];

	private ?string $mainImageUrl = null;


	/**
	 * @param string[] $categories
	 */
	public function __construct(string $title, string $description, string $link, \DateTimeImmutable $date)
	{
		$this->title = $title;
		$this->description = $description;
		$this->link = $link;
		$this->date = $date;
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


	public function getCreator(): ?string
	{
		return $this->creator;
	}


	public function setCreator(?string $creator): self
	{
		$this->creator = $creator;

		return $this;
	}


	/**
	 * @return string[]
	 */
	public function getCategories(): array
	{
		return $this->categories;
	}


	/**
	 * @param string[] $categories
	 */
	public function setCategories(array $categories): self
	{
		$this->categories = $categories;

		return $this;
	}


	public function getMainImageUrl(): ?string
	{
		return $this->mainImageUrl;
	}


	public function setMainImageUrl(?string $mainImageUrl): self
	{
		$this->mainImageUrl = $mainImageUrl;

		return $this;
	}
}
