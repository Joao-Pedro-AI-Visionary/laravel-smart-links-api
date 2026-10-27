<!doctype html>
<html lang="pt-BR" class="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%23090D16'/><text x='50%25' y='55%25' dominant-baseline='middle' text-anchor='middle' fill='%2310B981' font-family='monospace' font-weight='bold' font-size='14'>SL</text></svg>" />
  <title>SmartLinks API // Laravel 12 Queue Telemetry & Webhook Console</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#090D16] text-slate-100 antialiased">
  <header class="border-b border-slate-800 bg-[#0F172A]">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-lg border border-emerald-500/40 bg-emerald-500/10 font-mono text-sm font-bold text-emerald-400">
          SL
        </div>
        <div>
          <span class="font-mono text-xs font-bold tracking-wider text-emerald-400">
            SMART_LINKS_API // LARAVEL 12 + QUEUE TELEMETRY
          </span>
          <h1 class="text-lg font-bold text-slate-50">
            Smart URL Shortener, SSRF-Guarded Webhooks & Click Analytics Console
          </h1>
        </div>
      </div>
      <span class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 font-mono text-xs text-slate-300">
        REST API v1 + LGPD SHA-256 Visitor Hash
      </span>
    </div>
  </header>

  <main class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-4 py-6 lg:grid-cols-12">
    <!-- Left: Create / Update ShortLink Form -->
    <section class="space-y-4 rounded-xl border border-slate-800 bg-[#0F172A] p-5 lg:col-span-5">
      <h2 id="form-heading" class="text-base font-semibold text-slate-100">
        Criar / Editar Link Curto (CRUD + Constraints)
      </h2>

      <p id="api-error-alert" data-testid="api-error-alert" class="hidden rounded-lg border border-rose-500/40 bg-rose-500/15 px-3 py-2 text-xs text-rose-200"></p>
      <p id="api-success-alert" data-testid="api-success-alert" class="hidden rounded-lg border border-emerald-500/40 bg-emerald-500/15 px-3 py-2 text-xs text-emerald-200"></p>

      <form id="link-form" data-testid="link-form" class="space-y-3" novalidate>
        <input type="hidden" id="editing-slug" value="" />
        <div>
          <label class="block text-xs text-slate-300">Título do Link *</label>
          <input id="input-title" data-testid="input-title" type="text" placeholder="Ex: Portfólio GitHub Pleno" class="mt-1 min-h-[48px] w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
        </div>
        <div>
          <label class="block text-xs text-slate-300">Slug Customizado (opcional, único)</label>
          <input id="input-slug" data-testid="input-slug" type="text" placeholder="Ex: github-pleno" class="mt-1 min-h-[48px] w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
        </div>
        <div>
          <label class="block text-xs text-slate-300">URL de Destino (http/https) *</label>
          <input id="input-target-url" data-testid="input-target-url" type="text" placeholder="https://github.com/Joao-Pedro-AI-Visionary" class="mt-1 min-h-[48px] w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs text-slate-300">UTM Source</label>
            <input id="input-utm-source" data-testid="input-utm-source" type="text" placeholder="linkedin" class="mt-1 min-h-[48px] w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
          </div>
          <div>
            <label class="block text-xs text-slate-300">UTM Medium</label>
            <input id="input-utm-medium" data-testid="input-utm-medium" type="text" placeholder="recruiter" class="mt-1 min-h-[48px] w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
          </div>
        </div>
        <div>
          <label class="block text-xs text-slate-300">Webhook URL (Protegido contra SSRF)</label>
          <input id="input-webhook-url" data-testid="input-webhook-url" type="text" placeholder="https://hooks.example.com/click" class="mt-1 min-h-[48px] w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100" />
        </div>
        <button id="submit-link-btn" data-testid="submit-link-btn" type="submit" class="min-h-[48px] w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-950 hover:bg-emerald-400">
          Salvar Link Curto
        </button>
      </form>
    </section>

    <!-- Right: Links Table & Telemetry Inspector -->
    <section class="space-y-4 rounded-xl border border-slate-800 bg-[#0F172A] p-5 lg:col-span-7">
      <div class="flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-100">Links Ativos & Telemetria em Fila</h2>
        <button id="refresh-links-btn" data-testid="refresh-links-btn" type="button" class="min-h-[48px] rounded-lg border border-slate-700 bg-slate-900 px-3.5 py-2 text-xs text-slate-200">
          Atualizar Lista
        </button>
      </div>

      <div id="stats-panel" data-testid="stats-panel" class="hidden rounded-xl border border-emerald-500/40 bg-slate-950 p-4">
        <p class="font-mono text-xs text-emerald-400 uppercase">Telemetria Consolidada</p>
        <div id="stats-content" class="mt-2 font-mono text-xs text-slate-200"></div>
      </div>

      <div id="links-list" data-testid="links-list" class="space-y-3"></div>
    </section>
  </main>

  <script>
    const errorEl = document.getElementById('api-error-alert');
    const successEl = document.getElementById('api-success-alert');
    const linksListEl = document.getElementById('links-list');
    const statsPanelEl = document.getElementById('stats-panel');
    const statsContentEl = document.getElementById('stats-content');
    const editingSlugEl = document.getElementById('editing-slug');

    function showError(msg) {
      errorEl.textContent = msg;
      errorEl.classList.remove('hidden');
      successEl.classList.add('hidden');
    }

    function showSuccess(msg) {
      successEl.textContent = msg;
      successEl.classList.remove('hidden');
      errorEl.classList.add('hidden');
    }

    async function loadLinks() {
      const res = await fetch('/api/v1/links', { headers: { Accept: 'application/json' } });
      const json = await res.json();
      const items = json.data || [];
      linksListEl.innerHTML = items.map(item => `
        <article data-testid="link-item-${item.slug}" class="rounded-lg border border-slate-800 bg-slate-950 p-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <span class="font-mono text-xs text-emerald-400">/r/${item.slug}</span>
              <h3 data-testid="link-title-${item.slug}" class="text-sm font-semibold text-slate-100">${item.title}</h3>
              <p class="text-xs text-slate-400 break-all">${item.target_url}</p>
            </div>
            <span data-testid="link-clicks-${item.slug}" class="rounded bg-emerald-500/20 px-2.5 py-1 font-mono text-xs font-bold text-emerald-300">
              ${item.clicks_count} clique(s)
            </span>
          </div>
          <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" data-testid="simulate-click-${item.slug}" onclick="simulateClick('${item.slug}')" class="min-h-[40px] rounded border border-emerald-500/40 bg-emerald-500/15 px-3 py-1 text-xs text-emerald-300">
              Simular Clique (/r/${item.slug})
            </button>
            <button type="button" data-testid="inspect-stats-${item.slug}" onclick="inspectStats('${item.slug}')" class="min-h-[40px] rounded border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-200">
              Ver Stats
            </button>
            <button type="button" data-testid="edit-link-${item.slug}" onclick='startEdit(${JSON.stringify(item)})' class="min-h-[40px] rounded border border-amber-500/40 bg-amber-500/15 px-3 py-1 text-xs text-amber-300">
              Editar
            </button>
            <button type="button" data-testid="delete-link-${item.slug}" onclick="deleteLink('${item.slug}')" class="min-h-[40px] rounded border border-rose-500/40 bg-rose-500/15 px-3 py-1 text-xs text-rose-300">
              Excluir
            </button>
          </div>
        </article>
      `).join('');
    }

    async function simulateClick(slug) {
      await fetch(`/r/${slug}`, { redirect: 'manual' });
      await loadLinks();
      await inspectStats(slug);
    }

    async function inspectStats(slug) {
      const res = await fetch(`/api/v1/links/${slug}/stats`, { headers: { Accept: 'application/json' } });
      if (!res.ok) return;
      const { data } = await res.json();
      statsPanelEl.classList.remove('hidden');
      statsContentEl.textContent = `Slug: ${data.slug} | Cliques Totais: ${data.total_clicks} | Visitantes Únicos: ${data.unique_visitors} | Desktop: ${data.by_device.desktop} | Mobile: ${data.by_device.mobile}`;
    }

    function startEdit(item) {
      editingSlugEl.value = item.slug;
      document.getElementById('input-title').value = item.title;
      document.getElementById('input-slug').value = item.slug;
      document.getElementById('input-target-url').value = item.target_url;
      document.getElementById('input-utm-source').value = item.utm_source || '';
      document.getElementById('input-utm-medium').value = item.utm_medium || '';
      document.getElementById('input-webhook-url').value = item.webhook_url || '';
      document.getElementById('submit-link-btn').textContent = 'Atualizar Link Curto';
    }

    async function deleteLink(slug) {
      await fetch(`/api/v1/links/${slug}`, { method: 'DELETE', headers: { Accept: 'application/json' } });
      showSuccess(`Link ${slug} removido com sucesso.`);
      await loadLinks();
    }

    document.getElementById('link-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const editingSlug = editingSlugEl.value;
      const payload = {
        title: document.getElementById('input-title').value,
        slug: document.getElementById('input-slug').value || null,
        target_url: document.getElementById('input-target-url').value,
        utm_source: document.getElementById('input-utm-source').value || null,
        utm_medium: document.getElementById('input-utm-medium').value || null,
        webhook_url: document.getElementById('input-webhook-url').value || null,
      };

      const url = editingSlug ? `/api/v1/links/${editingSlug}` : '/api/v1/links';
      const method = editingSlug ? 'PUT' : 'POST';

      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify(payload)
      });

      const json = await res.json().catch(() => ({}));
      if (!res.ok) {
        const firstError = json.errors ? Object.values(json.errors).flat()[0] : (json.message || 'Erro de validação');
        showError(`Constraint 422: ${firstError}`);
        return;
      }

      editingSlugEl.value = '';
      document.getElementById('link-form').reset();
      document.getElementById('submit-link-btn').textContent = 'Salvar Link Curto';
      showSuccess(editingSlug ? 'Link atualizado com sucesso!' : 'Link criado com sucesso!');
      await loadLinks();
    });

    document.getElementById('refresh-links-btn').addEventListener('click', loadLinks);
    loadLinks();
  </script>
</body>
</html>
