(() => {
  'use strict';
  const photosInput = document.getElementById('gallery_images');
  const zipInput = document.getElementById('photo_gallery_zip');
  const previews = document.getElementById('galleryNewPreview');
  const feedback = document.getElementById('galleryUploadFeedback');
  const zipLabel = document.getElementById('zipFileName');
  if (!photosInput || !zipInput || !previews || !feedback || !zipLabel) return;
  const existingCount = Number(previews.dataset.existingCount || 0);
  const maxImageSize = 8 * 1024 * 1024;
  const maxZipSize = 20 * 1024 * 1024;
  const allowedExtensions = /\.(jpe?g|png|webp)$/i;
  const allowedMimeTypes = new Set(['image/jpeg', 'image/png', 'image/webp']);
  let previewUrls = [];
  let selectedFiles = Array.from(photosInput.files || []);

  function syncSelectedFiles() {
    if (typeof DataTransfer === 'undefined') return;
    const transfer = new DataTransfer();
    selectedFiles.forEach(file => transfer.items.add(file));
    photosInput.files = transfer.files;
  }

  function clearPreviews() {
    previewUrls.forEach(url => URL.revokeObjectURL(url));
    previewUrls = [];
    previews.replaceChildren();
    previews.hidden = true;
  }

  function refresh() {
    clearPreviews();
    photosInput.setCustomValidity('');
    zipInput.setCustomValidity('');
    const files = Array.from(photosInput.files || []);
    const zipFile = zipInput.files?.[0] || null;
    const removedCount = document.querySelectorAll('input[name="delete_photo_ids[]"]:checked').length;
    const photoProblems = [];
    const zipProblems = [];

    if (files.length > 12) photoProblems.push('Ek baar mein maximum 12 photos select karo.');
    if (existingCount - removedCount + files.length > 30) {
      photoProblems.push('Gallery mein total 30 photos hi ho sakti hain. Kuch purani photos remove karo.');
    }
    for (const file of files) {
      if (!allowedExtensions.test(file.name) || (file.type && !allowedMimeTypes.has(file.type))) {
        photoProblems.push(`${file.name}: JPG, PNG ya WebP file select karo.`);
      } else if (!file.size || file.size > maxImageSize) {
        photoProblems.push(`${file.name}: photo 8 MB se chhoti honi chahiye.`);
      }
    }
    if (zipFile && (!/\.zip$/i.test(zipFile.name) || zipFile.size > maxZipSize || !zipFile.size)) {
      zipProblems.push('Valid ZIP file select karo (maximum 20 MB).');
    }

    if (files.length) {
      previews.hidden = false;
      files.forEach((file, index) => {
        if (!allowedExtensions.test(file.name) || !allowedMimeTypes.has(file.type) && file.type !== '') return;
        const tile = document.createElement('div');
        tile.className = 'hd-selected-item';
        const photo = document.createElement('img');
        const objectUrl = URL.createObjectURL(file);
        previewUrls.push(objectUrl);
        photo.src = objectUrl;
        photo.alt = `Selected hotel photo ${index + 1}`;
        const caption = document.createElement('span');
        caption.className = 'hd-selected-label';
        caption.textContent = `${index + 1}. ${file.name}`;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'hd-selected-remove';
        remove.textContent = 'Remove';
        remove.setAttribute('aria-label', `Remove ${file.name} from upload`);
        remove.addEventListener('click', () => {
          selectedFiles.splice(index, 1);
          syncSelectedFiles();
          refresh();
        });
        tile.append(photo, caption, remove);
        previews.append(tile);
      });
    }

    zipLabel.textContent = zipFile
      ? `ZIP selected: ${zipFile.name}. ZIP photos Save karne ke baad gallery mein dikhengi.`
      : 'No ZIP selected. ZIP ke andar ki photos Save ke baad preview hongi.';

    if (photoProblems.length) photosInput.setCustomValidity(photoProblems.join(' '));
    if (zipProblems.length) zipInput.setCustomValidity(zipProblems.join(' '));
    const problems = [...photoProblems, ...zipProblems];
    feedback.classList.toggle('error', problems.length > 0);
    if (problems.length) {
      feedback.textContent = problems.join(' ');
    } else if (files.length) {
      feedback.textContent = `${files.length}/12 photo${files.length === 1 ? '' : 's'} selected ✓ Dobara Select Photos click karke aur photos add kar sakti ho. Save par upload hongi.${zipFile ? ' ZIP bhi selected hai.' : ''}`;
    } else if (zipFile) {
      feedback.textContent = 'ZIP selected ✓ Save par ZIP ki images extract hongi aur gallery mein dikhengi.';
    } else {
      feedback.textContent = 'JPG/PNG/WebP photos choose karo. Selected images ka preview yahin dikhega.';
    }
  }

  photosInput.addEventListener('change', () => {
    const incoming = Array.from(photosInput.files || []);
    incoming.forEach(file => {
      if (!selectedFiles.some(existing => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified)) {
        selectedFiles.push(file);
      }
    });
    syncSelectedFiles();
    // A previously selected ZIP can remain in the form even if the user now
    // wants individual JPG/PNG files. Default this interaction to direct photos.
    const staleZipRemoved = incoming.length > 0 && (zipInput.files?.length || 0) > 0;
    if (staleZipRemoved) zipInput.value = '';
    refresh();
    if (staleZipRemoved && !photosInput.validationMessage) {
      feedback.textContent += ' Pehle selected ZIP ko clear kar diya hai; sirf direct photos upload hongi.';
    }
  });
  zipInput.addEventListener('change', refresh);
  document.querySelectorAll('input[name="delete_photo_ids[]"]').forEach(box => box.addEventListener('change', refresh));
  window.addEventListener('pagehide', clearPreviews);
  refresh();
})();
