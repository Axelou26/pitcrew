<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Subscription;
use App\Entity\RecruiterSubscription;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscription = new Subscription();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Collection::class, $this->subscription->getRecruiterSubscriptions());
        $this->assertCount(0, $this->subscription->getRecruiterSubscriptions());
        $this->assertTrue($this->subscription->getIsActive());
    }

    public function testName(): void
    {
        $name = "Premium";
        $this->subscription->setName($name);
        $this->assertEquals($name, $this->subscription->getName());
    }

    public function testPrice(): void
    {
        $price = 49.99;
        $this->subscription->setPrice($price);
        $this->assertEquals($price, $this->subscription->getPrice());
    }

    public function testDuration(): void
    {
        $duration = 30;
        $this->subscription->setDuration($duration);
        $this->assertEquals($duration, $this->subscription->getDuration());
    }

    public function testFeatures(): void
    {
        $features = [
            'Publication illimitée d\'offres',
            'Accès aux CV complets',
            'Statistiques avancées'
        ];
        $this->subscription->setFeatures($features);
        $this->assertEquals($features, $this->subscription->getFeatures());
    }

    public function testMaxJobOffers(): void
    {
        // Test avec une valeur numérique
        $maxOffers = 5;
        $this->subscription->setMaxJobOffers($maxOffers);
        $this->assertEquals($maxOffers, $this->subscription->getMaxJobOffers());

        // Test avec une valeur null (illimité)
        $this->subscription->setMaxJobOffers(null);
        $this->assertNull($this->subscription->getMaxJobOffers());
    }

    public function testIsActive(): void
    {
        $this->subscription->setIsActive(false);
        $this->assertFalse($this->subscription->getIsActive());

        $this->subscription->setIsActive(true);
        $this->assertTrue($this->subscription->getIsActive());
    }

    public function testRecruiterSubscriptions(): void
    {
        $recruiterSubscription = new RecruiterSubscription();
        
        // Test d'ajout
        $this->subscription->addRecruiterSubscription($recruiterSubscription);
        $this->assertTrue($this->subscription->getRecruiterSubscriptions()->contains($recruiterSubscription));
        $this->assertSame($this->subscription, $recruiterSubscription->getSubscription());

        // Test de suppression
        $this->subscription->removeRecruiterSubscription($recruiterSubscription);
        $this->assertFalse($this->subscription->getRecruiterSubscriptions()->contains($recruiterSubscription));
        $this->assertNull($recruiterSubscription->getSubscription());
    }

    public function testFluentInterface(): void
    {
        $returnedSubscription = $this->subscription
            ->setName('Premium')
            ->setPrice(49.99)
            ->setDuration(30)
            ->setFeatures(['Feature 1', 'Feature 2'])
            ->setMaxJobOffers(5)
            ->setIsActive(true);

        $this->assertSame($this->subscription, $returnedSubscription);
    }
} 