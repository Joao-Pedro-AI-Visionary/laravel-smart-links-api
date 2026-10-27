# Laravel Smart Links & Asynchronous Click Telemetry API

API REST em **Laravel** para criação de links curtos rastreáveis, redirecionamento de baixa latência (`302`), processamento assíncrono de telemetria de cliques via **Queues/Jobs**, anonimização de visitantes (LGPD) e disparo de **Webhooks**.

---

## Destaques de Engenharia (Nível Pleno)

- **Redirecionamento Desacoplado com Filas (`ShouldQueue`):**
  - O endpoint `GET /r/{slug}` valida a existência e expiração (`410 Gone`) e despacha `RecordLinkClickJob`, mantendo o tempo de resposta de redirecionamento mínimo.
- **Transações Atômicas & Consistência:**
  - `RecordLinkClickJob` grava o evento em `link_clicks` e incrementa `clicks_count` dentro de `DB::transaction(...)`, garantindo integridade mesmo sob alta concorrência.
- **Privacidade por Design (LGPD):**
  - Nenhum endereço IP é armazenado em texto puro; o sistema gera um `visitor_hash` diário (`SHA-256` de `IP + User-Agent + Data`) para contabilizar visitantes únicos sem reter PII.
- **Injeção Automática de UTM & Webhooks Não-Bloqueantes:**
  - `UrlDestinationBuilder` preserva query strings existentes na URL de destino ao anexar `utm_source` e `utm_medium`.
  - Se o link possuir `webhook_url`, o worker dispara um `POST` JSON com timeout de 3 segundos e isolamento de falhas.

---

## Endpoints da API

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/api/v1/links` | Cria um link curto (com `slug` customizado ou gerado automaticamente) |
| `GET` | `/api/v1/links` | Lista os links cadastrados com paginação |
| `GET` | `/api/v1/links/{slug}/stats` | Retorna métricas agregadas (`total_clicks`, `unique_visitors`, `by_device`, `by_referer`) |
| `DELETE` | `/api/v1/links/{slug}` | Remove o link e sua telemetria em cascata |
| `GET` | `/r/{slug}` | Redireciona (`302`) para o destino final e enfileira `RecordLinkClickJob` |

### Exemplo de Criação (`POST /api/v1/links`)

```bash
curl -X POST http://localhost:8000/api/v1/links \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Portfólio GitHub",
    "slug": "meu-github",
    "target_url": "https://github.com/Joao-Pedro-AI-Visionary",
    "utm_source": "linkedin",
    "utm_medium": "profile",
    "webhook_url": "https://hooks.example.com/click-events"
  }'
```

### Exemplo de Resposta de Estatísticas (`GET /api/v1/links/meu-github/stats`)

```json
{
  "data": {
    "slug": "meu-github",
    "title": "Portfólio GitHub",
    "target_url": "https://github.com/Joao-Pedro-AI-Visionary",
    "total_clicks": 2,
    "unique_visitors": 2,
    "by_device": {
      "desktop": 1,
      "mobile": 1,
      "bot": 0
    },
    "by_referer": {
      "www.linkedin.com": 1,
      "github.com": 1
    },
    "is_expired": false
  }
}
```

---

## Como Executar

### Opção 1: Ambiente Nativo (PHP + Composer + SQLite)
```bash
composer install
cp -n .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate

# Rodar a suíte de testes automatizados (Feature + Unit)
php artisan test

# Iniciar servidor
php artisan serve
```

### Opção 2: Docker & Docker Compose
```bash
docker compose up --build
```
