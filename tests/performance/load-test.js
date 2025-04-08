import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Métriques personnalisées
const errors = new Rate('errors');

// Configuration des scénarios de test
export const options = {
  scenarios: {
    // Test de charge de base
    smoke: {
      executor: 'ramping-vus',
      startVUs: 1,
      stages: [
        { duration: '30s', target: 5 },
        { duration: '30s', target: 5 },
        { duration: '30s', target: 0 }
      ],
      gracefulRampDown: '30s',
    },
    // Test de charge moyenne
    load: {
      executor: 'ramping-vus',
      startVUs: 5,
      stages: [
        { duration: '1m', target: 10 },
        { duration: '3m', target: 10 },
        { duration: '1m', target: 0 }
      ],
      gracefulRampDown: '30s',
    },
    // Test de stress
    stress: {
      executor: 'ramping-vus',
      startVUs: 10,
      stages: [
        { duration: '2m', target: 30 },
        { duration: '5m', target: 30 },
        { duration: '2m', target: 0 }
      ],
      gracefulRampDown: '30s',
    }
  },
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% des requêtes doivent être sous 500ms
    http_req_failed: ['rate<0.01'],   // Moins de 1% d'erreurs
    errors: ['rate<0.01'],            // Moins de 1% d'erreurs personnalisées
  },
};

const BASE_URL = __ENV.TARGET_URL || 'http://localhost:8000';

// Fonction pour générer des données aléatoires
function generateRandomData() {
  return {
    email: `test${Date.now()}@example.com`,
    password: 'TestPassword123!',
    firstName: 'Test',
    lastName: 'User'
  };
}

// Fonction principale de test
export default function() {
  // Test de la page d'accueil
  let homeResponse = http.get(`${BASE_URL}/`);
  check(homeResponse, {
    'homepage status is 200': (r) => r.status === 200,
    'homepage has correct title': (r) => r.body.includes('PitCrew')
  });

  // Test de la page de connexion
  let loginResponse = http.get(`${BASE_URL}/login`);
  check(loginResponse, {
    'login page status is 200': (r) => r.status === 200,
    'login form is present': (r) => r.body.includes('form')
  });

  // Test de la recherche d'offres
  let searchResponse = http.get(`${BASE_URL}/jobs/search?q=developer`);
  check(searchResponse, {
    'search results status is 200': (r) => r.status === 200
  });

  // Test de l'API
  let apiResponse = http.get(`${BASE_URL}/api/health`);
  check(apiResponse, {
    'API health check is 200': (r) => r.status === 200,
    'API returns correct status': (r) => r.json('status') === 'healthy'
  });

  // Simulation d'une inscription (sans réellement créer l'utilisateur)
  const userData = generateRandomData();
  let registerResponse = http.post(`${BASE_URL}/register`, userData);
  check(registerResponse, {
    'register status is 200 or 302': (r) => [200, 302].includes(r.status)
  });

  // Pause entre les itérations
  sleep(1);
} 