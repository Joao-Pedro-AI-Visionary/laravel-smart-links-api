# Project Brief — Laravel Smart Links & Click Telemetry API

## 1. Problema e Objetivo
Desenvolvedores e equipes de produto precisam criar links curtos rastreáveis para portfólios, currículos e campanhas, com redirecionamento de baixíssima latência (`< 20ms`), contabilização assíncrona de cliques via fila (`Queue/Jobs`), agregação analítica (cliques totais, visitantes únicos por hash anônimo, distribuição por dispositivo/referer) e disparo de webhooks assíncronos.

## 2. Classificação e Dial
- **Classificação:** API REST de produção para portfólio Backend Pleno.
- **Classe no Dial:** `backend` / `db` — Nível 2 (Contrato + TDD + `test-gate` + `pr-review`).

## 3. Contratos da API (`api/v1`)
- `POST /api/v1/links` — Cria um link encurtado (com `slug` customizado ou gerado automaticamente, `target_url`, `title`, `utm_source`, `utm_medium`, `webhook_url`, `expires_at`).
- `GET /api/v1/links` — Lista links paginados com contagem de cliques.
- `GET /api/v1/links/{slug}/stats` — Retorna telemetria agregada do link (`total_clicks`, `unique_visitors`, `by_device`, `by_referer`, `recent_clicks`).
- `DELETE /api/v1/links/{slug}` — Remove o link e seus eventos associados.
- `GET /r/{slug}` — Redireciona (`302`) para a URL final (anexando parâmetros UTM quando configurados), verifica expiração (`410 Gone` se expirado) e despacha `RecordLinkClickJob` na fila.

## 4. Critérios de Aceitação Verificáveis
- **CA-01:** `POST /api/v1/links` valida URLs inválidas (`422`), rejeita slugs duplicados (`422`) e cria slug único automático quando omitido (`201`).
- **CA-02:** `GET /r/{slug}` retorna `302` anexando `utm_source`/`utm_medium` se definidos no link, ou `410` se `expires_at` estiver no passado.
- **CA-03:** O acesso a `GET /r/{slug}` processa `RecordLinkClickJob`, incrementando `clicks_count`, gravando `LinkClick` com hash SHA-256 anônimo do visitante (`visitor_hash`), detectando tipo de dispositivo (`desktop`, `mobile`, `bot`) e disparando POST para `webhook_url` quando configurado.
- **CA-04:** `GET /api/v1/links/{slug}/stats` retorna a agregação exata de cliques totais, visitantes únicos e breakdown por dispositivo.
