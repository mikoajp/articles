# 🚀 Articles 

## 💻 Instalacja

### 1. Sklonuj repozytorium
```bash
git clone git@github.com:mikoajp/articles.git
cd articles
```

### 2. Instalacja zależności
#### Backend
```bash
composer install
```
#### Frontend
```bash
npm install
```

### 3. Konfiguracja środowiska

#### Kopiowanie pliku konfiguracyjnego
```bash
cp .env.example .env
```

#### Generowanie klucza aplikacji
```bash
php artisan key:generate
```

#### Skonfiguruj plik `.env`:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=articles
DB_USERNAME=articles_user
DB_PASSWORD=password
```

### 4. Inicjalizacja bazy danych

#### Uruchomienie kontenerów Docker
```bash
docker-compose up -d
```

#### Czyszczenie cache konfiguracji
```bash
php artisan config:clear
```

#### Migracja i wypełnienie bazy danych
```bash
php artisan migrate:fresh
php artisan db:seed
```

### 5. Uruchomienie aplikacji

#### Kompilacja assetsów
```bash
npm run build
```

#### Uruchomienie środowiska developerskiego
```bash
npm run dev
```

#### Uruchomienie serwera Laravel
```bash
php artisan serve
```

