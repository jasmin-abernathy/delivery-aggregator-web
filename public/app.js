(() => {
  'use strict';

  const STORAGE_KEY = 'sansEffort.observations.v1';
  const MAX_OBSERVATIONS = 100;
  const FRESH_MS = 2 * 60 * 60 * 1000;
  const PLATFORMS = {
    'uber-eats': { label: 'Uber Eats', hosts: ['ubereats.com'] },
    deliveroo: { label: 'Deliveroo', hosts: ['deliveroo.fr', 'deliveroo.com'] }
  };
  const form = document.querySelector('#offerForm');
  const formError = document.querySelector('#formError');
  const storageStatus = document.querySelector('#storageStatus');
  const list = document.querySelector('#comparisonList');
  const emptyState = document.querySelector('#emptyState');
  const filterInput = document.querySelector('#filterInput');
  const comparisonStatus = document.querySelector('#comparisonStatus');
  let observations = [];
  let storageAvailable = true;

  function readStorage() {
    try {
      const saved = window.localStorage.getItem(STORAGE_KEY);
      const parsed = saved ? JSON.parse(saved) : [];
      observations = Array.isArray(parsed) ? parsed.filter(isValidObservation).slice(0, MAX_OBSERVATIONS) : [];
    } catch (_) {
      observations = [];
      storageAvailable = false;
    }
  }

  function isValidObservation(item) {
    return item && typeof item.id === 'string' && typeof item.scenario === 'string'
      && typeof item.merchant === 'string' && Object.hasOwn(PLATFORMS, item.platform)
      && Number.isFinite(item.total) && item.total > 0 && Number.isFinite(Date.parse(item.createdAt));
  }

  function isFresh(item) {
    const age = Date.now() - Date.parse(item.createdAt);
    return age >= 0 && age <= FRESH_MS;
  }

  function persist() {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(observations));
      storageAvailable = true;
    } catch (_) {
      storageAvailable = false;
    }
    storageStatus.textContent = storageAvailable
      ? 'Enregistré dans ce navigateur.'
      : 'Stockage local indisponible : garde cette page ouverte pour conserver la comparaison.';
    storageStatus.classList.toggle('storage-warning', !storageAvailable);
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[character]);
  }

  function safeOfferUrl(value, platform) {
    if (!value) return '';
    try {
      const url = new URL(value);
      const hosts = PLATFORMS[platform].hosts;
      if (url.protocol !== 'https:' || url.username || url.password || url.port
        || !hosts.some((host) => url.hostname === host || url.hostname.endsWith(`.${host}`))) return '';
      return url.href;
    } catch (_) {
      return '';
    }
  }

  function normalize(value) {
    return String(value).trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('fr');
  }

  function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
  }

  function formatDate(iso) {
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso));
  }

  function groupKey(item) {
    return `${normalize(item.scenario)}\u0000${normalize(item.merchant)}`;
  }

  function render() {
    const query = normalize(filterInput.value);
    const groups = new Map();
    observations.slice().sort((a, b) => Date.parse(b.createdAt) - Date.parse(a.createdAt)).forEach((item) => {
      const key = groupKey(item);
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key).push(item);
    });

    const matching = [...groups.values()].filter((items) => {
      const text = normalize(`${items[0].scenario} ${items[0].merchant}`);
      return !query || text.includes(query);
    });
    list.innerHTML = matching.map(renderGroup).join('');
    emptyState.hidden = matching.length > 0;
    if (observations.length && !matching.length) {
      emptyState.hidden = false;
      emptyState.innerHTML = '<h3>Aucun relevé correspondant</h3><p>Essaie un autre nom ou commerce dans le filtre.</p>';
    } else if (!observations.length) {
      emptyState.innerHTML = '<h3>Pas encore de relevé</h3><p>Ouvre une des applis, compose un panier réel sans passer commande, puis saisis son total ci-dessus. Aucun prix d’exemple n’est affiché.</p>';
    }
    const fresh = observations.filter(isFresh);
    comparisonStatus.textContent = fresh.length
      ? `${observations.length} relevé${observations.length === 1 ? '' : 's'} enregistré${observations.length === 1 ? '' : 's'} · ${fresh.length} encore valable${fresh.length === 1 ? '' : 's'} pour comparer (moins de 2 h).`
      : observations.length
        ? `${observations.length} relevé${observations.length === 1 ? '' : 's'} enregistré${observations.length === 1 ? '' : 's'} · aucun relevé récent, compare à nouveau dans les applis.`
        : 'Les prix ne sont relevés que lorsque tu les saisis depuis les applis.';
  }

  function renderGroup(items) {
    const fresh = items.filter(isFresh);
    const hasBothPlatforms = new Set(fresh.map((item) => item.platform)).size === 2;
    const cheapestId = hasBothPlatforms ? fresh.reduce((lowest, item) => item.total < lowest.total ? item : lowest).id : null;
    const spread = hasBothPlatforms ? Math.max(...fresh.map((item) => item.total)) - Math.min(...fresh.map((item) => item.total)) : null;
    const first = items[0];
    const cards = items.map((item) => {
      const stale = !isFresh(item);
      const link = safeOfferUrl(item.url, item.platform);
      const details = [item.basket, item.fees].filter(Boolean).map(escapeHtml).join(' · ');
      return `<article class="observation-card${item.id === cheapestId ? ' is-cheapest' : ''}">
        <div class="observation-top"><strong>${escapeHtml(PLATFORMS[item.platform].label)}</strong>${item.id === cheapestId ? '<span class="badge badge-best">Moins cher relevé</span>' : ''}${stale ? '<span class="badge badge-stale">À actualiser</span>' : ''}</div>
        <p class="total">${formatMoney(item.total)}</p>
        <dl class="facts"><div><dt>Délai</dt><dd>${item.eta ? `${escapeHtml(item.eta)} min` : 'Non indiqué'}</dd></div><div><dt>Relevé</dt><dd>${escapeHtml(formatDate(item.createdAt))}</dd></div></dl>
        ${details ? `<p class="details">${details}</p>` : ''}
        <div class="card-actions">${link ? `<a href="${escapeHtml(link)}" target="_blank" rel="noopener noreferrer">Revoir l’offre ↗</a>` : '<span class="muted">Lien non fourni</span>'}<button class="delete-button" type="button" data-delete="${escapeHtml(item.id)}" aria-label="Supprimer le relevé ${escapeHtml(PLATFORMS[item.platform].label)} du ${escapeHtml(formatDate(item.createdAt))}">Supprimer</button></div>
      </article>`;
    }).join('');
    const compareNote = hasBothPlatforms
      ? `<p class="compare-note">${formatMoney(spread)} d’écart entre les relevés récents. Vérifie que le panier et les conditions de livraison sont identiques.</p>`
      : '<p class="compare-note">Ajoute un relevé récent de l’autre plateforme pour comparer.</p>';
    return `<section class="comparison-group" aria-label="${escapeHtml(first.scenario)} chez ${escapeHtml(first.merchant)}">
      <div class="group-heading"><div><h3>${escapeHtml(first.scenario)}</h3><p>${escapeHtml(first.merchant)}</p></div><span class="group-count">${items.length} relevé${items.length === 1 ? '' : 's'}</span></div>
      <div class="observation-grid">${cards}</div>${compareNote}</section>`;
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    formError.hidden = true;
    const data = new FormData(form);
    const scenario = String(data.get('scenario') || '').trim();
    const merchant = String(data.get('merchant') || '').trim();
    const platform = String(data.get('platform') || '');
    const total = Number(data.get('total'));
    const etaValue = String(data.get('eta') || '').trim();
    const eta = etaValue ? Number(etaValue) : null;
    const rawUrl = String(data.get('url') || '').trim();
    const url = rawUrl ? safeOfferUrl(rawUrl, platform) : '';
    if (!scenario || !merchant || !Object.hasOwn(PLATFORMS, platform) || !Number.isFinite(total) || total <= 0 || total > 10000) {
      formError.textContent = 'Complète le nom de comparaison, le commerce et un total supérieur à 0 €.';
      formError.hidden = false;
      return;
    }
    if (etaValue && (!Number.isInteger(eta) || eta < 1 || eta > 600)) {
      formError.textContent = 'Le délai doit être un nombre entier entre 1 et 600 minutes.';
      formError.hidden = false;
      return;
    }
    if (rawUrl && !url) {
      formError.textContent = 'Le lien doit être une adresse HTTPS officielle Uber Eats ou Deliveroo correspondant à la plateforme choisie.';
      formError.hidden = false;
      return;
    }
    observations.unshift({
      id: window.crypto && window.crypto.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random().toString(16).slice(2)}`,
      scenario, merchant, platform, total: Math.round(total * 100) / 100, eta,
      fees: String(data.get('fees') || '').trim(), basket: String(data.get('basket') || '').trim(),
      url, createdAt: new Date().toISOString()
    });
    observations = observations.slice(0, MAX_OBSERVATIONS);
    persist();
    form.reset();
    render();
    document.querySelector('#observations-title').focus({ preventScroll: true });
    document.querySelector('#observations').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-delete]');
    if (!button) return;
    observations = observations.filter((item) => item.id !== button.dataset.delete);
    persist();
    render();
  });

  filterInput.addEventListener('input', render);
  readStorage();
  storageStatus.textContent = storageAvailable ? 'Les relevés sont conservés sur cet appareil.' : 'Le stockage local est indisponible; les relevés précédents n’ont pas pu être chargés.';
  storageStatus.classList.toggle('storage-warning', !storageAvailable);
  render();
})();
