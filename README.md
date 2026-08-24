# Cadim

O Cadim é um sistema pessoal de organização financeira. Ele ajuda a registrar receitas e despesas, acompanhar contas e cartões e planejar o futuro com orçamentos e metas.

Construído com Laravel, Livewire, Tailwind CSS, Vite e PostgreSQL.

## Funcionalidades

- Dashboard com receitas, despesas, saldos e evolução financeira por período.
- Lançamentos únicos, recorrentes e parcelados.
- Confirmação, cancelamento, edição, duplicação e exclusão de lançamentos.
- Projeção de ocorrências futuras para séries recorrentes.
- Contas financeiras com saldo realizado, projetado e consolidado.
- Cartões de crédito com limite comprometido, limite disponível e faturas atual e seguinte.
- Pagamento e desfazimento de faturas, com data e valor registrados.
- Categorias separadas para receitas e despesas.
- Transferências entre contas sem distorcer os totais consolidados.
- Orçamentos mensais, recorrentes e alertas de acompanhamento.
- Metas financeiras com progresso e prazo opcional.
- Importação de transações por CSV e exportação dos lançamentos.
- Deduplicação automática ao reimportar um CSV.
- Notificações financeiras persistentes, com controle de leitura.
- Autenticação, onboarding, termos de uso e central de suporte.

## Requisitos

Para o fluxo recomendado, instale apenas:

- Docker com Docker Compose;
- Git.

Para executar sem Docker, também são necessários PHP 8.3+, Composer, Node.js/npm e PostgreSQL.

## Executar com Docker

Clone o projeto e suba os serviços:

```bash
git clone <url-do-repositorio>
cd bank-management-system
docker compose -f docker/compose.yaml up --build -d
```

O container da aplicação gera a chave e executa as migrations na inicialização. Acesse:

- Aplicação: <http://localhost:5101>
- Vite: <http://localhost:5102>
- PostgreSQL: `localhost:5433`

Para criar um usuário e dados de demonstração, execute uma vez:

```bash
docker compose -f docker/compose.yaml exec app php artisan db:seed --force
```

O seed cria o usuário `admin@example.com` com a senha `password`, além de categorias e dados financeiros de exemplo. Não use essas credenciais em produção.

Com os serviços já configurados, os atalhos equivalentes são:

```bash
composer run dev        # inicia os serviços
composer run dev:build  # reconstrói as imagens e inicia os serviços
composer run docker     # atalho para o Docker Compose
```

O serviço `scheduler` permanece ativo e executa a materialização de transações recorrentes todos os dias às 02:00.

## Configuração

Na execução local sem Docker, copie o arquivo de ambiente e ajuste as credenciais do banco:

```bash
cp .env.example .env
composer install
npm ci
php artisan key:generate
php artisan migrate
```

O endereço padrão do PostgreSQL local é `127.0.0.1:5432`, com banco `verde_financas`. Para consultar automaticamente a bandeira de cartões, defina também `API_NINJAS_API_KEY`; sem essa chave, o detector local continua funcionando.

Depois, inicie a aplicação e o Vite em terminais separados:

```bash
php artisan serve
npm run dev
```

## Comandos úteis

Os comandos abaixo usam o container `app` quando executados via Composer:

```bash
composer test             # testes PHPUnit/Pest
composer analyse          # PHPStan/Larastan
composer pint:check       # verifica formatação PHP
composer pint             # aplica formatação PHP
npm run build             # build dos assets
php artisan test --compact # executa a suíte local sem Docker
```

Para gerar manualmente ocorrências futuras de séries ativas:

```bash
docker compose -f docker/compose.yaml exec app php artisan transactions:materialize
docker compose -f docker/compose.yaml exec app php artisan transactions:materialize --months=6
docker compose -f docker/compose.yaml exec app php artisan transactions:materialize --until=2026-12-31
```

As opções `--months` e `--until` são alternativas. O comando é idempotente e não duplica ocorrências já materializadas.

## Testes E2E

Com o ambiente Docker em execução, rode o Playwright no perfil dedicado:

```bash
docker compose -f docker/compose.yaml --profile e2e run --rm playwright
```

Para abrir a interface do Playwright localmente:

```bash
npm run test:e2e:ui
```

O pipeline em `.github/workflows/quality.yml` valida testes PHP, PHPStan, Pint e o build dos assets em cada pull request.

Em produção, configure backups automatizados do PostgreSQL, monitore os logs da aplicação e mantenha `APP_DEBUG=false`. As credenciais de demonstração documentadas neste arquivo são apenas para desenvolvimento.

## Produção

Crie o arquivo de ambiente de produção a partir do exemplo, defina domínio, chave da aplicação e credenciais fortes do PostgreSQL:

```bash
cp docker/.env.example docker/.env
docker compose --env-file docker/.env -f docker/compose.prod.yaml up -d --build
```

Antes do primeiro acesso, revise `APP_DOMAIN`, `APP_KEY`, `POSTGRES_DB`, `POSTGRES_USER` e `POSTGRES_PASSWORD`. Não versionar arquivos `.env` nem senhas.

## Estrutura principal

```text
app/
├── Livewire/       # páginas e componentes interativos
├── Models/         # entidades do domínio
├── Services/       # regras financeiras e integrações
└── Console/        # comandos Artisan
database/
├── migrations/     # estrutura do banco
└── seeders/        # categorias e dados de demonstração
resources/
├── views/          # layouts e telas Blade/Livewire
├── css/            # estilos Tailwind
└── js/             # entrada do Vite
tests/
├── Feature/        # regras de negócio e páginas
└── E2E/            # fluxos completos no navegador
```
