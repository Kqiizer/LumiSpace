/* ===== LumiSpace – Translator =====
   Toggle ES <-> EN con Azure Translator
   - Cache local por frase (ES -> EN)
   - Evita bucles con MutationObserver
   - Reconvierte a ES recargando el HTML original
*/

(() => {
  const LS_LANG  = 'lumispace_lang';        // 'es' | 'en'
  const LS_CACHE = 'lumispace_cache_v1';    // { en: { 'texto ES': 'texto EN' } }

  const btn      = document.getElementById('lang-toggle');
  const btnLabel = document.getElementById('lang-label');
  const btnFlag  = document.getElementById('lang-flag');
  const base     = document.body.getAttribute('data-base') || './';
  const endpoint = (base.endsWith('/') ? base : base + '/') + 'azure/api.php';

  // Qué NO traducir y qué atributos revisar
  const SKIP_SELECTORS = [
    'script','style','noscript','code','pre','kbd','samp','var',
    '.i18n-skip','.no-translate','.price','.badge','.sku','.email','.tel'
  ];
  const ATTRS = ['title','placeholder','aria-label','alt'];

  // Alcance a traducir (puedes acotar si quieres más rendimiento)
  const SCOPE_SELECTORS = ['body'];

  // Almacenes de originales para poder mapear de ES->EN sin duplicar
  const esMap   = new WeakMap();   // TextNode  -> texto ES original (incl. espacios)
  const attrMap = new WeakMap();   // Element   -> { attr : valorES }

  // Flags y observer
  let isTranslating = false;
  let mo;
  let rafId = null;

  // Utilidades de caché
  const getCache = () => {
    try { return JSON.parse(localStorage.getItem(LS_CACHE) || '{}'); }
    catch { return {}; }
  };
  const setCache = (c) => localStorage.setItem(LS_CACHE, JSON.stringify(c));
  const setBtnLabel = (lang) => {
    if (!btnLabel) return;
    const isSpanish = lang === 'es';
    btnLabel.textContent = isSpanish ? 'English' : 'Español';
    if (btnFlag) {
      const flagSrc = isSpanish ? btn?.dataset.flagEn : btn?.dataset.flagEs;
      if (flagSrc) btnFlag.src = flagSrc;
      btnFlag.alt = isSpanish ? 'Bandera de Inglaterra' : 'Bandera de España';
    }
  };

  // Helpers de selección
  const getScopes = () => {
    const out = [];
    SCOPE_SELECTORS.forEach(sel => document.querySelectorAll(sel).forEach(n => out.push(n)));
    return out.length ? out : [document.body];
  };

  const shouldSkipTextNode = (node) => {
    if (node.nodeType !== Node.TEXT_NODE) return true;
    const t = node.nodeValue;
    if (!t || !/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/.test(t)) return true; // vacío o sólo números/puntuación
    const p = node.parentElement;
    if (!p) return true;
    if (SKIP_SELECTORS.some(sel => p.closest(sel))) return true;
    return false;
  };

  function collectTextNodes() {
    const nodes = [];
    getScopes().forEach(root => {
      const w = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
      let n;
      while ((n = w.nextNode())) {
        if (!shouldSkipTextNode(n)) nodes.push(n);
      }
    });
    return nodes;
  }

  function collectAttrElements() {
    const result = [];
    getScopes().forEach(root => {
      root.querySelectorAll('*').forEach(el => {
        if (SKIP_SELECTORS.some(sel => el.closest(sel))) return;
        const picked = {};
        let has = false;
        ATTRS.forEach(a => {
          const v = el.getAttribute(a);
          if (v && /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/.test(v)) { picked[a] = v; has = true; }
        });
        if (has) result.push([el, picked]);
      });
    });
    return result;
  }

  async function toEnglish() {
    const cache = getCache(); cache.en = cache.en || {};
    const textNodes = collectTextNodes();
    const attrEls   = collectAttrElements();

    // 1) Guardar originales y construir lista única de ES a traducir
    const pendingMap = new Map();   // es -> index en 'list'
    const list = [];

    textNodes.forEach(n => {
      if (!esMap.has(n)) esMap.set(n, n.nodeValue);
      const key = n.nodeValue.trim();
      if (key && !(key in cache.en) && !pendingMap.has(key)) {
        pendingMap.set(key, list.length); list.push(key);
      }
    });

    attrEls.forEach(([el, bag]) => {
      if (!attrMap.has(el)) attrMap.set(el, Object.assign({}, bag));
      Object.values(bag).forEach(v => {
        const key = v.trim();
        if (key && !(key in cache.en) && !pendingMap.has(key)) {
          pendingMap.set(key, list.length); list.push(key);
        }
      });
    });

    // 2) Llamar a Azure por los faltantes
    if (list.length) {
      try {
        const r = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ from: 'es', to: 'en', texts: list })
        });
        const j = await r.json();
        const translated = Array.isArray(j?.translated) ? j.translated : [];
        translated.forEach((enText, i) => {
          const esText = list[i];
          if (esText != null && typeof enText === 'string') {
            cache.en[esText] = enText;
          }
        });
        setCache(cache);
      } catch (e) {
        console.error('Azure translate error:', e);
      }
    }

    // 3) Pintar textos
    textNodes.forEach(n => {
      const esOriginal = esMap.get(n);
      if (!esOriginal) return;
      const key = esOriginal.trim();
      const en  = cache.en[key];
      if (en) {
        // Conserva espacios/puntuación alrededor reemplazando sólo el trim()
        n.nodeValue = esOriginal.replace(key, en);
      }
    });

    // 4) Pintar atributos
    attrEls.forEach(([el, bag]) => {
      Object.entries(bag).forEach(([attr, es]) => {
        const key = es.trim();
        const en  = cache.en[key];
        if (en) el.setAttribute(attr, es.replace(key, en));
      });
    });

    document.documentElement.lang = 'en';
    localStorage.setItem(LS_LANG, 'en');
    setBtnLabel('en');
  }

  // Versión segura que desconecta observer y evita reentradas
  async function safeToEnglish() {
    if (isTranslating) return;
    isTranslating = true;
    try {
      mo && mo.disconnect();                 // evita que nuestras mutaciones disparen el observer
      await toEnglish();
    } finally {
      isTranslating = false;
      mo && mo.observe(document.documentElement, { childList: true, subtree: true });
    }
  }

  function toSpanish() {
    // Restauramos el HTML original desde el servidor (ES)
    localStorage.setItem(LS_LANG, 'es');
    document.documentElement.lang = 'es';
    setBtnLabel('es');
    location.reload();
  }

  // --- Toggle del botón
  btn?.addEventListener('click', () => {
    const lang = localStorage.getItem(LS_LANG) || 'es';
    (lang === 'es') ? safeToEnglish() : toSpanish();
  });

  // --- Inicialización
  const initial = localStorage.getItem(LS_LANG) || 'es';
  setBtnLabel(initial);
  if (initial === 'en') safeToEnglish();

  // --- Observer para contenido que aparezca después (paginación, AJAX, etc.)
  mo = new MutationObserver(() => {
    const lang = localStorage.getItem(LS_LANG) || 'es';
    if (lang !== 'en' || isTranslating) return;
    // Debounce por frame
    cancelAnimationFrame(rafId);
    rafId = requestAnimationFrame(() => { safeToEnglish(); });
  });

  mo.observe(document.documentElement, { childList: true, subtree: true });

})();
