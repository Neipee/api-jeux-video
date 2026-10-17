# API Jeux vidéo — TP Symfony

TP « Créer une API Symfony » (+ suite) avec Symfony 7.4.

## Installation

```bash
composer install
```

Créer `.env.local` avec l'accès à la base :

```env
DATABASE_URL="mysql://root:MOT_DE_PASSE@127.0.0.1:3306/api_jeux_video?serverVersion=8.0.45&charset=utf8mb4"
```

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php bin/console lexik:jwt:generate-keypair
php bin/console assets:install
symfony serve
```

## Utilisation

- Token : `POST /api/login_check` avec `{"username": "admin@jeux.fr", "password": "admin"}` (admin) ou `user1@jeux.fr` / `user`.
- Routes : `/api/v1/videogames`, `/api/v1/categories`, `/api/v1/editors`, `/api/v1/users` (pagination `?page=1&limit=3`).
- Création / modification d'un jeu : catégorie et éditeur dans l'URL (`?idCategory=1&idEditor=1`).
- Jaquette : `POST /api/v1/videogames/{id}/cover` en `multipart/form-data` (champ `coverImage`).
- Documentation : `/api/doc`.
- Newsletter : `php bin/console app:send-newsletter`, planifiée tous les lundis à 8h30 (`php bin/console messenger:consume scheduler_default`).
