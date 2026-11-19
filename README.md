# Casa em Dia

## Instalação

Requisitos: PHP 8.4, Composer, Node.js, PostgreSQL 15+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD` no `.env`. Crie o banco antes de migrar.

## Configuração e execução

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Acesse `http://127.0.0.1:8000` e crie sua conta. Para desenvolvimento com recarga automática, use `npm run dev` em outro terminal.

## Testes e qualidade

```bash
php artisan test
vendor/bin/pint
vendor/bin/phpstan analyse
```
