<?php

namespace App\Entity;

use App\Repository\RecruiterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecruiterRepository::class)]
class Recruiter extends User
{
    #[ORM\Column(length: 255)]
    private ?string $companyName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $companyDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkedin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sector = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companySize = null;

    #[ORM\Column(nullable: true)]
    private ?int $foundedYear = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $benefits = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToMany(targetEntity: Applicant::class)]
    private Collection $favoriteApplicants;

    public function __construct()
    {
        parent::__construct();
        $this->favoriteApplicants = new ArrayCollection();
        $this->setRoles(['ROLE_RECRUTEUR']);
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getCompanyDescription(): ?string
    {
        return $this->companyDescription;
    }

    public function setCompanyDescription(?string $companyDescription): static
    {
        $this->companyDescription = $companyDescription;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function setLinkedin(?string $linkedin): static
    {
        $this->linkedin = $linkedin;
        return $this;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setSector(?string $sector): static
    {
        $this->sector = $sector;
        return $this;
    }

    public function getCompanySize(): ?string
    {
        return $this->companySize;
    }

    public function setCompanySize(?string $companySize): static
    {
        $this->companySize = $companySize;
        return $this;
    }

    public function getFoundedYear(): ?int
    {
        return $this->foundedYear;
    }

    public function setFoundedYear(?int $foundedYear): static
    {
        $this->foundedYear = $foundedYear;
        return $this;
    }

    public function getBenefits(): ?string
    {
        return $this->benefits;
    }

    public function setBenefits(?string $benefits): static
    {
        $this->benefits = $benefits;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    /**
     * @return Collection<int, Applicant>
     */
    public function getFavoriteApplicants(): Collection
    {
        return $this->favoriteApplicants;
    }

    public function addFavoriteApplicant(Applicant $applicant): static
    {
        if (!$this->favoriteApplicants->contains($applicant)) {
            $this->favoriteApplicants->add($applicant);
        }
        return $this;
    }

    public function removeFavoriteApplicant(Applicant $applicant): static
    {
        $this->favoriteApplicants->removeElement($applicant);
        return $this;
    }

    public function hasCandidateInFavorites(User $user): bool
    {
        if (!$user instanceof Applicant) {
            return false;
        }
        return $this->favoriteApplicants->contains($user);
    }

    public function addJobOffer(JobOffer $jobOffer): static
    {
        if (!$this->jobOffers->contains($jobOffer)) {
            $this->jobOffers->add($jobOffer);
            $jobOffer->setRecruiter($this);
        }
        return $this;
    }

    public function removeJobOffer(JobOffer $jobOffer): static
    {
        if ($this->jobOffers->removeElement($jobOffer)) {
            if ($jobOffer->getRecruiter() === $this) {
                $jobOffer->setRecruiter(null);
            }
        }
        return $this;
    }
}
