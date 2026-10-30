'use strict';
(() => {
  const $ = id => document.getElementById(id);
  const KEY = 'cyberweb-hotel-editor-draft-v1';
  const initial = {
    hotelName: '', address: '', phone: '', email: '',
    amenities: ['Free Wi-Fi', 'Free Parking', 'Swimming Pool', 'Breakfast', 'Fitness Center', 'Air Conditioning'],
    rooms: [{ type: '2 Bedroom', description: '', beds: '2 beds' }, { type: '3 Bedroom', description: '', beds: '3 beds' }],
    attractions: [{ name: '', distance: '', description: '' }],
    gallery: [],
    faqs: [
      { question: 'What time is check-in and check-out?', answer: '' },
      { question: 'Is parking available at the hotel?', answer: '' },
      { question: 'Does the hotel offer free Wi-Fi?', answer: '' }
    ]
  };
  let data;
  let localPreviews = []; // Object URLs are intentionally not saved to localStorage.
  try { const saved = JSON.parse(localStorage.getItem(KEY)); data = saved && typeof saved === 'object' ? {...structuredClone(initial), ...saved} : structuredClone(initial); }
  catch { data = structuredClone(initial); }
  const basicAmenities = ['Free Wi-Fi', 'Free Parking', 'Swimming Pool', 'Breakfast', 'Fitness Center', 'Air Conditioning', 'Pet Friendly', '24-Hour Front Desk', 'Restaurant', 'Business Center', 'Airport Shuttle', 'Laundry Service'];
  const fieldNames = ['hotelName','address','phone','email'];
  const markDirty = () => { $('saveStatus').textContent = 'Unsaved changes'; document.querySelector('.draft-indicator').classList.remove('saved'); $('footerFeedback').textContent = 'You have unsaved changes.'; };
  fieldNames.forEach(k => { $(k).value = data[k] || ''; $(k).addEventListener('input', e => { data[k] = e.target.value; markDirty(); }); });
  const el = (tag, className, text) => { const node = document.createElement(tag); if(className) node.className = className; if(text !== undefined) node.textContent = text; return node; };
  const input = (value, placeholder, callback, multiline=false) => {
    const control = document.createElement(multiline ? 'textarea' : 'input');
    if(multiline) control.rows = 2;
    control.value = value || ''; control.placeholder = placeholder;
    control.addEventListener('input', e => { callback(e.target.value); markDirty(); });
    return control;
  };
  const labeled = (text, control, wide=false) => { const label = el('label', wide ? 'wide' : ''); label.append(document.createTextNode(text),control); return label; };
  function renderAmenities() {
    const parent = $('amenityOptions'); parent.replaceChildren();
    [...new Set([...basicAmenities, ...data.amenities])].forEach(name => {
      const label = el('label','amenity-option'); const checkbox = document.createElement('input'); checkbox.type='checkbox'; checkbox.checked=data.amenities.includes(name);
      checkbox.addEventListener('change',()=>{ data.amenities=checkbox.checked ? [...new Set([...data.amenities,name])] : data.amenities.filter(a=>a!==name); markDirty(); });
      label.append(checkbox,document.createTextNode(name)); parent.append(label);
    });
  }
  $('addAmenity').addEventListener('click',()=>{ const name=$('customAmenity').value.trim(); if(!name) return; data.amenities=[...new Set([...data.amenities,name])]; $('customAmenity').value=''; renderAmenities(); markDirty(); });
  function renderRepeats(kind) {
    const parent=$(kind+'List'); parent.replaceChildren();
    data[kind].forEach((item,index)=>{
      const card=el('div','repeat-item'); const heading=el('div','repeat-title'); heading.append(el('span','',kind==='rooms'?`Room type ${index+1}`:kind==='faqs'?`FAQ ${index+1}`:`Attraction ${index+1}`));
      if(kind!=='faqs') { const remove=el('button','remove-button','Remove'); remove.type='button'; remove.setAttribute('aria-label',`Remove ${kind==='rooms'?'room':'attraction'} ${index+1}`); remove.addEventListener('click',()=>{data[kind].splice(index,1);renderRepeats(kind);markDirty();});heading.append(remove); }
      const fields=el('div','repeat-fields');
      const def=kind==='rooms' ? [['type','Room type','e.g. 2 Bedroom',false],['beds','Bed arrangement','e.g. 2 King Beds',false],['description','Room description','Describe the room and its facilities...',true]] : kind==='attractions' ? [['name','Attraction name','e.g. City Museum',false],['distance','Distance from hotel','e.g. 3 miles',false],['description','Description','Why should guests visit?',true]] : [['question','Question','Enter the question',false],['answer','Answer','Enter the answer',true]];
      def.forEach(([key,title,placeholder,multiline])=>fields.append(labeled(title,input(item[key],placeholder,value=>{item[key]=value},multiline),multiline)));
      card.append(heading,fields); parent.append(card);
    });
  }
  $('addRoom').addEventListener('click',()=>{data.rooms.push({type:'',beds:'',description:''});renderRepeats('rooms');markDirty();});
  $('addAttraction').addEventListener('click',()=>{data.attractions.push({name:'',distance:'',description:''});renderRepeats('attractions');markDirty();});
  const galleryEl=$('galleryList');
  function renderGallery(){
    galleryEl.replaceChildren();
    const items=[...data.gallery.map(src=>({src,persisted:true})),...localPreviews.map(src=>({src,persisted:false}))];
    if(!items.length)galleryEl.append(el('p','gallery-empty','No photos added yet.')); 
    items.forEach(({src,persisted})=>{ const card=el('div','gallery-tile');const image=document.createElement('img');image.src=src;image.alt='Hotel gallery preview';image.loading='lazy';image.onerror=()=>{image.alt='Image failed to load';}; const remove=el('button','','×');remove.type='button';remove.setAttribute('aria-label','Remove image');remove.addEventListener('click',()=>{if(persisted){data.gallery=data.gallery.filter(url=>url!==src);}else{localPreviews=localPreviews.filter(url=>url!==src);URL.revokeObjectURL(src);}renderGallery();markDirty();});card.append(image,remove);galleryEl.append(card); });
  }
  $('addGalleryUrl').addEventListener('click',()=>{const src=$('galleryUrl').value.trim();if(!src)return;try{const url=new URL(src);if(!['http:','https:'].includes(url.protocol))throw Error();data.gallery.push(url.href);$('galleryUrl').value='';renderGallery();markDirty();}catch{alert('Please enter a valid http(s) image URL.');}});
  $('galleryUpload').addEventListener('change',e=>{for(const file of e.target.files){if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>8*1024*1024){alert(`Skipped ${file.name}: image must be JPG/PNG/WebP and less than 8 MB.`);continue;}localPreviews.push(URL.createObjectURL(file));}renderGallery();markDirty();e.target.value='';});
  $('hotelForm').addEventListener('submit',e=>{e.preventDefault();if(!$('hotelForm').reportValidity())return;try{localStorage.setItem(KEY,JSON.stringify(data));$('saveStatus').textContent='Draft saved';document.querySelector('.draft-indicator').classList.add('saved');$('footerFeedback').textContent='Draft saved in this browser. Image file previews are not saved.';}catch{$('footerFeedback').textContent='Could not save draft. Browser storage may be full.';}});
  $('exportButton').addEventListener('click',()=>{const blob=new Blob([JSON.stringify(data,null,2)],{type:'application/json'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='hotelforge-hotel-data.json';document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);});
  $('resetButton').addEventListener('click',()=>{if(!confirm('Reset all editor fields and delete the saved browser draft?'))return;localStorage.removeItem(KEY);localPreviews.forEach(URL.revokeObjectURL);localPreviews=[];location.reload();});
  renderAmenities(); ['rooms','attractions','faqs'].forEach(renderRepeats);renderGallery();
  if(localStorage.getItem(KEY)){$('saveStatus').textContent='Draft loaded';document.querySelector('.draft-indicator').classList.add('saved');$('footerFeedback').textContent='Previously saved browser draft loaded.';}
})();
