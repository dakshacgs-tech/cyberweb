'use strict';
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('mobileOverlay');
function setMenu(open){if(!sidebar)return; sidebar.classList.toggle('open',open); if(overlay)overlay.hidden=!open; menuToggle?.setAttribute('aria-expanded',String(open));menuToggle?.setAttribute('aria-label',open?'Close navigation':'Open navigation');}
menuToggle?.addEventListener('click',()=>setMenu(!sidebar.classList.contains('open')));
overlay?.addEventListener('click',()=>setMenu(false));
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&sidebar?.classList.contains('open')){setMenu(false);menuToggle?.focus();}});
const today = document.getElementById('todayDate');
if(today)today.textContent=new Intl.DateTimeFormat('en',{weekday:'short',day:'numeric',month:'short',year:'numeric'}).format(new Date());
const search = document.getElementById('projectSearch');
search?.addEventListener('input',()=>{let shown=0;document.querySelectorAll('.project-row[data-search]').forEach(row=>{let match=row.dataset.search.includes(search.value.trim().toLowerCase());row.hidden=!match;if(match)shown++;});const empty=document.getElementById('noResults');if(empty)empty.hidden=shown!==0;});
