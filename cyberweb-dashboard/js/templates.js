(() => {
  'use strict';
  const cards = [...document.querySelectorAll('.tm-card')];
  const grid = document.getElementById('templateGrid');
  const hotelBrand = grid?.dataset.hotelBrand || '';
  const galleryCover = grid?.dataset.galleryCover || '';
  const projectId = grid?.dataset.projectId || '';
  const galleryIds = (grid?.dataset.galleryIds || '').split(',').filter(id => /^\d+$/.test(id));
  const storagePrefix = grid?.dataset.projectId ? 'cyberweb-template-preview:' + grid.dataset.projectId + ':' : 'cyberweb-template-preview:';
  let databaseSettings = {};
  try { databaseSettings = JSON.parse(grid?.dataset.savedSettings || '{}') || {}; } catch {}
  const csrf = grid?.dataset.csrf || '';
  const drafts = {};
  if (hotelBrand) {
    cards.forEach(card => {
      const top = card.querySelector('.tm-design-top');
      if (top?.firstChild?.nodeType === Node.TEXT_NODE) top.firstChild.textContent = hotelBrand + ' ';
      if (galleryCover) {
        const design = card.querySelector('.tm-design');
        const image = document.createElement('img');
        image.className = 'tm-hotel-photo';
        image.src = galleryCover;
        image.alt = '';
        image.loading = 'lazy';
        design?.prepend(image);
        // Make the user's actual photos visible across all four concept cards.
        const photoStrip = document.createElement('div');
        photoStrip.className = 'tm-preview-photo-strip';
        photoStrip.setAttribute('aria-label', `Your hotel gallery, ${galleryIds.length} photos`);
        galleryIds.slice(0, 4).forEach((id, index) => {
          const thumbnail = document.createElement('img');
          thumbnail.src = `gallery-image.php?project_id=${projectId}&image_id=${id}&v=20261028-5`;
          thumbnail.alt = `Gallery photo ${index + 1}`;
          thumbnail.loading = 'lazy';
          photoStrip.append(thumbnail);
        });
        const countBadge = document.createElement('span');
        countBadge.textContent = `${galleryIds.length} photos`;
        photoStrip.append(countBadge);
        card.querySelector('.tm-preview')?.append(photoStrip);
      }
    });
  }
  const search = document.getElementById('templateSearch');
  const filter = document.getElementById('templateFilter');
  const empty = document.getElementById('tmEmpty');
  const previewDialog = document.getElementById('tmPreviewDialog');
  const createDialog = document.getElementById('tmCreateDialog');
  const previewMount = document.getElementById('tmPreviewMount');
  const previewTitle = document.getElementById('tmPreviewTitle');
  const selectedName = document.getElementById('tmSelectedName');
  const selectedTemplate = document.getElementById('tmSelectedTemplate');
  const chosenBrand = document.getElementById('tmChosenBrand');
  const chosenHeadline = document.getElementById('tmChosenHeadline');
  const projectName = document.getElementById('tmProjectName');
  const brandInput = document.getElementById('tmEditBrand');
  const headlineInput = document.getElementById('tmEditHeadline');
  const previewUse = document.getElementById('tmPreviewUse');
  const reset = document.getElementById('tmResetPreview');
  const savePreview = document.getElementById('tmSavePreview');
  const saveStatus = document.getElementById('tmSaveStatus');
  const imageAlert = document.getElementById('tmGalleryLoadError');
  document.addEventListener('error', event => {
    const image = event.target;
    if (!(image instanceof HTMLImageElement) || !image.src.includes('gallery-image.php')) return;
    image.hidden = true;
    if (imageAlert) imageAlert.hidden = false;
  }, true);
  let currentName = '';
  const defaults = {};
  cards.forEach(card => {
    const name = card.querySelector('[data-preview]')?.dataset.preview;
    if (!name) return;
    defaults[name] = {
      brand: card.querySelector('.tm-design-top')?.childNodes[0]?.textContent?.trim() || name,
      headline: card.querySelector('.tm-design-center strong')?.textContent?.replace(/\s+/g,' ').trim() || name
    };
  });
  function refresh() {
    const term = (search?.value || '').toLocaleLowerCase().trim();
    const style = filter?.value || 'all';
    let shown = 0;
    cards.forEach(card => {
      const ok = (!term || card.dataset.title.toLocaleLowerCase().includes(term)) &&
        (style === 'all' || card.dataset.style === style);
      card.hidden = !ok;
      if (ok) shown++;
    });
    if (empty) empty.hidden = shown !== 0;
  }
  search?.addEventListener('input', refresh);
  filter?.addEventListener('change', refresh);
  function saved(name) {
    if (drafts[name]) return drafts[name];
    if (projectId) {
      if (!databaseSettings[name]) {
        // Existing users may have text from the old browser-only preview version.
        try { const legacy = JSON.parse(localStorage.getItem(storagePrefix + name)); if (legacy) return legacy; } catch {}
      }
      return {
        brand: databaseSettings[name]?.brand || defaults[name].brand,
        headline: databaseSettings[name]?.headline || defaults[name].headline
      };
    }
    try { return JSON.parse(localStorage.getItem(storagePrefix + name)) || defaults[name]; }
    catch { return defaults[name]; }
  }
  function overrides(name) {
    const value = saved(name) || defaults[name];
    // Empty DB fields mean "follow the hotel name / normal template default".
    const brand = (value.brand || '').trim();
    const headline = (value.headline || '').trim();
    return {
      brand_name: brand === defaults[name].brand ? '' : brand,
      headline: headline === defaults[name].headline ? '' : headline
    };
  }
  function syncPreview() {
    const design = previewMount?.querySelector('.tm-design');
    if (!design) return;
    const brand = design.querySelector('.tm-design-top');
    const headline = design.querySelector('.tm-design-center strong');
    if (brand?.firstChild) brand.firstChild.textContent = (brandInput.value.trim() || defaults[currentName].brand) + ' ';
    if (headline) headline.textContent = headlineInput.value.trim() || defaults[currentName].headline;
    drafts[currentName] = { brand: brandInput.value, headline: headlineInput.value };
    if (projectId) { if (saveStatus) saveStatus.textContent = 'Preview changes not saved yet.'; }
    else { try { localStorage.setItem(storagePrefix + currentName, JSON.stringify(drafts[currentName])); } catch {} }
  }
  function openPreview(name) {
    const card = cards.find(c => c.querySelector('[data-preview]')?.dataset.preview === name);
    if (!card || !previewDialog || !previewMount) return;
    currentName = name;
    previewTitle.textContent = name + ' template';
    previewMount.replaceChildren(card.querySelector('.tm-preview').cloneNode(true));
    const values = saved(name) || defaults[name];
    brandInput.value = values.brand || '';
    headlineInput.value = values.headline || '';
    syncPreview();
    if (saveStatus) saveStatus.textContent = 'Use Save Text to MySQL to persist preview edits.';
    previewDialog.showModal();
  }
  function openCreate(name) {
    if (!defaults[name] || !createDialog) return;
    currentName = name;
    selectedTemplate.value = name;
    selectedName.textContent = name;
    const values = overrides(name);
    if (chosenBrand) chosenBrand.value = values.brand_name;
    if (chosenHeadline) chosenHeadline.value = values.headline;
    if (projectName && !projectName.value) projectName.value = 'My ' + name + ' Hotel Website';
    if (previewDialog?.open) previewDialog.close();
    createDialog.showModal();
  }
  document.querySelectorAll('[data-preview]').forEach(btn => btn.addEventListener('click', () => openPreview(btn.dataset.preview)));
  document.querySelectorAll('[data-use]').forEach(btn => btn.addEventListener('click', () => openCreate(btn.dataset.use)));
  document.querySelectorAll('[data-close]').forEach(btn => btn.addEventListener('click', () => btn.closest('dialog')?.close()));
  [previewDialog,createDialog].forEach(dialog => dialog?.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  }));
  brandInput?.addEventListener('input', syncPreview);
  headlineInput?.addEventListener('input', syncPreview);
  reset?.addEventListener('click', () => {
    if (!currentName) return;
    brandInput.value = defaults[currentName].brand;
    headlineInput.value = defaults[currentName].headline;
    syncPreview();
  });
  savePreview?.addEventListener('click', async () => {
    if (!projectId || !currentName) return;
    const name = currentName;
    const values = overrides(name);
    savePreview.disabled = true;
    if (saveStatus) saveStatus.textContent = 'Saving to MySQL…';
    try {
      const body = new URLSearchParams({action:'save_preview',project_id:projectId,csrf_token:csrf,
        template:name,brand_name:values.brand_name,headline:values.headline});
      const response = await fetch('templates.php', {method:'POST',credentials:'same-origin',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},body});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.error || 'Database save failed.');
      databaseSettings[name] = {brand:values.brand_name,headline:values.headline};
      if (saveStatus) saveStatus.textContent = '✓ Preview text saved to MySQL.';
    } catch (error) {
      if (saveStatus) saveStatus.textContent = `Save failed: ${error.message}. Try again.`;
    } finally { savePreview.disabled = false; }
  });
  previewUse?.addEventListener('click', () => openCreate(currentName));
  refresh();
})();
