<?php

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait UserDocumentsTrait
{
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $skills = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resume = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $documents = [];

    public function getSkills(): ?array
    {
        return $this->skills;
    }

    public function setSkills(?array $skills): static
    {
        $this->skills = $skills;
        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;
        return $this;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocuments(?array $documents): static
    {
        $this->documents = $documents;
        return $this;
    }
} 