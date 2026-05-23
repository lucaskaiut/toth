# Toth

Monorepo da aplicação CRM **Toth**.

## Estrutura

| Pacote | Descrição |
|--------|-----------|
| `web/` | Painel web (React Router 7, React 19, Tailwind CSS 4) |
| `api/` | API backend (Laravel 13, PHP 8.4) |

## Stack Docker

| Serviço | Imagem | Porta | Descrição |
|---------|--------|-------|-----------|
| `web` | `node:24-alpine` | 5173 | Painel React |
| `nginx` | `nginx:1.27-alpine` | 8080 | Proxy HTTP da API |
| `api` | PHP 8.4 FPM (build local) | — | Laravel 13 |
| `mysql` | `mysql:8.4` | 3306 | Banco de dados |
| `redis` | `redis:8.4-alpine` | 6379 | Cache, sessões e filas |
| `mailhog` | `mailhog/mailhog:v1.0.1` | 8025 (UI), 1025 (SMTP) | E-mail de desenvolvimento |

## Subir tudo

Na raiz do repositório:

```bash
docker compose up -d
```

- Painel web: http://localhost:5173
- API Laravel: http://localhost:8080
- Mailhog: http://localhost:8025

## Comandos via Docker

Todos os comandos dos serviços rodam **dentro dos containers**:

```bash
# Web
docker compose exec web npm install
docker compose exec web npm run build

# API
docker compose exec api composer install
docker compose exec api php artisan migrate
docker compose exec api php artisan test
```

## API (`api/`)

Stack: Laravel 13.11, PHP 8.4, MySQL 8.4, Redis 8.4, Mailhog, Nginx.

Credenciais padrão de desenvolvimento (`.env`):

- **MySQL:** host `mysql`, database `toth`, user `toth`, password `secret`
- **Redis:** host `redis`, port `6379`
- **Mail:** host `mailhog`, port `1025`
