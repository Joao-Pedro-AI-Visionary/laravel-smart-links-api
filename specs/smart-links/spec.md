# Spec — Smart Links & Asynchronous Telemetry (`specs/smart-links/spec.md`)

## Modelos e Banco de Dados
1. `short_links`:
   - `id` (bigint PK)
   - `slug` (string unique indexed)
   - `title` (string)
   - `target_url` (text)
   - `utm_source` (string nullable)
   - `utm_medium` (string nullable)
   - `webhook_url` (text nullable)
   - `expires_at` (timestamp nullable)
   - `clicks_count` (unsigned bigint default 0)
   - `timestamps`
2. `link_clicks`:
   - `id` (bigint PK)
   - `short_link_id` (foreignId cascadeOnDelete)
   - `visitor_hash` (string 64 indexed)
   - `device_type` (string 32 indexed — `mobile | desktop | bot`)
   - `referer_host` (string nullable)
   - `user_agent` (string nullable)
   - `clicked_at` (timestamp indexed)

## Regras de Domínio
- **Construção de URL de destino:** Se `utm_source` ou `utm_medium` estiverem preenchidos no `ShortLink`, anexa os query parameters preservando eventuais query params já existentes na `target_url`.
- **Privacidade por Design (LGPD):** O endereço IP nunca é persistido em texto puro; apenas `hash('sha256', $ip . '|' . $userAgent . '|' . $date)` é salvo em `visitor_hash` para contagem de visitantes únicos diários.
- **Processamento em Fila:** `RecordLinkClickJob` roda em background (`ShouldQueue`), registra o clique em transação atômica (`DB::transaction`), incrementa `clicks_count` e, se `webhook_url` estiver presente, envia payload JSON com timeout de 3s.
