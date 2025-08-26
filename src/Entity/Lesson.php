<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
#[ORM\Table(name: 'lessons')]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    #[ORM\Column]
    private ?int $orderIndex = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;

    #[ORM\OneToMany(mappedBy: 'lesson', targetEntity: Progress::class, orphanRemoval: true)]
    private Collection $progresses;

    #[ORM\OneToMany(mappedBy: 'lesson', targetEntity: Prerequisite::class, orphanRemoval: true)]
    private Collection $prerequisites;

    #[ORM\OneToMany(mappedBy: 'requiredLesson', targetEntity: Prerequisite::class, orphanRemoval: true)]
    private Collection $requiredBy;

    public function __construct()
    {
        $this->progresses = new ArrayCollection();
        $this->prerequisites = new ArrayCollection();
        $this->requiredBy = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getOrderIndex(): ?int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(int $orderIndex): self
    {
        $this->orderIndex = $orderIndex;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;
        return $this;
    }

    /**
     * @return Collection<int, Progress>
     */
    public function getProgresses(): Collection
    {
        return $this->progresses;
    }

    public function addProgress(Progress $progress): self
    {
        if (!$this->progresses->contains($progress)) {
            $this->progresses->add($progress);
            $progress->setLesson($this);
        }
        return $this;
    }

    public function removeProgress(Progress $progress): self
    {
        if ($this->progresses->removeElement($progress)) {
            if ($progress->getLesson() === $this) {
                $progress->setLesson(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Prerequisite>
     */
    public function getPrerequisites(): Collection
    {
        return $this->prerequisites;
    }

    public function addPrerequisite(Prerequisite $prerequisite): self
    {
        if (!$this->prerequisites->contains($prerequisite)) {
            $this->prerequisites->add($prerequisite);
            $prerequisite->setLesson($this);
        }
        return $this;
    }

    public function removePrerequisite(Prerequisite $prerequisite): self
    {
        if ($this->prerequisites->removeElement($prerequisite)) {
            if ($prerequisite->getLesson() === $this) {
                $prerequisite->setLesson(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Prerequisite>
     */
    public function getRequiredBy(): Collection
    {
        return $this->requiredBy;
    }

    public function addRequiredBy(Prerequisite $requiredBy): self
    {
        if (!$this->requiredBy->contains($requiredBy)) {
            $this->requiredBy->add($requiredBy);
            $requiredBy->setRequiredLesson($this);
        }
        return $this;
    }

    public function removeRequiredBy(Prerequisite $requiredBy): self
    {
        if ($this->requiredBy->removeElement($requiredBy)) {
            if ($requiredBy->getRequiredLesson() === $this) {
                $requiredBy->setRequiredLesson(null);
            }
        }
        return $this;
    }
}
