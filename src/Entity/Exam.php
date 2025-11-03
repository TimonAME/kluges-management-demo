<?php

namespace App\Entity;

use App\Repository\ExamRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamRepository::class)]
class Exam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $exam_name = null;

    #[ORM\ManyToOne(inversedBy: 'exams')]
    private ?Subject $subject = null;

    #[ORM\ManyToOne(inversedBy: 'exams')]
    private ?User $user_taking_exam = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExamName(): ?string
    {
        return $this->exam_name;
    }

    public function setExamName(string $exam_name): static
    {
        $this->exam_name = $exam_name;

        return $this;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(?Subject $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getUserTakingExam(): ?User
    {
        return $this->user_taking_exam;
    }

    public function setUserTakingExam(?User $user_taking_exam): static
    {
        $this->user_taking_exam = $user_taking_exam;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }
}
