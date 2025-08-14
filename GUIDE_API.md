# 🌐 Guide de l'API PitCrew

Ce guide documente l'API REST de PitCrew, incluant tous les endpoints disponibles, les formats de données et les exemples d'utilisation.

## 📋 Table des Matières

1. [Authentification](#authentification)
2. [Utilisateurs](#utilisateurs)
3. [Offres d'Emploi](#offres-demploi)
4. [Candidatures](#candidatures)
5. [Entretiens](#entretiens)
6. [Posts et Réseau Social](#posts-et-réseau-social)
7. [Messagerie](#messagerie)
8. [Notifications](#notifications)
9. [Paiements](#paiements)
10. [Recherche et Filtres](#recherche-et-filtres)

## 🔐 Authentification

### Connexion
```http
POST /api/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

**Réponse :**
```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refresh_token": "refresh_token_here",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "roles": ["ROLE_USER"],
        "profile_type": "applicant"
    }
}
```

### Rafraîchissement du Token
```http
POST /api/token/refresh
Content-Type: application/json

{
    "refresh_token": "refresh_token_here"
}
```

### Déconnexion
```http
POST /api/logout
Authorization: Bearer {token}
```

## 👤 Utilisateurs

### Créer un Compte
```http
POST /api/register
Content-Type: application/json

{
    "email": "newuser@example.com",
    "password": "password123",
    "profile_type": "applicant",
    "first_name": "Jean",
    "last_name": "Dupont"
}
```

### Profil Utilisateur
```http
GET /api/user/profile
Authorization: Bearer {token}
```

**Réponse :**
```json
{
    "id": 1,
    "email": "user@example.com",
    "first_name": "Jean",
    "last_name": "Dupont",
    "profile_type": "applicant",
    "created_at": "2024-01-15T10:30:00+00:00",
    "profile": {
        "phone": "+33123456789",
        "location": "Paris, France",
        "bio": "Développeur passionné...",
        "skills": ["PHP", "Symfony", "MySQL"],
        "experience_years": 5
    }
}
```

### Mettre à Jour le Profil
```http
PUT /api/user/profile
Authorization: Bearer {token}
Content-Type: application/json

{
    "first_name": "Jean-Pierre",
    "phone": "+33123456789",
    "location": "Lyon, France",
    "bio": "Nouvelle bio mise à jour..."
}
```

### Changer le Mot de Passe
```http
PUT /api/user/password
Authorization: Bearer {token}
Content-Type: application/json

{
    "current_password": "oldpassword",
    "new_password": "newpassword123"
}
```

## 💼 Offres d'Emploi

### Lister les Offres
```http
GET /api/job-offers?page=1&limit=20&location=Paris&category=engineering
Authorization: Bearer {token}
```

**Réponse :**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Développeur PHP Senior",
            "company": "TechCorp",
            "location": "Paris, France",
            "type": "CDI",
            "salary_min": 50000,
            "salary_max": 70000,
            "description": "Description du poste...",
            "requirements": ["PHP 8+", "Symfony", "5 ans d'expérience"],
            "created_at": "2024-01-15T10:30:00+00:00",
            "expires_at": "2024-02-15T10:30:00+00:00"
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 150,
        "pages": 8
    }
}
```

### Détails d'une Offre
```http
GET /api/job-offers/{id}
Authorization: Bearer {token}
```

### Créer une Offre (Recruteurs)
```http
POST /api/job-offers
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Développeur Full Stack",
    "company": "Ma Startup",
    "location": "Paris, France",
    "type": "CDI",
    "salary_min": 45000,
    "salary_max": 65000,
    "description": "Description détaillée du poste...",
    "requirements": ["PHP", "JavaScript", "React"],
    "benefits": ["Télétravail", "Mutuelle", "Tickets restaurant"],
    "expires_at": "2024-03-15T10:30:00+00:00"
}
```

### Mettre à Jour une Offre
```http
PUT /api/job-offers/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "title": "Développeur Full Stack Senior",
    "salary_max": 75000
}
```

### Supprimer une Offre
```http
DELETE /api/job-offers/{id}
Authorization: Bearer {token}
```

## 📋 Candidatures

### Postuler à une Offre
```http
POST /api/job-offers/{id}/apply
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "cover_letter": "Lettre de motivation...",
    "cv": [file],
    "portfolio_url": "https://monportfolio.com"
}
```

### Mes Candidatures (Candidat)
```http
GET /api/applications?status=pending&page=1&limit=20
Authorization: Bearer {token}
```

**Réponse :**
```json
{
    "data": [
        {
            "id": 1,
            "job_offer": {
                "id": 1,
                "title": "Développeur PHP Senior",
                "company": "TechCorp"
            },
            "status": "pending",
            "applied_at": "2024-01-15T10:30:00+00:00",
            "cover_letter": "Lettre de motivation...",
            "cv_url": "/uploads/cv/cv_123.pdf"
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 25,
        "pages": 2
    }
}
```

### Candidatures Reçues (Recruteur)
```http
GET /api/job-offers/{id}/applications?status=all&page=1&limit=20
Authorization: Bearer {token}
```

### Mettre à Jour le Statut
```http
PUT /api/applications/{id}/status
Authorization: Bearer {token}
Content-Type: application/json

{
    "status": "accepted",
    "feedback": "Profil très intéressant, nous vous contactons bientôt"
}
```

## 🎥 Entretiens

### Planifier un Entretien
```http
POST /api/applications/{id}/interview
Authorization: Bearer {token}
Content-Type: application/json

{
    "scheduled_at": "2024-01-20T14:00:00+00:00",
    "duration": 60,
    "type": "video",
    "notes": "Entretien technique sur Symfony et PHP"
}
```

### Détails de l'Entretien
```http
GET /api/interviews/{id}
Authorization: Bearer {token}
```

**Réponse :**
```json
{
    "id": 1,
    "application": {
        "id": 1,
        "job_offer": {
            "title": "Développeur PHP Senior",
            "company": "TechCorp"
        }
    },
    "scheduled_at": "2024-01-20T14:00:00+00:00",
    "duration": 60,
    "type": "video",
    "status": "scheduled",
    "jitsi_room": "pitcrew-interview-123",
    "jitsi_token": "jwt_token_here",
    "notes": "Entretien technique sur Symfony et PHP"
}
```

### Rejoindre l'Entretien
```http
GET /api/interviews/{id}/join
Authorization: Bearer {token}
```

### Annuler/Reporter un Entretien
```http
PUT /api/interviews/{id}/reschedule
Authorization: Bearer {token}
Content-Type: application/json

{
    "scheduled_at": "2024-01-22T14:00:00+00:00",
    "reason": "Conflit d'emploi du temps"
}
```

## 📱 Posts et Réseau Social

### Créer un Post
```http
POST /api/posts
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "content": "Nouveau post sur le développement...",
    "images": [file1, file2],
    "hashtags": ["#PHP", "#Symfony", "#Dev"]
}
```

### Fil d'Actualité
```http
GET /api/posts/feed?page=1&limit=20
Authorization: Bearer {token}
```

**Réponse :**
```json
{
    "data": [
        {
            "id": 1,
            "content": "Nouveau post sur le développement...",
            "author": {
                "id": 1,
                "first_name": "Jean",
                "last_name": "Dupont",
                "profile_type": "applicant"
            },
            "images": [
                "/uploads/posts/image1.jpg",
                "/uploads/posts/image2.jpg"
            ],
            "hashtags": ["#PHP", "#Symfony", "#Dev"],
            "likes_count": 15,
            "comments_count": 3,
            "created_at": "2024-01-15T10:30:00+00:00",
            "is_liked": false
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 150,
        "pages": 8
    }
}
```

### Liker/Unliker un Post
```http
POST /api/posts/{id}/like
Authorization: Bearer {token}
```

```http
DELETE /api/posts/{id}/like
Authorization: Bearer {token}
```

### Commenter un Post
```http
POST /api/posts/{id}/comments
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "Excellent article !"
}
```

### Supprimer un Post
```http
DELETE /api/posts/{id}
Authorization: Bearer {token}
```

## 💬 Messagerie

### Conversations
```http
GET /api/conversations?page=1&limit=20
Authorization: Bearer {token}
```

### Messages d'une Conversation
```http
GET /api/conversations/{id}/messages?page=1&limit=50
Authorization: Bearer {token}
```

### Envoyer un Message
```http
POST /api/conversations/{id}/messages
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "Bonjour, je suis intéressé par votre profil...",
    "attachments": [file1, file2]
}
```

### Démarrer une Conversation
```http
POST /api/conversations
Authorization: Bearer {token}
Content-Type: application/json

{
    "recipient_id": 2,
    "subject": "Proposition de collaboration",
    "initial_message": "Bonjour, je souhaite discuter d'une opportunité..."
}
```

## 🔔 Notifications

### Mes Notifications
```http
GET /api/notifications?unread_only=true&page=1&limit=20
Authorization: Bearer {token}
```

**Réponse :**
```json
{
    "data": [
        {
            "id": 1,
            "type": "application_status_changed",
            "title": "Statut de candidature mis à jour",
            "message": "Votre candidature pour 'Développeur PHP Senior' a été acceptée",
            "data": {
                "application_id": 1,
                "job_title": "Développeur PHP Senior",
                "company": "TechCorp"
            },
            "is_read": false,
            "created_at": "2024-01-15T10:30:00+00:00"
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 45,
        "pages": 3
    }
}
```

### Marquer comme Lu
```http
PUT /api/notifications/{id}/read
Authorization: Bearer {token}
```

### Marquer Toutes comme Lues
```http
PUT /api/notifications/read-all
Authorization: Bearer {token}
```

## 💳 Paiements

### Plans d'Abonnement
```http
GET /api/subscriptions/plans
Authorization: Bearer {token}
```

**Réponse :**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Basic",
            "price": 29.99,
            "currency": "EUR",
            "billing_cycle": "monthly",
            "features": [
                "5 offres d'emploi par mois",
                "Recherche de base",
                "Support email"
            ]
        },
        {
            "id": 2,
            "name": "Pro",
            "price": 79.99,
            "currency": "EUR",
            "billing_cycle": "monthly",
            "features": [
                "Offres d'emploi illimitées",
                "Recherche avancée",
                "Support prioritaire",
                "Analytics détaillés"
            ]
        }
    ]
}
```

### Créer un Abonnement
```http
POST /api/subscriptions
Authorization: Bearer {token}
Content-Type: application/json

{
    "plan_id": 2,
    "payment_method_id": "pm_1234567890"
}
```

### Historique des Paiements
```http
GET /api/subscriptions/payments?page=1&limit=20
Authorization: Bearer {token}
```

### Annuler un Abonnement
```http
DELETE /api/subscriptions/{id}
Authorization: Bearer {token}
```

## 🔍 Recherche et Filtres

### Recherche d'Offres
```http
GET /api/search/job-offers?q=PHP&location=Paris&experience=5&salary_min=50000
Authorization: Bearer {token}
```

### Recherche de Candidats
```http
GET /api/search/candidates?skills=PHP,Symfony&location=Paris&experience_min=3
Authorization: Bearer {token}
```

### Recherche d'Entreprises
```http
GET /api/search/companies?q=Tech&industry=software&location=Paris
Authorization: Bearer {token}
```

### Suggestions de Recherche
```http
GET /api/search/suggestions?q=PHP&type=skills
Authorization: Bearer {token}
```

## 📊 Filtres et Tri

### Filtres Disponibles

#### Offres d'Emploi
- `location` : Localisation (ville, pays)
- `category` : Catégorie de poste
- `type` : Type de contrat (CDI, CDD, Freelance)
- `salary_min` / `salary_max` : Fourchette de salaire
- `experience` : Années d'expérience requises
- `remote` : Télétravail (true/false)
- `company_size` : Taille de l'entreprise

#### Candidats
- `skills` : Compétences (séparées par des virgules)
- `experience_min` / `experience_max` : Expérience
- `location` : Localisation
- `education` : Niveau d'éducation
- `availability` : Disponibilité (immédiate, 2 semaines, 1 mois)

### Tri
- `sort_by` : Champ de tri (created_at, salary, experience)
- `sort_order` : Ordre (asc, desc)

### Pagination
- `page` : Numéro de page (défaut: 1)
- `limit` : Nombre d'éléments par page (défaut: 20, max: 100)

## 🚨 Gestion des Erreurs

### Format des Erreurs
```json
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Les données fournies sont invalides",
        "details": {
            "email": ["L'email doit être valide"],
            "password": ["Le mot de passe doit contenir au moins 8 caractères"]
        }
    }
}
```

### Codes d'Erreur Communs

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `VALIDATION_ERROR` | Erreur de validation des données | 400 |
| `UNAUTHORIZED` | Non authentifié | 401 |
| `FORBIDDEN` | Accès refusé | 403 |
| `NOT_FOUND` | Ressource non trouvée | 404 |
| `RATE_LIMIT_EXCEEDED` | Limite de taux dépassée | 429 |
| `INTERNAL_ERROR` | Erreur serveur | 500 |

## 🔒 Sécurité

### Headers Requis
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Limites de Taux
- **Authentification** : 5 tentatives par minute par IP
- **API générale** : 1000 requêtes par heure par utilisateur
- **Uploads** : 10 fichiers par minute par utilisateur

### Validation des Données
- Toutes les entrées sont validées côté serveur
- Protection CSRF sur les formulaires
- Sanitisation des données HTML
- Validation des types de fichiers

## 📱 Webhooks

### Configuration des Webhooks
```http
POST /api/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
    "url": "https://votre-app.com/webhook",
    "events": ["application.status_changed", "interview.scheduled"],
    "secret": "webhook_secret_here"
}
```

### Événements Disponibles
- `user.registered` : Nouvel utilisateur inscrit
- `application.submitted` : Nouvelle candidature
- `application.status_changed` : Statut de candidature modifié
- `interview.scheduled` : Entretien programmé
- `interview.completed` : Entretien terminé
- `payment.succeeded` : Paiement réussi
- `payment.failed` : Paiement échoué

### Format des Webhooks
```json
{
    "event": "application.status_changed",
    "timestamp": "2024-01-15T10:30:00+00:00",
    "data": {
        "application_id": 1,
        "old_status": "pending",
        "new_status": "accepted",
        "user_id": 1
    },
    "signature": "sha256_signature_here"
}
```

## 📚 Exemples d'Intégration

### JavaScript/Node.js
```javascript
const axios = require('axios');

class PitCrewAPI {
    constructor(baseURL, token) {
        this.client = axios.create({
            baseURL,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
    }

    async getJobOffers(filters = {}) {
        const response = await this.client.get('/api/job-offers', { params: filters });
        return response.data;
    }

    async applyToJob(jobId, applicationData) {
        const formData = new FormData();
        Object.keys(applicationData).forEach(key => {
            formData.append(key, applicationData[key]);
        });

        const response = await this.client.post(`/api/job-offers/${jobId}/apply`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        return response.data;
    }
}

// Utilisation
const api = new PitCrewAPI('https://api.pitcrew.com', 'your_token_here');
const offers = await api.getJobOffers({ location: 'Paris', category: 'engineering' });
```

### PHP
```php
<?php

class PitCrewAPI
{
    private string $baseURL;
    private string $token;
    private array $headers;

    public function __construct(string $baseURL, string $token)
    {
        $this->baseURL = $baseURL;
        $this->token = $token;
        $this->headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
    }

    public function getJobOffers(array $filters = []): array
    {
        $url = $this->baseURL . '/api/job-offers';
        if (!empty($filters)) {
            $url .= '?' . http_build_query($filters);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function applyToJob(int $jobId, array $applicationData): array
    {
        $url = $this->baseURL . "/api/job-offers/{$jobId}/apply";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $applicationData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}

// Utilisation
$api = new PitCrewAPI('https://api.pitcrew.com', 'your_token_here');
$offers = $api->getJobOffers(['location' => 'Paris', 'category' => 'engineering']);
```

## 📞 Support

### Documentation Interactive
- **Swagger UI** : `/api/docs` (développement uniquement)
- **OpenAPI Spec** : `/api/docs.json`

### Contact Support
- **Email** : api-support@pitcrew.com
- **Documentation** : https://docs.pitcrew.com/api
- **Status Page** : https://status.pitcrew.com

---

**🎉 L'API PitCrew est prête à vous accompagner dans vos développements !**

**🔑 Commencez par vous authentifier : `POST /api/login`**
