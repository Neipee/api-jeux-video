# API Jeux vidéo — TP Symfony

API REST de jeux vidéo faite avec **Symfony 7.4** (TP « Créer une API Symfony » + « Suite »).

## Fonctionnalités

- Entités `VideoGame` (title, releaseDate, description, coverImage), `Category` (name), `Editor` (name, country) et `User` (email, roles, password, subcriptionToNewsletter).
- Migrations Doctrine.
- CRUD complet pour chaque entité, avec les clés étrangères (catégorie et éditeur d'un jeu) et la vérification des données (Asserts).
- Suppression logique : la colonne `deleted_at` est remplie, aucune ligne n'est supprimée de la base.
- Authentification JWT (LexikJWTAuthenticationBundle) : seul un `ROLE_ADMIN` peut créer, modifier ou supprimer.
- Jeu d'essai avec les DataFixtures (et Faker).
- Erreurs renvoyées en JSON (`ExceptionSubscriber`).
- Pagination (`page`, `limit`) avec cache (tags vidés à chaque création, modification ou suppression).
- Documentation Swagger (NelmioApiDocBundle) sur `/api/doc`.
- Newsletter : la commande `app:send-newsletter` envoie à chaque abonné un e-mail (template Twig) avec les jeux qui sortent dans les 7 prochains jours. Le Scheduler la lance tous les lundis à 8h30.
- Jaquette d'un jeu (`coverImage`) envoyée en `multipart/form-data`.

## Prérequis

- PHP 8.2 ou plus, avec les extensions `pdo_mysql`, `openssl` et `fileinfo` activées dans `php.ini`
- Composer
- MySQL 8
- Symfony CLI (facultatif)

## Installation

```bash
git clone https://github.com/Neipee/api-jeux-video.git
cd api-jeux-video
composer install
```

Créer le fichier `.env.local` (jamais commité) avec ses propres accès MySQL :

```env
DATABASE_URL="mysql://root:MOT_DE_PASSE@127.0.0.1:3306/api_jeux_video?serverVersion=8.0.45&charset=utf8mb4"
```

Puis :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php bin/console lexik:jwt:generate-keypair
php bin/console assets:install
```

Sous Windows, si `lexik:jwt:generate-keypair` affiche `error:80000003:system library`, indiquer le fichier de config OpenSSL fourni avec PHP :

```bash
OPENSSL_CONF="C:/php/extras/ssl/openssl.cnf" php bin/console lexik:jwt:generate-keypair
```

Lancer le serveur :

```bash
symfony serve
```

ou, sans Symfony CLI :

```bash
php -S 127.0.0.1:8000 -t public
```

## Comptes de test (fixtures)

| Email | Mot de passe | Rôle | Newsletter |
|---|---|---|---|
| admin@jeux.fr | admin | ROLE_ADMIN | oui |
| user1@jeux.fr … user5@jeux.fr | user | ROLE_USER | user1, user3, user5 |

## Authentification

```http
POST /api/login_check
Content-Type: application/json

{"username": "admin@jeux.fr", "password": "admin"}
```

Réponse : `{"token": "..."}`. Ensuite, envoyer l'en-tête `Authorization: Bearer <token>` sur toutes les routes `/api/v1/...`.

## Routes

| Méthode | Route | Rôle | Description |
|---|---|---|---|
| GET | /api/v1/videogames?page=1&limit=3 | connecté | Liste paginée (cache) |
| GET | /api/v1/videogames/{id} | connecté | Un jeu |
| POST | /api/v1/videogames?idCategory=1&idEditor=1 | ADMIN | Créer un jeu |
| PUT | /api/v1/videogames/{id}?idCategory=2&idEditor=3 | ADMIN | Modifier un jeu (idCategory et idEditor facultatifs) |
| DELETE | /api/v1/videogames/{id} | ADMIN | Supprimer (logiquement) un jeu |
| POST | /api/v1/videogames/{id}/cover | ADMIN | Envoyer la jaquette (champ `coverImage`) |
| GET / POST | /api/v1/categories | connecté / ADMIN | Liste / créer |
| GET / PUT / DELETE | /api/v1/categories/{id} | connecté / ADMIN / ADMIN | Une catégorie |
| GET / POST | /api/v1/editors | connecté / ADMIN | Liste / créer |
| GET / PUT / DELETE | /api/v1/editors/{id} | connecté / ADMIN / ADMIN | Un éditeur |
| GET / POST | /api/v1/users | ADMIN | Liste / créer |
| GET / PUT / DELETE | /api/v1/users/{id} | ADMIN | Un utilisateur |
| GET | /api/doc | public | Documentation Swagger |

Toutes les listes acceptent `page` (défaut 1) et `limit` (défaut 3).

## Exemples de corps JSON

Jeu vidéo (catégorie et éditeur passés dans l'URL : `?idCategory=1&idEditor=2`) :

```json
{"title": "Mon jeu", "releaseDate": "2026-10-20", "description": "Une description d'au moins 10 caractères"}
```

Catégorie : `{"name": "Action"}`

Éditeur : `{"name": "Sega", "country": "Japon"}`

Utilisateur :

```json
{"email": "nouveau@jeux.fr", "password": "secret", "roles": [], "subcriptionToNewsletter": true}
```

Le mot de passe est haché avant l'enregistrement. Pour le modifier, renvoyer `password` dans le PUT.

## Jaquette

```bash
curl -X POST -H "Authorization: Bearer TOKEN" -F "coverImage=@jaquette.png" http://127.0.0.1:8000/api/v1/videogames/1/cover
```

L'image (jpg, png, gif ou webp) est enregistrée dans `public/uploads/covers/` avec un nom unique. Elle est accessible sur `/uploads/covers/<coverImage>`.

## Newsletter et Scheduler

Envoi manuel :

```bash
php bin/console app:send-newsletter
```

Le transport des e-mails se règle dans `.env.local` avec `MAILER_DSN` (par exemple celui donné par Mailtrap). Par défaut `null://null` : aucun e-mail ne part vraiment.

Planification (tous les lundis à 8h30, expression CRON `30 8 * * 1`) :

```bash
php bin/console debug:scheduler
php bin/console messenger:consume scheduler_default
```

## Erreurs

Toutes les erreurs sont renvoyées en JSON : `{"status": 404, "message": "..."}`. Les erreurs de validation renvoient un code 400 avec la liste des champs en erreur.
