<?php

namespace App\Entity;

use App\Repository\PrerequisiteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrerequisiteRepository::class)]
#[ORM\Table(name: 'prerequisites')]
#[ORM\UniqueConstraint(name: 'unique_lesson_required', columns: ['lesson_id', 'required_lesson_id'])]
class Prerequisite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'prerequisites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lesson $lesson = null;

    #[ORM\ManyToOne(inversedBy: 'requiredBy')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lesson $requiredLesson = null;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRequiredLesson(): ?Lesson
    {
        return $this->requiredLesson;
    }

    public function setRequiredLesson(?Lesson $requiredLesson): self
    {
        $this->requiredLesson = $requiredLesson;
        return $this;
    }
}
