<?php declare(strict_types=1);

namespace PFinal\Memo\Models;

class Memo
{
    private int $id;
    private string $content;
    private string $createdAt;

    public function __construct(int $id, string $content)
    {
        $this->id = $id;
        $this->content = $content;
        $this->createdAt = date('Y-m-d H:i:s');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->createdAt,
        ];
    }
}
