(() => {
  'use strict';

  const restaurants = Array.isArray(window.DEMO_RESTAURANTS) ? window.DEMO_RESTAURANTS : [];
  const ALLOWED_PLATFORM_HOSTS = {
    'uber-eats': ['ubereats.com', 'www.ubereats.com'],
    'deliveroo': ['deliveroo.fr', 'www.deliveroo.fr', 'deliveroo.com', 'www.deliveroo.com'],
    'too-good-to-go': ['toogoodtogo.com', 'www.toogoodtogo.com'],
    'le-fourgon': ['lefourgon.com', 'www.lefourgon.com']
  };
  const STORAGE_KEY = 'deliveryAggregator.preferences.v1';

  const elements = {
    form: document.querySelector('#searchForm'),
    query: document.querySelector('#searchInput'),
    platformChecks: [...document.querySelectorAll('input[name="platform"]')],
    fulfillmentChecks: [...document.querySelectorAll('input[name="fulfillment"]')],
    categoryChecks: [...document.querySelectorAll('input[name="category"]')],
    uberOne: document.querySelector('#uberOne'),
    deliverooPlus: document.querySelector('#deliverooPlus'),
    favoritesOnly: document.querySelector('#favoritesOnly'),
    cards: document.querySelector('#cards'),
    resultCount: document.querySelector('#resultCount'),
    emptyState: document.querySelector('#emptyState'),
    clearFilters: document.querySelector('#clearFilters'),
    resetLocal: document.querySelector('#resetLocal'),
    preferenceNote: document.querySelector('#preferenceNote')
  };

  const state = {
    query: '',
    platforms: new Set(),
    fulfillmentModes: new Set(),
    categories: new Set(),
    favoritesOnly: false,
    subscriptions: { uberOne: false, deliverooPlus: false },
    favorites: new Set(),
    storageAvailable: true
  };

  function normalizeText(value) {
    return String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLocaleLowerCase('fr-FR')
      .trim();
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function formatMoney(value) {
    if (!Number.isFinite(value)) return 'Non renseigné';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
  }

  function formatEta(value) {
    if (!Array.isArray(value) || value.length !== 2 || !value.every(Number.isFinite)) return 'Non renseigné';
    return `${value[0]}–${value[1]} min`;
  }

  function loadLocalState() {
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const saved = JSON.parse(raw);
      state.subscriptions.uberOne = saved?.subscriptions?.uberOne === true;
      state.subscriptions.deliverooPlus = saved?.subscriptions?.deliverooPlus === true;
      state.favorites = new Set(Array.isArray(saved?.favorites) ? saved.favorites.filter(id => typeof id === 'string') : []);
    } catch (error) {
      state.storageAvailable = false;
    }
  }

  function saveLocalState() {
    if (!state.storageAvailable) return false;
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
        subscriptions: state.subscriptions,
        favorites: [...state.favorites]
      }));
      return true;
    } catch (error) {
      state.storageAvailable = false;
      updatePreferenceNote();
      return false;
    }
  }

  function clearLocalState() {
    state.subscriptions = { uberOne: false, deliverooPlus: false };
    state.favorites.clear();
    state.favoritesOnly = false;
    try {
      window.localStorage.removeItem(STORAGE_KEY);
      state.storageAvailable = true;
    } catch (error) {
      state.storageAvailable = false;
    }
    syncControlsFromState();
    render();
  }

  function updatePreferenceNote() {
    const active = [];
    if (state.subscriptions.uberOne) active.push('Uber One');
    if (state.subscriptions.deliverooPlus) active.push('Deliveroo Plus');

    if (!state.storageAvailable) {
      elements.preferenceNote.textContent = 'Le stockage local est indisponible : vos abonnements et favoris fonctionnent pour cette session, mais ne survivront pas au rechargement. Aucun prix n’est recalculé.';
      return;
    }

    elements.preferenceNote.textContent = active.length
      ? `Préférences enregistrées localement : ${active.join(' + ')}. Elles restent informatives et ne modifient aucun prix.`
      : 'Les abonnements restent informatifs : leur avantage réel n’est jamais calculé dans ce prototype.';
  }

  function isAllowedOfficialUrl(platformId, rawUrl) {
    if (!rawUrl || typeof rawUrl !== 'string') return false;
    try {
      const url = new URL(rawUrl);
      if (url.protocol !== 'https:') return false;
      return (ALLOWED_PLATFORM_HOSTS[platformId] || []).includes(url.hostname.toLocaleLowerCase('en-US'));
    } catch (error) {
      return false;
    }
  }

  function restaurantMatchesQuery(restaurant) {
    if (!state.query) return true;
    const haystack = normalizeText([
      restaurant.name,
      restaurant.city,
      restaurant.neighborhood,
      ...(restaurant.cuisines || []),
      ...(restaurant.offers || []).map(offer => offer.platformName),
      ...(restaurant.offers || []).map(offer => offer.fulfillmentMode === 'pickup' ? 'retrait à emporter' : 'livraison'),
      ...(restaurant.offers || []).flatMap(offer => (offer.categories || []).map(categoryLabel))
    ].join(' '));
    return haystack.includes(normalizeText(state.query));
  }

  function categoryLabel(category) {
    return ({ meal: 'repas', grocery: 'courses', 'anti-waste': 'anti-gaspi' })[category] || category;
  }

  function offerMatchesFilters(offer) {
    if (state.platforms.size && !state.platforms.has(offer.platformId)) return false;
    if (state.fulfillmentModes.size && !state.fulfillmentModes.has(offer.fulfillmentMode)) return false;
    if (state.categories.size && !(offer.categories || []).some(category => state.categories.has(category))) return false;
    return true;
  }

  function restaurantMatchesOffers(restaurant) {
    if (!state.platforms.size && !state.fulfillmentModes.size && !state.categories.size) return true;
    return restaurant.offers.some(offerMatchesFilters);
  }

  function getVisibleOffers(restaurant) {
    if (!state.platforms.size && !state.fulfillmentModes.size && !state.categories.size) return restaurant.offers;
    return restaurant.offers.filter(offerMatchesFilters);
  }

  function getFilteredRestaurants() {
    return restaurants.filter(restaurant => {
      if (!restaurantMatchesQuery(restaurant)) return false;
      if (!restaurantMatchesOffers(restaurant)) return false;
      if (state.favoritesOnly && !state.favorites.has(restaurant.id)) return false;
      return true;
    });
  }

  function renderMetric(label, value, isFictitious) {
    return `<div class="metric"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value)}</strong>${isFictitious && value !== 'Non renseigné' ? '<span class="fake-label">Exemple inventé</span>' : ''}</div>`;
  }

  function renderOffer(offer) {
    const allowedUrl = isAllowedOfficialUrl(offer.platformId, offer.officialUrl) ? offer.officialUrl : null;
    const statusLabels = {
      demo: 'Démonstration',
      estimated: 'Estimé',
      confirmed: 'Confirmé',
      unknown: 'Inconnu'
    };
    const statusClass = ['demo', 'estimated', 'confirmed', 'unknown'].includes(offer.dataState) ? offer.dataState : 'unknown';
    const fulfillmentLabel = offer.fulfillmentMode === 'pickup' ? 'Retrait' : 'Livraison';
    const categoryLabels = (offer.categories || []).map(category => categoryLabel(category));
    const sourceBits = [offer.source || 'Source non renseignée'];
    if (offer.verifiedAt) sourceBits.push(`vérifié le ${offer.verifiedAt}`);

    return `
      <section class="offer" aria-label="Offre ${escapeHtml(offer.platformName)}">
        <div class="offer-top">
          <span class="platform">${escapeHtml(offer.platformName)} <span class="mode-pill">${fulfillmentLabel}</span>${categoryLabels.map(label => ` <span class="category-pill">${escapeHtml(label)}</span>`).join('')}</span>
          <span class="status-pill status-${statusClass}">${escapeHtml(statusLabels[statusClass])}</span>
        </div>
        <div class="offer-values">
          ${renderMetric('Exemple de panier', formatMoney(offer.itemPriceExample), offer.fictitious)}
          ${renderMetric(offer.fulfillmentMode === 'pickup' ? 'Frais de retrait' : 'Frais de livraison', formatMoney(offer.deliveryFeeExample), offer.fictitious)}
          ${renderMetric('Délai', formatEta(offer.etaMinutes), offer.fictitious)}
          ${renderMetric('Minimum', formatMoney(offer.minimumOrderExample), offer.fictitious)}
        </div>
        <p class="offer-meta">${escapeHtml(sourceBits.join(' · '))}</p>
        ${allowedUrl
          ? `<a class="button" href="${escapeHtml(allowedUrl)}" target="_blank" rel="noopener noreferrer">Commander sur ${escapeHtml(offer.platformName)}</a><p class="action-note">Ouverture de l’URL HTTPS officielle vérifiée. Prix final confirmé sur la plateforme.</p>`
          : `<button class="button" type="button" disabled>Commander sur ${escapeHtml(offer.platformName)}</button><p class="action-note">Lien officiel non renseigné : aucune destination n’est inventée.</p>`}
      </section>`;
  }

  function renderRestaurant(restaurant) {
    const favorite = state.favorites.has(restaurant.id);
    const tags = (restaurant.cuisines || []).map(cuisine => `<span class="tag">${escapeHtml(cuisine)}</span>`).join('');
    const offers = getVisibleOffers(restaurant).map(renderOffer).join('');

    return `
      <article class="restaurant-card" data-restaurant-id="${escapeHtml(restaurant.id)}">
        <div class="card-head">
          <div>
            <h3>${escapeHtml(restaurant.name)}</h3>
            <p class="location">${escapeHtml(restaurant.neighborhood)} · ${escapeHtml(restaurant.city)}</p>
            <div class="tags">${tags}</div>
          </div>
          <button class="favorite" type="button" data-favorite="${escapeHtml(restaurant.id)}" aria-pressed="${favorite}" aria-label="${favorite ? 'Retirer' : 'Ajouter'} ${escapeHtml(restaurant.name)} ${favorite ? 'des' : 'aux'} favoris">${favorite ? '★ Favori' : '☆ Favori'}</button>
        </div>
        <div class="offers">${offers}</div>
      </article>`;
  }

  function render() {
    const filtered = getFilteredRestaurants();
    elements.cards.innerHTML = filtered.map(renderRestaurant).join('');
    elements.emptyState.hidden = filtered.length !== 0;
    elements.resultCount.textContent = `${filtered.length} ${filtered.length > 1 ? 'établissements/services fictifs affichés' : 'établissement/service fictif affiché'}`;
    updatePreferenceNote();
  }

  function syncControlsFromState() {
    elements.uberOne.checked = state.subscriptions.uberOne;
    elements.deliverooPlus.checked = state.subscriptions.deliverooPlus;
    elements.favoritesOnly.checked = state.favoritesOnly;
  }

  function clearFilters() {
    state.query = '';
    state.platforms.clear();
    state.fulfillmentModes.clear();
    state.categories.clear();
    state.favoritesOnly = false;
    elements.query.value = '';
    elements.platformChecks.forEach(check => { check.checked = false; });
    elements.fulfillmentChecks.forEach(check => { check.checked = false; });
    elements.categoryChecks.forEach(check => { check.checked = false; });
    elements.favoritesOnly.checked = false;
    render();
  }

  elements.form.addEventListener('submit', event => {
    event.preventDefault();
    state.query = elements.query.value.trim();
    render();
  });

  elements.query.addEventListener('input', () => {
    state.query = elements.query.value.trim();
    render();
  });

  elements.platformChecks.forEach(check => {
    check.addEventListener('change', () => {
      state.platforms = new Set(elements.platformChecks.filter(item => item.checked).map(item => item.value));
      render();
    });
  });

  elements.fulfillmentChecks.forEach(check => {
    check.addEventListener('change', () => {
      state.fulfillmentModes = new Set(elements.fulfillmentChecks.filter(item => item.checked).map(item => item.value));
      render();
    });
  });

  elements.categoryChecks.forEach(check => {
    check.addEventListener('change', () => {
      state.categories = new Set(elements.categoryChecks.filter(item => item.checked).map(item => item.value));
      render();
    });
  });

  elements.uberOne.addEventListener('change', () => {
    state.subscriptions.uberOne = elements.uberOne.checked;
    saveLocalState();
    updatePreferenceNote();
  });

  elements.deliverooPlus.addEventListener('change', () => {
    state.subscriptions.deliverooPlus = elements.deliverooPlus.checked;
    saveLocalState();
    updatePreferenceNote();
  });

  elements.favoritesOnly.addEventListener('change', () => {
    state.favoritesOnly = elements.favoritesOnly.checked;
    render();
  });

  elements.cards.addEventListener('click', event => {
    const button = event.target.closest('[data-favorite]');
    if (!button) return;
    const id = button.dataset.favorite;
    if (state.favorites.has(id)) state.favorites.delete(id);
    else state.favorites.add(id);
    saveLocalState();
    render();
  });

  elements.clearFilters.addEventListener('click', clearFilters);
  elements.resetLocal.addEventListener('click', clearLocalState);

  loadLocalState();
  syncControlsFromState();
  render();
})();
