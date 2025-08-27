<?php

namespace App\Entity;

use App\Repository\ProgressHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgressHistoryRepository::class)]
#[ORM\Table(name: 'progress_history')]
class ProgressHistory implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Progress $progress = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lesson $lesson = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $oldStatus = null;

    #[ORM\Column(length: 20)]
    private ?string $newStatus = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $changedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $requestId = null;

    public function __construct()
    {
        $this->changedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgress(): ?Progress
    {
        return $this->progress;
    }

    public function setProgress(?Progress $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): self
    {
        $this->lesson = $lesson;
        return $this;
    }

    public function getOldStatus(): ?string
    {
        return $this->oldStatus;
    }

    public function setOldStatus(?string $oldStatus): self
    {
        $this->oldStatus = $oldStatus;
        return $this;
    }

    public function getNewStatus(): ?string
    {
        return $this->newStatus;
    }

    public function setNewStatus(string $newStatus): self
    {
        $this->newStatus = $newStatus;
        return $this;
    }

    public function getChangedAt(): ?\DateTimeImmutable
    {
        return $this->changedAt;
    }

    public function setChangedAt(\DateTimeImmutable $changedAt): self
    {
        $this->changedAt = $changedAt;
        return $this;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestId(?string $requestId): self
    {
        $this->requestId = $requestId;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'progress_id' => $this->getProgress() ? $this->getProgress()->getId() : null,
            'user_id' => $this->getUser() ? $this->getUser()->getId() : null,
            'lesson_id' => $this->getLesson() ? $this->getLesson()->getId() : null,
            'old_status' => $this->getOldStatus(),
            'new_status' => $this->getNewStatus(),
            'changed_at' => $this->getChangedAt() ? $this->getChangedAt()->format('Y-m-d H:i:s') : null,
            'request_id' => $this->getRequestId(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
