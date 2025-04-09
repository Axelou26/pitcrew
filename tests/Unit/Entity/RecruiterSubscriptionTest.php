<?php

namespace App\Tests\Unit\Entity;

use App\Entity\RecruiterSubscription;
use App\Entity\Recruiter;
use App\Entity\Subscription;
use PHPUnit\Framework\TestCase;

class RecruiterSubscriptionTest extends TestCase
{
    private RecruiterSubscription $recruiterSubscription;
    private Recruiter $recruiter;
    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recruiterSubscription = new RecruiterSubscription();
        $this->recruiter = new Recruiter();
        $this->subscription = new Subscription();

        $this->recruiter->setEmail('recruiter@example.com');
        $this->subscription->setName('Premium');
    }

    public function testConstructor(): void
    {
        $this->assertFalse($this->recruiterSubscription->isCancelled());
        $this->assertTrue($this->recruiterSubscription->isAutoRenew());
    }

    public function testRecruiterAssociation(): void
    {
        $this->recruiterSubscription->setRecruiter($this->recruiter);
        $this->assertSame($this->recruiter, $this->recruiterSubscription->getRecruiter());

        // Test avec une valeur null
        $this->recruiterSubscription->setRecruiter(null);
        $this->assertNull($this->recruiterSubscription->getRecruiter());
    }

    public function testSubscriptionAssociation(): void
    {
        $this->recruiterSubscription->setSubscription($this->subscription);
        $this->assertSame($this->subscription, $this->recruiterSubscription->getSubscription());

        // Test avec une valeur null
        $this->recruiterSubscription->setSubscription(null);
        $this->assertNull($this->recruiterSubscription->getSubscription());
    }

    public function testDates(): void
    {
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-02-01');

        $this->recruiterSubscription->setStartDate($startDate);
        $this->recruiterSubscription->setEndDate($endDate);

        $this->assertEquals($startDate, $this->recruiterSubscription->getStartDate());
        $this->assertEquals($endDate, $this->recruiterSubscription->getEndDate());
    }

    public function testIsActive(): void
    {
        $this->recruiterSubscription->setIsActive(true);
        $this->assertTrue($this->recruiterSubscription->getIsActive());

        $this->recruiterSubscription->setIsActive(false);
        $this->assertFalse($this->recruiterSubscription->getIsActive());
    }

    public function testPaymentStatus(): void
    {
        $status = 'completed';
        $this->recruiterSubscription->setPaymentStatus($status);
        $this->assertEquals($status, $this->recruiterSubscription->getPaymentStatus());
    }

    public function testRemainingJobOffers(): void
    {
        // Test avec une valeur numérique
        $remaining = 3;
        $this->recruiterSubscription->setRemainingJobOffers($remaining);
        $this->assertEquals($remaining, $this->recruiterSubscription->getRemainingJobOffers());

        // Test avec une valeur null (illimité)
        $this->recruiterSubscription->setRemainingJobOffers(null);
        $this->assertNull($this->recruiterSubscription->getRemainingJobOffers());
    }

    public function testCancelled(): void
    {
        $this->recruiterSubscription->setCancelled(true);
        $this->assertTrue($this->recruiterSubscription->isCancelled());

        $this->recruiterSubscription->setCancelled(false);
        $this->assertFalse($this->recruiterSubscription->isCancelled());
    }

    public function testAutoRenew(): void
    {
        $this->recruiterSubscription->setAutoRenew(false);
        $this->assertFalse($this->recruiterSubscription->isAutoRenew());

        $this->recruiterSubscription->setAutoRenew(true);
        $this->assertTrue($this->recruiterSubscription->isAutoRenew());
    }

    public function testStripeSubscriptionId(): void
    {
        $stripeId = 'sub_123456789';
        $this->recruiterSubscription->setStripeSubscriptionId($stripeId);
        $this->assertEquals($stripeId, $this->recruiterSubscription->getStripeSubscriptionId());

        // Test avec une valeur null
        $this->recruiterSubscription->setStripeSubscriptionId(null);
        $this->assertNull($this->recruiterSubscription->getStripeSubscriptionId());
    }

    public function testFluentInterface(): void
    {
        $returnedSubscription = $this->recruiterSubscription
            ->setRecruiter($this->recruiter)
            ->setSubscription($this->subscription)
            ->setStartDate(new \DateTime())
            ->setEndDate(new \DateTime())
            ->setIsActive(true)
            ->setPaymentStatus('completed')
            ->setRemainingJobOffers(3)
            ->setCancelled(false)
            ->setAutoRenew(true)
            ->setStripeSubscriptionId('sub_123456789');

        $this->assertSame($this->recruiterSubscription, $returnedSubscription);
    }
}
