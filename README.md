# README

## Setup

Follow the instructions in [docs/SETUP.md](docs/SETUP.md).

## Environment

The application requires the following environment variables:

- `APP_KEY`
- `APP_DEBUG`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `MAIL_MAILER`
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT`
- `GEMINI_API_KEY`
- `TMDB_API_KEY`
- `MIDTRANS_SERVER_KEY`
- `MIDTRANS_CLIENT_KEY`
- `MIDTRANS_IS_PRODUCTION`
- `MIDTRANS_IS_SANITIZED`
- `MIDTRANS_NOTIFICATION_URL`
- `MIDTRANS_FINISHED_URL`
- `MIDTRANS_UNFINISHED_URL`
- `MIDTRANS_ERROR_URL`
- `PUSHER_APP_ID`
- `PUSHER_APP_KEY`
- `PUSHER_APP_SECRET`
- `PUSHER_APP_CLUSTER`
- `VITE_PUSHER_APP_KEY`
- `VITE_PUSHER_APP_CLUSTER`
- `VITE_GEMINI_API_KEY`
- `VITE_TMDB_API_KEY`
- `VITE_MIDTRANS_CLIENT_KEY`
- `VITE_MIDTRANS_SERVER_KEY`
- `VITE_MIDTRANS_IS_PRODUCTION`
- `VITE_MIDTRANS_IS_SANITIZED`
- `VITE_MIDTRANS_NOTIFICATION_URL`
- `VITE_MIDTRANS_FINISHED_URL`
- `VITE_MIDTRANS_UNFINISHED_URL`
- `VITE_MIDTRANS_ERROR_URL`
- `VITE_PUSHER_APP_ID`
- `VITE_PUSHER_APP_KEY`
- `VITE_PUSHER_APP_SECRET`
- `VITE_PUSHER_APP_CLUSTER`
- `VITE_GOOGLE_CLIENT_ID`
- `VITE_GOOGLE_CLIENT_SECRET`
- `VITE_GOOGLE_REDIRECT`
- `VITE_APP_URL`
- `VITE_APP_DEBUG`
- `VITE_APP_KEY`
- `VITE_APP_NAME`
- `VITE_APP_ENV`
- `VITE_APP_TIMEZONE`
- `VITE_APP_LOCALE`
- `VITE_APP_FALLBACK_LOCALE`
- `VITE_APP_URL`
- `VITE_APP_DEBUG`
- `VITE_APP_KEY`
- `VITE_APP_NAME`
- `VITE_APP_ENV`
- `VITE_APP_TIMEZONE`
- `VITE_APP_LOCALE`
- `VITE_APP_FALLBACK_LOCALE`

## Commands

- `composer install`
- `npm install`
- `npm run dev`
- `php artisan migrate`
- `php artisan serve`

## Architecture Overview

The application follows the Laravel framework's architecture. Key components include:

- **Controllers**: Handle incoming requests and return responses.
- **Models**: Represent the data structure and interact with the database.
- **Views**: Define the user interface using Blade templates.
- **Routes**: Define the application's endpoints and map them to controllers.
- **Services**: Contain business logic and interact with external APIs.
- **Middleware**: Handle cross-cutting concerns like authentication and authorization.

## Tests

Run the tests using the following command:

```bash
php artisan test
```

## Demo Steps

1. Clone the repository.
2. Follow the setup instructions in [docs/SETUP.md](docs/SETUP.md).
3. Run the application using `php artisan serve`.
4. Access the application in your browser at `http://localhost:8000`.
5. Explore the features and functionality of the application.