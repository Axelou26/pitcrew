<?php

namespace App\Entity\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\JobOffer;
use App\Entity\JobApplication;
use App\Entity\Interview;
use App\Entity\Education;
use App\Entity\WorkExperience;
use App\Entity\RecruiterSubscription;

trait UserProfessionalTrait
{
    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: JobOffer::class, cascade: ['persist', 'remove'])]
    protected Collection $jobOffers;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: JobApplication::class, orphanRemoval: true)]
    private Collection $applications;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: Interview::class)]
    private Collection $recruiterInterviews;

    #[ORM\OneToMany(mappedBy: 'applicant', targetEntity: Interview::class)]
    private Collection $applicantInterviews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Education::class, orphanRemoval: true)]
    private Collection $education;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WorkExperience::class, orphanRemoval: true)]
    private Collection $workExperiences;

    #[ORM\OneToMany(mappedBy: 'recruiter', targetEntity: RecruiterSubscription::class, orphanRemoval: true)]
    private Collection $subscriptions;

    public function initializeProfessionalCollections(): void
    {
        $this->jobOffers = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->recruiterInterviews = new ArrayCollection();
        $this->applicantInterviews = new ArrayCollection();
        $this->education = new ArrayCollection();
        $this->workExperiences = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }

    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function getRecruiterInterviews(): Collection
    {
        return $this->recruiterInterviews;
    }

    public function getApplicantInterviews(): Collection
    {
        return $this->applicantInterviews;
    }

    public function getEducation(): Collection
    {
        return $this->education;
    }

    public function getWorkExperiences(): Collection
    {
        return $this->workExperiences;
    }

    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addEducation(Education $education): static
    {
        if (!$this->education->contains($education)) {
            $this->education->add($education);
            $education->setUser($this);
        }
        return $this;
    }

    public function removeEducation(Education $education): static
    {
        if ($this->education->removeElement($education)) {
            if ($education->getUser() === $this) {
                $education->setUser(null);
            }
        }
        return $this;
    }
}
