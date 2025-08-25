<?php

namespace PFinal\Memo\Models;

class Memo
{
    private $id;
    private $content;
    private $created_at;
    private $updated_at;

    public function __construct(string $content)
    {
        if (empty(trim($content))) {
            throw new \InvalidArgumentException('备忘录内容不能为空');
        }
        
        $this->id = uniqid('memo_', true);
        $this->content = trim($content);
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = $this->created_at;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        if (empty(trim($content))) {
            throw new \InvalidArgumentException('备忘录内容不能为空');
        }
        
        $this->content = trim($content);
        $this->updated_at = date('Y-m-d H:i:s');
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): string
    {
        return $this->updated_at;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
