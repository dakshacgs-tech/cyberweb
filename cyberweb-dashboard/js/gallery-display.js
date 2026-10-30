(() => {
  'use strict';
  const warning = document.getElementById('sitePhotoLoadError');
  const inlineWarning = document.getElementById('siteGalleryInlineError');
  const reason = document.getElementById('siteGalleryErrorReason');
  let diagnosisStarted = false;

  async function diagnose(url) {
    if (diagnosisStarted) return;
    diagnosisStarted = true;
    try {
      const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store' });
      const mime = response.headers.get('content-type') || '';
      if (response.redirected && response.url.includes('login.php')) {
        reason.textContent = 'Login session expire ho sakta hai. Dobara login karke try karo.';
      } else if (!response.ok) {
        reason.textContent = `Image server HTTP ${response.status} return kar raha hai.`;
      } else if (!mime.startsWith('image/')) {
        reason.textContent = 'Image ke badle page/text response aa raha hai. gallery-image.php check karo.';
      } else {
        const bytes = new Uint8Array(await response.arrayBuffer());
        const isJpeg = bytes[0] === 0xff && bytes[1] === 0xd8;
        const isPng = bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4e && bytes[3] === 0x47;
        const isWebp = String.fromCharCode(...bytes.slice(0, 4)) === 'RIFF'
          && String.fromCharCode(...bytes.slice(8, 12)) === 'WEBP';
        if (!(isJpeg || isPng || isWebp)) {
          reason.textContent = 'Server response image ke valid bytes se start nahi ho raha. PHP extra text/output bhej raha ho sakta hai.';
        } else {
          reason.textContent = 'Image header sahi hai, lekin file browser decode nahi kar pa raha. Image corrupt/truncated ho sakti hai.';
        }
      }
    } catch {
      if (reason) reason.textContent = 'Image request fail ho gayi. Apache aur browser console check karo.';
    }
  }
  document.querySelectorAll('img[data-gallery-image]').forEach(photo => {
    photo.addEventListener('error', () => {
      if (warning) warning.hidden = false;
      if (inlineWarning) inlineWarning.hidden = false;
      if (reason) diagnose(photo.src);
      if (photo.classList.contains('hotel-hero-image')) {
        photo.remove();
        return;
      }
      const figure = photo.closest('.hotel-gallery-photo');
      if (!figure) return;
      photo.remove();
      figure.classList.add('photo-broken');
      const message = document.createElement('span');
      message.textContent = 'Photo could not load — see Gallery warning';
      figure.append(message);
    }, { once: true });
    // Cached-image failures sometimes happen before listeners are attached.
    if (photo.complete && photo.naturalWidth === 0) {
      photo.dispatchEvent(new Event('error'));
    }
  });
})();
