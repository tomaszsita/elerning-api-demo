<?php

namespace App\Repository\Interfaces;

use App\Entity\Lesson;

interface LessonRepositoryInterface
{
    public function find(mixed $id, $lockMode = null, ?int $lockVersion = null): ?object;
    public function save(Lesson $lesson): void;
}
