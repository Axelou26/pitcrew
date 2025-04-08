<?php

namespace App\Tests\Unit\Config;

use App\Config\SubscriptionFeatures;
use PHPUnit\Framework\TestCase;

class SubscriptionFeaturesTest extends TestCase
{
    public function testSubscriptionLevels(): void
    {
        $this->assertContains(SubscriptionFeatures::LEVEL_BASIC, SubscriptionFeatures::SUBSCRIPTION_LEVELS);
        $this->assertContains(SubscriptionFeatures::LEVEL_PREMIUM, SubscriptionFeatures::SUBSCRIPTION_LEVELS);
        $this->assertContains(SubscriptionFeatures::LEVEL_BUSINESS, SubscriptionFeatures::SUBSCRIPTION_LEVELS);
        $this->assertCount(3, SubscriptionFeatures::SUBSCRIPTION_LEVELS);
    }

    public function testFeatureDescriptions(): void
    {
        // Test de quelques descriptions de fonctionnalités
        $this->assertArrayHasKey('post_job_offer', SubscriptionFeatures::FEATURES_DESCRIPTIONS);
        $this->assertArrayHasKey('unlimited_job_offers', SubscriptionFeatures::FEATURES_DESCRIPTIONS);
        $this->assertArrayHasKey('advanced_candidate_search', SubscriptionFeatures::FEATURES_DESCRIPTIONS);
        
        // Vérifier que les descriptions ne sont pas vides
        foreach (SubscriptionFeatures::FEATURES_DESCRIPTIONS as $description) {
            $this->assertNotEmpty($description);
        }
    }

    public function testFeaturesByLevel(): void
    {
        // Test des fonctionnalités Basic
        $this->assertContains('post_job_offer', SubscriptionFeatures::FEATURES_BY_LEVEL[SubscriptionFeatures::LEVEL_BASIC]);
        $this->assertContains('basic_applications', SubscriptionFeatures::FEATURES_BY_LEVEL[SubscriptionFeatures::LEVEL_BASIC]);
        
        // Test des fonctionnalités Premium
        $this->assertContains('unlimited_job_offers', SubscriptionFeatures::FEATURES_BY_LEVEL[SubscriptionFeatures::LEVEL_PREMIUM]);
        $this->assertContains('full_cv_access', SubscriptionFeatures::FEATURES_BY_LEVEL[SubscriptionFeatures::LEVEL_PREMIUM]);
        
        // Test des fonctionnalités Business
        $this->assertContains('advanced_candidate_search', SubscriptionFeatures::FEATURES_BY_LEVEL[SubscriptionFeatures::LEVEL_BUSINESS]);
        $this->assertContains('priority_support', SubscriptionFeatures::FEATURES_BY_LEVEL[SubscriptionFeatures::LEVEL_BUSINESS]);
    }

    public function testGetAvailableFeatures(): void
    {
        // Test pour le niveau Basic
        $basicFeatures = SubscriptionFeatures::getAvailableFeatures(SubscriptionFeatures::LEVEL_BASIC);
        $this->assertContains('post_job_offer', $basicFeatures);
        $this->assertNotContains('unlimited_job_offers', $basicFeatures);
        
        // Test pour le niveau Premium
        $premiumFeatures = SubscriptionFeatures::getAvailableFeatures(SubscriptionFeatures::LEVEL_PREMIUM);
        $this->assertContains('post_job_offer', $premiumFeatures); // Hérite de Basic
        $this->assertContains('unlimited_job_offers', $premiumFeatures);
        $this->assertNotContains('advanced_candidate_search', $premiumFeatures);
        
        // Test pour le niveau Business
        $businessFeatures = SubscriptionFeatures::getAvailableFeatures(SubscriptionFeatures::LEVEL_BUSINESS);
        $this->assertContains('post_job_offer', $businessFeatures); // Hérite de Basic
        $this->assertContains('unlimited_job_offers', $businessFeatures); // Hérite de Premium
        $this->assertContains('advanced_candidate_search', $businessFeatures);
        
        // Test avec un niveau invalide
        $invalidFeatures = SubscriptionFeatures::getAvailableFeatures('invalid_level');
        $this->assertEmpty($invalidFeatures);
    }

    public function testGetFeatureDescription(): void
    {
        // Test de descriptions existantes
        $this->assertEquals(
            SubscriptionFeatures::FEATURES_DESCRIPTIONS['post_job_offer'],
            SubscriptionFeatures::getFeatureDescription('post_job_offer')
        );
        
        // Test avec une fonctionnalité inexistante
        $this->assertEquals(
            'Description non disponible',
            SubscriptionFeatures::getFeatureDescription('non_existent_feature')
        );
        
        // Test de la casse insensible
        $this->assertEquals(
            SubscriptionFeatures::getFeatureDescription('post_job_offer'),
            SubscriptionFeatures::getFeatureDescription('POST_JOB_OFFER')
        );
    }

    public function testIsValidSubscriptionLevel(): void
    {
        // Test des niveaux valides
        $this->assertTrue(SubscriptionFeatures::isValidSubscriptionLevel(SubscriptionFeatures::LEVEL_BASIC));
        $this->assertTrue(SubscriptionFeatures::isValidSubscriptionLevel(SubscriptionFeatures::LEVEL_PREMIUM));
        $this->assertTrue(SubscriptionFeatures::isValidSubscriptionLevel(SubscriptionFeatures::LEVEL_BUSINESS));
        
        // Test avec un niveau invalide
        $this->assertFalse(SubscriptionFeatures::isValidSubscriptionLevel('invalid_level'));
        
        // Test de la casse insensible
        $this->assertTrue(SubscriptionFeatures::isValidSubscriptionLevel(strtoupper(SubscriptionFeatures::LEVEL_BASIC)));
    }

    public function testIsFeatureAvailableForLevel(): void
    {
        // Test des fonctionnalités Basic
        $this->assertTrue(SubscriptionFeatures::isFeatureAvailableForLevel(
            'post_job_offer',
            SubscriptionFeatures::LEVEL_BASIC
        ));
        
        // Test des fonctionnalités Premium
        $this->assertTrue(SubscriptionFeatures::isFeatureAvailableForLevel(
            'unlimited_job_offers',
            SubscriptionFeatures::LEVEL_PREMIUM
        ));
        $this->assertFalse(SubscriptionFeatures::isFeatureAvailableForLevel(
            'advanced_candidate_search',
            SubscriptionFeatures::LEVEL_PREMIUM
        ));
        
        // Test des fonctionnalités Business
        $this->assertTrue(SubscriptionFeatures::isFeatureAvailableForLevel(
            'advanced_candidate_search',
            SubscriptionFeatures::LEVEL_BUSINESS
        ));
        
        // Test de l'héritage des fonctionnalités
        $this->assertTrue(SubscriptionFeatures::isFeatureAvailableForLevel(
            'post_job_offer',
            SubscriptionFeatures::LEVEL_BUSINESS
        ));
        
        // Test avec une fonctionnalité invalide
        $this->assertFalse(SubscriptionFeatures::isFeatureAvailableForLevel(
            'invalid_feature',
            SubscriptionFeatures::LEVEL_BASIC
        ));
        
        // Test de la casse insensible
        $this->assertTrue(SubscriptionFeatures::isFeatureAvailableForLevel(
            'POST_JOB_OFFER',
            'BASIC'
        ));
    }
} 