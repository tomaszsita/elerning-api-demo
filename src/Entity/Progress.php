<?php

namespace App\Entity;

use App\Repository\ProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgressRepository::class)]
#[ORM\Table(name: 'progress')]
#[ORM\UniqueConstraint(name: 'unique_user_lesson', columns: ['user_id', 'lesson_id'])]
class Progress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $requestId = null;

    #[ORM\ManyToOne(inversedBy: 'progresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'progresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lesson $lesson = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
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

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }
}
