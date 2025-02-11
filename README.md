🚀 Szybki start
📋 Wymagania systemowe

PHP 8.1 lub wyższy
Node.js & NPM
Docker & Docker Compose
Composer

💻 Instalacja
1. Sklonuj repozytorium
[bashCopygit clone https://github.com/twoj-projekt/articles-system.git](https://github.com/mikoajp/articles)
cd articles
2. Instalacja zależności backendowych
composer install
3. Instalacja zależności frontendowych
npm install
5. Konfiguracja środowiska
Kopiowanie pliku konfiguracyjnego
cp .env.example .env

# Generowanie klucza aplikacji
php artisan key:generate
Skonfiguruj plik .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=articles
DB_USERNAME=articles_user
DB_PASSWORD=password
5. Inicjalizacja bazy danych
Uruchomienie kontenerów Docker
docker-compose up -d

# Czyszczenie cache konfiguracji
php artisan config:clear

# Migracja i wypełnienie bazy danych
php artisan migrate:fresh
php artisan db:seed
6. Uruchomienie aplikacji
Kompilacja assetsów
npm run build

# Uruchomienie środowiska developerskiego
npm run dev

# Uruchomienie serwera Laravel
php artisan serve
