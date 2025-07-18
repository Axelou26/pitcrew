<?php

namespace App\Doctrine\Connection;

use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Connection qui optimise les performances en mettant en cache certaines requêtes
 * Compatible avec Doctrine DBAL 3.9
 */
class CacheableConnection extends PrimaryReadReplicaConnection
{
    private CacheItemPoolInterface $queryCache;
    private int $queryCacheTtl = 60; // 1 minute par défaut

    /**
     * @inheritDoc
     */
    public function __construct(array $params, $driver, $config = null, $eventManager = null)
    {
        parent::__construct($params, $driver, $config, $eventManager);

        // Cache en mémoire pour les requêtes fréquentes
        $this->queryCache = new ArrayAdapter();
    }

    /**
     * Optimise les requêtes SELECT fréquentes
     *
     * @param string $sql
     * @param array $params
     * @param array $types
     * @return mixed
     */
    public function fetchAssociative(string $sql, array $params = [], array $types = [])
    {
        // Si la requête peut être mise en cache
        if ($this->shouldCache($sql)) {
            $cacheKey = $this->generateCacheKey($sql, $params);
            $cacheItem = $this->queryCache->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = parent::fetchAssociative($sql, $params, $types);

            // Ne mettre en cache que les résultats non-null
            if ($result !== false) {
                $cacheItem->set($result);
                $cacheItem->expiresAfter($this->queryCacheTtl);
                $this->queryCache->save($cacheItem);
            }

            return $result;
        }

        return parent::fetchAssociative($sql, $params, $types);
    }

    /**
     * Détermine si une requête devrait être mise en cache
     */
    private function shouldCache(string $sql): bool
    {
        // Cacher uniquement les requêtes SELECT
        if (!str_starts_with(strtoupper(trim($sql)), 'SELECT')) {
            return false;
        }

        // Ne pas cacher les requêtes avec des fonctions temporelles
        $timeFunctions = ['NOW()', 'CURRENT_TIMESTAMP', 'CURRENT_DATE'];
        foreach ($timeFunctions as $function) {
            if (stripos($sql, $function) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Génère une clé de cache unique pour une requête
     */
    private function generateCacheKey(string $sql, array $params): string
    {
        return md5($sql . serialize($params));
    }
}
