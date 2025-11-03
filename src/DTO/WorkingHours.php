<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Serializer\Attribute\Groups;

class WorkingHours
{
    #[Groups(['user:work'])]
    public readonly array $template;

    #[Groups(['user:work'])]
    public readonly array $individual;

    public function __construct(array $template = [], array $individual = [])
    {
        $this->template = $template;
        $this->individual = $individual;
    }
}
