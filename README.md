# FilmLog 🎬

A movie watchlist and review platform inspired by Letterboxd. FilmLog allows users to discover movies, manage their watchlists, track watched movies, write reviews, and receive AI-powered movie recommendations based on their preferences.

## Features

- **User Authentication** — Register, log in, and log out using JWT authentication.
- **Movie Discovery** — Browse the available movie catalogue and view individual movie details.
- **Watchlist Management** — Add movies to or remove movies from your personal watchlist.
- **Watched Movies** — Mark movies as watched and keep track of your viewing history.
- **Ratings and Reviews** — Rate watched movies and write personal reviews.
- **AI Movie Recommendations** — Get personalized recommendations using Google's Gemini API, grounded in the movie catalogue stored in the database.

## Tech Stack

| Component | Technology |
|---|---|
| Backend | PHP, Laravel 12 |
| Frontend | HTML, CSS, Vanilla JavaScript |
| Database | SQLite |
| Authentication | JWT (Laravel JWT Auth) |
| AI Integration | Google Gemini API |
| Development Tools | VS Code, Composer, Git |

## Project Structure

```text
movie-watchlist-starter/
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   └── Models/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── database.sqlite
│   ├── routes/
│   │   └── api.php
│   ├── .env.example
│   └── ...
│
├── frontend/
│   ├── app.js
│   ├── index.html
│   └── ...
│
└── README.md
```

## Getting Started

### Prerequisites

Make sure you have installed:

- PHP 8.2 or newer
- Composer
- SQLite
- Node.js (only if needed for optional frontend tooling)
- Visual Studio Code with the Live Server extension

### 1. Clone the Repository

```bash
git clone https://github.com/altschrei/filmlog.git
cd filmlog
```

### 2. Set Up the Backend

Navigate to the backend directory:

```bash
cd backend
```

Install PHP dependencies:

```bash
composer install
```

Create your environment configuration:

```bash
cp .env.example .env
```

On Windows PowerShell, you can use:

```powershell
Copy-Item .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Create the SQLite database file if it does not already exist:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force
```

Configure the database in your `.env` file:

```env
DB_CONNECTION=sqlite
```

Configure JWT authentication:

```bash
php artisan jwt:secret
```

Run database migrations and seed the movie catalogue:

```bash
php artisan migrate --seed
```

If your project uses a separate movie seeder, run that seeder according to its class name.

### 3. Configure Gemini API

FilmLog uses Google's Gemini API to generate personalized movie recommendations.

Obtain an API key from [Google AI Studio](https://aistudio.google.com/).

Add the following configuration to your backend `.env` file:

```env
GEMINI_API_KEY=your_gemini_api_key
GEMINI_MODEL=gemini-3.8-flash
```

Keep your API key private. Do not commit your `.env` file or expose the key in frontend JavaScript.

The backend reads the API key from the environment and makes requests to Gemini. Movie recommendations are grounded in the existing movie catalogue rather than generated from an unrestricted list of movie titles.

### 4. Start the Backend Server

From the `backend` directory:

```bash
php artisan serve
```

The backend should be available at:

```text
http://127.0.0.1:8000
```

### 5. Start the Frontend

Open the `frontend` directory in Visual Studio Code.

Right-click `index.html` and select **Open with Live Server**.

The frontend will open in your browser. It communicates with the Laravel backend running locally.

## API Endpoints

All API endpoints use the `/api` prefix.

### Authentication

| Method | Endpoint | Description | Authentication |
|---|---|---|---|
| POST | `/api/auth/register` | Register a new user | No |
| POST | `/api/auth/login` | Log in | No |
| POST | `/api/auth/logout` | Log out | Yes |

### Movies

| Method | Endpoint | Description | Authentication |
|---|---|---|---|
| GET | `/api/movies` | Retrieve the movie catalogue | No |
| GET | `/api/movies/{id}` | Retrieve movie details | No |
| POST | `/api/movies/recommend` | Generate AI movie recommendations | Yes |

### Watchlist

| Method | Endpoint | Description | Authentication |
|---|---|---|---|
| GET | `/api/watchlist` | Retrieve the user's watchlist | Yes |
| POST | `/api/watchlist` | Add a movie to the watchlist | Yes |
| DELETE | `/api/watchlist/{movieId}` | Remove a movie from the watchlist | Yes |

### Watched Movies

| Method | Endpoint | Description | Authentication |
|---|---|---|---|
| GET | `/api/watched` | Retrieve watched movies | Yes |
| POST | `/api/watched` | Mark a movie as watched and submit a rating or review | Yes |

Protected endpoints require a valid JWT authentication token.

## AI Recommendation System

FilmLog integrates Google's Gemini API to provide personalized movie recommendations.

The recommendation process works as follows:

1. The user submits their movie preferences through the frontend.
2. The frontend sends the preferences to the Laravel backend.
3. The backend retrieves the available movies from the local database.
4. The backend sends the movie catalogue and user preferences to Gemini.
5. Gemini selects suitable movies from the provided catalogue and returns movie IDs with explanations.
6. The backend validates the returned IDs against the database.
7. The frontend displays the recommended movies and their explanations.

This approach grounds the AI's recommendations in the application's actual movie data and helps prevent recommendations for movies that do not exist in the catalogue.

**Note:** AI recommendations require a valid Gemini API key and are subject to the API provider's usage limits and availability.

## Database

FilmLog uses SQLite for data storage.

The main data entities include:

- **Users** — Stores registered user accounts.
- **Movies** — Stores movie information, including titles, descriptions, release years, genres, directors, and poster URLs.
- **Watcheds** — Stores user-specific watched movie records, including ratings and reviews.
- **Watchlist** — Stores the relationship between users and movies they want to watch.

User-related movie records are associated with their respective users, allowing each user to maintain an independent watchlist and viewing history.

## Security and Error Handling

FilmLog implements several backend security practices:

- JWT authentication for protected API endpoints.
- Server-side validation of incoming requests.
- Authentication middleware to restrict access to user-specific resources.
- Environment variables for sensitive configuration, including the Gemini API key.
- Backend validation of AI-generated movie IDs against the database.

## Future Improvements

Potential improvements include:

- Automated backend and API tests.
- More advanced movie filtering and search.
- Improved recommendation personalization based on viewing history.
- Deployment to a production hosting environment.
- More comprehensive error handling and API documentation.

## Author

**Raihan Aframadhan**

GitHub: [@altschrei](https://github.com/altschrei)

---

*FilmLog — Discover, track, and review the movies you love.*
