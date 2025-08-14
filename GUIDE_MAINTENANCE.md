# 🔧 Guide de Maintenance et d'Optimisation PitCrew

Ce guide détaille les tâches de maintenance régulières, les procédures d'optimisation et la résolution de problèmes pour maintenir PitCrew en parfait état de fonctionnement.

## 📋 Table des Matières

1. [Maintenance Préventive](#maintenance-préventive)
2. [Monitoring et Surveillance](#monitoring-et-surveillance)
3. [Optimisation des Performances](#optimisation-des-performances)
4. [Sauvegarde et Récupération](#sauvegarde-et-récupération)
5. [Sécurité et Mises à Jour](#sécurité-et-mises-à-jour)
6. [Résolution de Problèmes](#résolution-de-problèmes)
7. [Maintenance de la Base de Données](#maintenance-de-la-base-de-données)
8. [Logs et Debugging](#logs-et-debugging)

## 🛠️ Maintenance Préventive

### 📅 Tâches Quotidiennes

#### Vérification de l'État des Services
```bash
# Vérifier le statut de tous les services
./manage-environments.sh status

# Vérifier les logs d'erreur
./manage-environments.sh logs | grep -i error

# Vérifier l'espace disque
df -h
docker system df
```

#### Monitoring des Performances
```bash
# Vérifier les métriques Prometheus
curl http://localhost:8888/metrics | grep -E "(http_requests_total|http_request_duration)"

# Vérifier la santé de l'application
curl http://localhost:8888/health

# Vérifier la base de données
docker-compose exec database mysql -u root -pazerty-26 -e "SHOW PROCESSLIST;"
```

### 📅 Tâches Hebdomadaires

#### Nettoyage Automatique
```bash
# Exécuter les scripts de nettoyage
composer cleanup:check

# Vérifier les fichiers orphelins
php bin/console app:check-orphaned-files

# Vérifier les doublons
php bin/console app:check-duplicates

# Nettoyer le cache
php bin/console cache:clear
docker-compose exec app php bin/console cache:clear
```

#### Vérification de la Qualité du Code
```bash
# Exécuter les tests
composer test:all

# Vérifier la qualité du code
composer quality:check

# Analyser avec PHPStan
composer phpstan

# Vérifier les standards PSR-12
composer php-cs-fixer -- --dry-run
```

#### Maintenance de la Base de Données
```bash
# Vérifier l'intégrité
docker-compose exec database mysqlcheck -u root -pazerty-26 --all-databases

# Analyser les tables
docker-compose exec database mysql -u root -pazerty-26 -e "ANALYZE TABLE user, job_offer, application;"

# Optimiser les tables
docker-compose exec database mysql -u root -pazerty-26 -e "OPTIMIZE TABLE user, job_offer, application;"
```

### 📅 Tâches Mensuelles

#### Mise à Jour des Dépendances
```bash
# Vérifier les mises à jour disponibles
composer outdated
npm outdated

# Mettre à jour les dépendances (après tests)
composer update
npm update

# Vérifier la sécurité
composer audit
npm audit
```

#### Analyse des Performances
```bash
# Exécuter les tests de performance
./tests/performance/HomepagePerformanceTest.php

# Analyser les requêtes lentes
docker-compose exec database mysql -u root -pazerty-26 -e "SHOW VARIABLES LIKE 'slow_query_log';"
docker-compose exec database mysql -u root -pazerty-26 -e "SHOW VARIABLES LIKE 'long_query_time';"
```

#### Sauvegarde Complète
```bash
# Sauvegarde de la base de données
docker exec pitcrew_database_1 mysqldump -u root -pazerty-26 pitcrew > backup_monthly_$(date +%Y%m).sql

# Sauvegarde des uploads
tar -czf uploads_backup_$(date +%Y%m).tar.gz public/uploads/

# Sauvegarde de la configuration
tar -czf config_backup_$(date +%Y%m).tar.gz config/ .env*
```

## 📊 Monitoring et Surveillance

### 🎯 Métriques Clés à Surveiller

#### Performance de l'Application
```bash
# Temps de réponse moyen
curl -s http://localhost:8888/metrics | grep http_request_duration_seconds

# Taux de requêtes par seconde
curl -s http://localhost:8888/metrics | grep http_requests_total

# Utilisation de la mémoire
docker stats --no-stream
```

#### Base de Données
```bash
# Connexions actives
docker-compose exec database mysql -u root -pazerty-26 -e "SHOW STATUS LIKE 'Threads_connected';"

# Requêtes en cours
docker-compose exec database mysql -u root -pazerty-26 -e "SHOW PROCESSLIST;"

# Performance des requêtes
docker-compose exec database mysql -u root -pazerty-26 -e "SHOW STATUS LIKE 'Slow_queries';"
```

#### Système
```bash
# Utilisation CPU et mémoire
docker stats --no-stream

# Espace disque
df -h
docker system df

# Logs d'erreur
docker-compose logs --tail=100 | grep -i error
```

### 🚨 Alertes Automatiques

#### Configuration des Seuils
```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['!event', '!doctrine']
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: !php/const Monolog\Logger::DEBUG
            channels: ["!event", "!doctrine"]
        # Fichier pour les erreurs critiques
        critical:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.critical.log"
            level: !php/const Monolog\Logger::CRITICAL
        # Email pour les erreurs critiques
        critical_email:
            type: native_mailer
            from_email: 'alerts@pitcrew.com'
            to_email: 'admin@pitcrew.com'
            subject: '[CRITICAL] Erreur PitCrew'
            level: !php/const Monolog\Logger::CRITICAL
```

#### Scripts de Surveillance
```bash
#!/bin/bash
# bin/monitor-health.sh

# Vérifier la santé de l'application
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/health)

if [ "$HEALTH_CHECK" != "200" ]; then
    echo "ALERTE: L'application n'est pas en bonne santé (HTTP $HEALTH_CHECK)"
    # Envoyer une alerte
    curl -X POST "https://hooks.slack.com/services/YOUR_WEBHOOK" \
         -H "Content-type: application/json" \
         -d "{\"text\":\"🚨 ALERTE: PitCrew n'est pas en bonne santé (HTTP $HEALTH_CHECK)\"}"
fi

# Vérifier l'espace disque
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 80 ]; then
    echo "ALERTE: Espace disque critique ($DISK_USAGE%)"
fi

# Vérifier la mémoire
MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ "$MEMORY_USAGE" -gt 90 ]; then
    echo "ALERTE: Utilisation mémoire critique ($MEMORY_USAGE%)"
fi
```

## 🚀 Optimisation des Performances

### 🗄️ Optimisation de la Base de Données

#### Index et Requêtes
```sql
-- Ajouter des index pour améliorer les performances
CREATE INDEX idx_user_email ON user(email);
CREATE INDEX idx_user_created_at ON user(created_at);
CREATE INDEX idx_job_offer_location ON job_offer(location);
CREATE INDEX idx_job_offer_category ON job_offer(category);
CREATE INDEX idx_application_status ON application(status);
CREATE INDEX idx_application_created_at ON application(created_at);

-- Index composites pour les requêtes fréquentes
CREATE INDEX idx_job_offer_location_category ON job_offer(location, category);
CREATE INDEX idx_user_location_skills ON user(location, skills);
```

#### Configuration MySQL Optimisée
```ini
# docker/mysql/my.cnf
[mysqld]
# Cache des requêtes
query_cache_type = 1
query_cache_size = 128M
query_cache_limit = 2M

# Buffer pool
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_log_buffer_size = 64M

# Connexions
max_connections = 200
max_connect_errors = 1000

# Requêtes lentes
slow_query_log = 1
long_query_time = 2
log_queries_not_using_indexes = 1
```

#### Requêtes Optimisées
```php
// src/Repository/JobOfferRepository.php
public function findOptimizedOffers(array $filters = []): array
{
    $qb = $this->createQueryBuilder('jo')
        ->select('jo', 'c') // Sélectionner les relations nécessaires
        ->leftJoin('jo.company', 'c')
        ->where('jo.isActive = :active')
        ->setParameter('active', true);

    // Appliquer les filtres avec des index
    if (isset($filters['location'])) {
        $qb->andWhere('jo.location LIKE :location')
           ->setParameter('location', '%' . $filters['location'] . '%');
    }

    if (isset($filters['category'])) {
        $qb->andWhere('jo.category = :category')
           ->setParameter('category', $filters['category']);
    }

    return $qb->orderBy('jo.createdAt', 'DESC')
              ->setMaxResults(20)
              ->getQuery()
              ->getResult();
}
```

### 🚀 Optimisation du Cache

#### Configuration Redis Optimisée
```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'
        pools:
            cache.doctrine.orm.default.result:
                adapter: cache.adapter.redis
                provider: '%env(REDIS_URL)%'
                default_lifetime: 3600
            cache.doctrine.orm.default.query:
                adapter: cache.adapter.redis
                provider: '%env(REDIS_URL)%'
                default_lifetime: 7200
            cache.app:
                adapter: cache.adapter.redis
                provider: '%env(REDIS_URL)%'
                default_lifetime: 1800
```

#### Cache des Requêtes Fréquentes
```php
// src/Service/CacheService.php
class CacheService
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function getCachedData(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $cached = $this->cache->get($key);
        
        if ($cached !== null) {
            $this->logger->info("Cache hit for key: $key");
            return $cached;
        }

        $this->logger->info("Cache miss for key: $key");
        $data = $callback();
        
        $this->cache->set($key, $data, $ttl);
        
        return $data;
    }

    public function invalidatePattern(string $pattern): void
    {
        // Invalider tous les caches correspondant au pattern
        $keys = $this->cache->getKeys($pattern);
        foreach ($keys as $key) {
            $this->cache->delete($key);
        }
    }
}
```

### 🌐 Optimisation Nginx

#### Configuration Nginx Optimisée
```nginx
# docker/nginx/default.conf
server {
    listen 80;
    server_name localhost;
    root /var/www/public;
    index index.php;

    # Compression Gzip
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;

    # Cache des assets statiques
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Cache des polices
    location ~* \.(woff|woff2|ttf|otf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeout optimisé
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Sécurité
    location ~ /\. {
        deny all;
    }
    
    location ~ /(config|src|tests|vendor) {
        deny all;
    }
}
```

## 💾 Sauvegarde et Récupération

### 🔄 Stratégie de Sauvegarde

#### Sauvegarde Automatisée
```bash
#!/bin/bash
# bin/backup.sh

BACKUP_DIR="/backups/pitcrew"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="pitcrew_backup_$DATE"

# Créer le répertoire de sauvegarde
mkdir -p "$BACKUP_DIR"

# Sauvegarde de la base de données
docker exec pitcrew_database_1 mysqldump \
    -u root -pazerty-26 \
    --single-transaction \
    --routines \
    --triggers \
    pitcrew > "$BACKUP_DIR/${BACKUP_NAME}.sql"

# Compression
gzip "$BACKUP_DIR/${BACKUP_NAME}.sql"

# Sauvegarde des uploads
tar -czf "$BACKUP_DIR/${BACKUP_NAME}_uploads.tar.gz" public/uploads/

# Sauvegarde de la configuration
tar -czf "$BACKUP_DIR/${BACKUP_NAME}_config.tar.gz" config/ .env*

# Nettoyage des anciennes sauvegardes (garder 30 jours)
find "$BACKUP_DIR" -name "*.gz" -mtime +30 -delete

# Log de la sauvegarde
echo "Sauvegarde $BACKUP_NAME terminée le $(date)" >> "$BACKUP_DIR/backup.log"

# Envoyer une notification
curl -X POST "https://hooks.slack.com/services/YOUR_WEBHOOK" \
     -H "Content-type: application/json" \
     -d "{\"text\":\"✅ Sauvegarde PitCrew terminée: $BACKUP_NAME\"}"
```

#### Récupération de Données
```bash
#!/bin/bash
# bin/restore.sh

BACKUP_FILE="$1"
BACKUP_DIR="/backups/pitcrew"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file>"
    echo "Exemple: $0 pitcrew_backup_20240115_143000.sql.gz"
    exit 1
fi

if [ ! -f "$BACKUP_DIR/$BACKUP_FILE" ]; then
    echo "Erreur: Fichier de sauvegarde non trouvé: $BACKUP_DIR/$BACKUP_FILE"
    exit 1
fi

echo "Récupération depuis: $BACKUP_FILE"
echo "ATTENTION: Cette opération va écraser la base de données actuelle!"
read -p "Êtes-vous sûr? (y/N): " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Arrêter l'application
    docker-compose stop app
    
    # Restaurer la base de données
    gunzip -c "$BACKUP_DIR/$BACKUP_FILE" | \
    docker exec -i pitcrew_database_1 mysql -u root -pazerty-26 pitcrew
    
    # Redémarrer l'application
    docker-compose start app
    
    echo "✅ Récupération terminée avec succès!"
else
    echo "❌ Récupération annulée"
fi
```

### 🔄 Sauvegarde Continue

#### Configuration des Réplications
```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  database:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: pitcrew_prod
    volumes:
      - mysql-data:/var/lib/mysql
      - ./docker/mysql/backup:/backup
    command: >
      --default-authentication-plugin=mysql_native_password
      --log-bin=mysql-bin
      --server-id=1
      --binlog-format=ROW
      --expire-logs-days=7
```

## 🔒 Sécurité et Mises à Jour

### 🛡️ Mise à Jour de Sécurité

#### Vérification des Vulnérabilités
```bash
# Vérifier les dépendances PHP
composer audit

# Vérifier les dépendances Node.js
npm audit

# Vérifier les images Docker
docker scout cves pitcrew/app:latest

# Mettre à jour les dépendances critiques
composer update --with-dependencies
npm audit fix
```

#### Script de Mise à Jour Sécurisée
```bash
#!/bin/bash
# bin/security-update.sh

echo "🔒 Mise à jour de sécurité PitCrew"
echo "=================================="

# Créer une sauvegarde avant mise à jour
echo "📦 Création d'une sauvegarde..."
./bin/backup.sh

# Vérifier les vulnérabilités
echo "🔍 Vérification des vulnérabilités..."
composer audit
npm audit

# Mettre à jour les dépendances critiques
echo "⬆️ Mise à jour des dépendances..."
composer update --with-dependencies
npm audit fix

# Vérifier la sécurité après mise à jour
echo "✅ Vérification post-mise à jour..."
composer audit
npm audit

# Exécuter les tests
echo "🧪 Exécution des tests..."
composer test:all

# Redémarrer les services
echo "🔄 Redémarrage des services..."
docker-compose restart

echo "🎉 Mise à jour de sécurité terminée!"
```

### 🔐 Rotation des Clés

#### Rotation des Secrets
```bash
#!/bin/bash
# bin/rotate-secrets.sh

echo "🔑 Rotation des secrets PitCrew"
echo "================================"

# Générer un nouveau APP_SECRET
NEW_SECRET=$(openssl rand -hex 32)
echo "Nouveau APP_SECRET généré"

# Mettre à jour .env.local
sed -i "s/APP_SECRET=.*/APP_SECRET=$NEW_SECRET/" .env.local

# Régénérer les clés JWT si nécessaire
php bin/console lexik:jwt:generate-keypair --overwrite

# Vider le cache
php bin/console cache:clear

# Redémarrer les services
docker-compose restart

echo "✅ Secrets mis à jour avec succès!"
```

## 🚨 Résolution de Problèmes

### 🔍 Diagnostic des Problèmes

#### Vérification Systématique
```bash
#!/bin/bash
# bin/diagnostic.sh

echo "🔍 Diagnostic complet PitCrew"
echo "=============================="

echo "1. Vérification des services..."
./manage-environments.sh status

echo "2. Vérification des logs d'erreur..."
docker-compose logs --tail=50 | grep -i error

echo "3. Vérification de la base de données..."
docker-compose exec database mysql -u root -pazerty-26 -e "SHOW PROCESSLIST;"

echo "4. Vérification de l'espace disque..."
df -h
docker system df

echo "5. Vérification de la mémoire..."
free -h
docker stats --no-stream

echo "6. Vérification de la santé de l'application..."
curl -s http://localhost:8888/health

echo "7. Vérification des métriques..."
curl -s http://localhost:8888/metrics | head -20

echo "✅ Diagnostic terminé!"
```

#### Problèmes Courants et Solutions

##### Application Lente
```bash
# Vérifier le cache
php bin/console cache:clear
docker-compose exec app php bin/console cache:clear

# Vérifier la base de données
docker-compose exec database mysql -u root -pazerty-26 -e "SHOW STATUS LIKE 'Slow_queries';"

# Vérifier Redis
docker-compose exec redis redis-cli info memory

# Vérifier les logs
docker-compose logs app | grep -i slow
```

##### Erreurs de Base de Données
```bash
# Vérifier la connexion
docker-compose exec database mysql -u root -pazerty-26 -e "SELECT 1;"

# Vérifier les processus
docker-compose exec database mysql -u root -pazerty-26 -e "SHOW PROCESSLIST;"

# Vérifier l'espace disque
docker-compose exec database df -h

# Redémarrer la base
docker-compose restart database
```

##### Problèmes de Cache
```bash
# Vider tous les caches
php bin/console cache:clear
docker-compose exec app php bin/console cache:clear

# Vérifier Redis
docker-compose exec redis redis-cli flushall

# Redémarrer Redis
docker-compose restart redis
```

### 📊 Analyse des Performances

#### Profiling avec Symfony Profiler
```yaml
# config/packages/dev/web_profiler.yaml
web_profiler:
    toolbar: true
    intercept_redirects: false
    excluded_ajax_paths: ^/api/
```

#### Monitoring des Requêtes Lentes
```sql
-- Activer le log des requêtes lentes
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Vérifier les requêtes lentes
SELECT 
    sql_text,
    exec_count,
    avg_timer_wait/1000000000 as avg_time_sec
FROM performance_schema.events_statements_summary_by_digest
WHERE avg_timer_wait > 1000000000
ORDER BY avg_timer_wait DESC;
```

## 🗄️ Maintenance de la Base de Données

### 🧹 Nettoyage Régulier

#### Script de Nettoyage
```bash
#!/bin/bash
# bin/database-cleanup.sh

echo "🧹 Nettoyage de la base de données PitCrew"
echo "==========================================="

# Nettoyer les sessions expirées
echo "Suppression des sessions expirées..."
docker-compose exec database mysql -u root -pazerty-26 pitcrew -e "
DELETE FROM sessions WHERE last_used < DATE_SUB(NOW(), INTERVAL 30 DAY);
"

# Nettoyer les logs anciens
echo "Suppression des logs anciens..."
docker-compose exec database mysql -u root -pazerty-26 pitcrew -e "
DELETE FROM log_entries WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
"

# Nettoyer les notifications anciennes
echo "Suppression des notifications anciennes..."
docker-compose exec database mysql -u root -pazerty-26 pitcrew -e "
DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY);
"

# Optimiser les tables
echo "Optimisation des tables..."
docker-compose exec database mysql -u root -pazerty-26 pitcrew -e "
OPTIMIZE TABLE user, job_offer, application, post, conversation;
"

# Analyser les tables
echo "Analyse des tables..."
docker-compose exec database mysql -u root -pazerty-26 pitcrew -e "
ANALYZE TABLE user, job_offer, application, post, conversation;
"

echo "✅ Nettoyage terminé!"
```

### 📈 Optimisation des Index

#### Script d'Optimisation des Index
```bash
#!/bin/bash
# bin/optimize-indexes.sh

echo "📈 Optimisation des index PitCrew"
echo "=================================="

# Analyser l'utilisation des index
echo "Analyse de l'utilisation des index..."
docker-compose exec database mysql -u root -pazerty-26 pitcrew -e "
SELECT 
    table_name,
    index_name,
    cardinality,
    sub_part,
    packed,
    null,
    index_type
FROM information_schema.statistics 
WHERE table_schema = 'pitcrew'
ORDER BY table_name, index_name;
"

# Identifier les index inutilisés
echo "Identification des index potentiellement inutilisés..."
docker-compose exec database mysql -u root -pazerty-26 pitcrew -e "
SELECT 
    s.table_name,
    s.index_name,
    s.column_name
FROM information_schema.statistics s
LEFT JOIN information_schema.table_constraints tc 
    ON s.table_name = tc.table_name 
    AND s.index_name = tc.constraint_name
WHERE tc.constraint_name IS NULL
    AND s.table_schema = 'pitcrew'
    AND s.index_name != 'PRIMARY';
"

echo "✅ Analyse des index terminée!"
```

## 📝 Logs et Debugging

### 🔍 Configuration des Logs

#### Configuration Monolog Avancée
```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['!event', '!doctrine']
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: !php/const Monolog\Logger::DEBUG
            channels: ["!event", "!doctrine"]
        # Logs d'erreur séparés
        error:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.error.log"
            level: !php/const Monolog\Logger::ERROR
        # Logs de sécurité
        security:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.security.log"
            level: !php/const Monolog\Logger::INFO
            channels: ["security"]
        # Logs de performance
        performance:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.performance.log"
            level: !php/const Monolog\Logger::INFO
            channels: ["performance"]
        # Logs d'API
        api:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.api.log"
            level: !php/const Monolog\Logger::INFO
            channels: ["api"]
```

#### Script d'Analyse des Logs
```bash
#!/bin/bash
# bin/analyze-logs.sh

echo "📊 Analyse des logs PitCrew"
echo "============================"

LOG_DIR="var/log"
ENV="dev"

echo "1. Erreurs des dernières 24h..."
grep "$(date '+%Y-%m-%d')" "$LOG_DIR/$ENV.error.log" | wc -l

echo "2. Top 10 des erreurs..."
grep "$(date '+%Y-%m-%d')" "$LOG_DIR/$ENV.error.log" | \
    cut -d' ' -f5- | sort | uniq -c | sort -nr | head -10

echo "3. Requêtes lentes..."
grep "slow" "$LOG_DIR/$ENV.log" | tail -10

echo "4. Utilisation de la mémoire..."
grep "memory" "$LOG_DIR/$ENV.log" | tail -5

echo "5. Erreurs de base de données..."
grep -i "database\|mysql\|doctrine" "$LOG_DIR/$ENV.error.log" | tail -10

echo "✅ Analyse des logs terminée!"
```

### 🐛 Debugging Avancé

#### Configuration Xdebug
```ini
; docker/php/php.ini
[xdebug]
xdebug.mode=debug,profile
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.idekey=PHPSTORM
xdebug.profiler_output_dir=/var/www/var/cache/profiler
xdebug.profiler_enable=1
```

#### Script de Debug
```bash
#!/bin/bash
# bin/debug.sh

echo "🐛 Mode debug PitCrew"
echo "====================="

# Activer le mode debug
export APP_ENV=dev
export APP_DEBUG=1

# Vider le cache
php bin/console cache:clear

# Activer le profiler
echo "Profiler activé: http://localhost:8888/_profiler"

# Afficher les routes
echo "Routes disponibles:"
php bin/console debug:router | head -20

# Afficher les services
echo "Services disponibles:"
php bin/console debug:container --tag=controller | head -10

echo "✅ Mode debug activé!"
```

## 📋 Checklist de Maintenance

### ✅ Quotidien
- [ ] Vérifier le statut des services
- [ ] Surveiller les logs d'erreur
- [ ] Vérifier l'espace disque
- [ ] Contrôler la santé de l'application

### ✅ Hebdomadaire
- [ ] Exécuter les scripts de nettoyage
- [ ] Vérifier la qualité du code
- [ ] Analyser les performances
- [ ] Maintenir la base de données

### ✅ Mensuel
- [ ] Mettre à jour les dépendances
- [ ] Vérifier la sécurité
- [ ] Analyser les performances
- [ ] Créer une sauvegarde complète

### ✅ Trimestriel
- [ ] Audit de sécurité complet
- [ ] Optimisation des index
- [ ] Révision de la configuration
- [ ] Plan de maintenance

---

**🔧 Ce guide vous accompagne dans la maintenance et l'optimisation de PitCrew !**

**🚀 Pour commencer : `./bin/diagnostic.sh`**
