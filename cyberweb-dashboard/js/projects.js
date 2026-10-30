(() => { 'use strict';
const modal=document.getElementById('projectModal'); if(!modal)return;
const form=document.getElementById('projectForm'), title=document.getElementById('pjDialogTitle'),submit=document.getElementById('pjSubmit');
let lastFocus=null;
function open(){lastFocus=document.activeElement;modal.hidden=false;document.body.classList.add('pj-dialog-open');document.getElementById('pjName').focus();}
function close(){modal.hidden=true;document.body.classList.remove('pj-dialog-open');lastFocus?.focus();}
function create(){form.reset();document.getElementById('pjAction').value='create';document.getElementById('pjId').value='';document.getElementById('pjStatusField').hidden=true;document.getElementById('pjNextStepNote').hidden=false;title.textContent='Create a project';submit.textContent='Continue to hotel details →';open();}
document.querySelectorAll('[data-open-create]').forEach(el=>el.addEventListener('click',create));
document.querySelectorAll('[data-edit]').forEach(el=>el.addEventListener('click',()=>{form.reset();document.getElementById('pjAction').value='update';document.getElementById('pjId').value=el.dataset.id;for(const [id,val] of [['pjName',el.dataset.name],['pjProperty',el.dataset.property],['pjTeamMember',el.dataset.team],['pjStatus',el.dataset.status]])document.getElementById(id).value=val||'';document.getElementById('pjStatusField').hidden=false;document.getElementById('pjNextStepNote').hidden=true;title.textContent='Edit project';submit.textContent='Save changes';open();}));
document.querySelectorAll('[data-close-modal]').forEach(el=>el.addEventListener('click',close));
document.addEventListener('keydown',e=>{if(modal.hidden)return;if(e.key==='Escape')close();if(e.key==='Tab'){const focusable=[...modal.querySelectorAll('button:not([disabled]),input:not([type="hidden"]),select')];const first=focusable[0],last=focusable[focusable.length-1];if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}}});
document.querySelectorAll('[data-delete]').forEach(el=>el.addEventListener('click',e=>{if(!confirm('Delete this project permanently? This cannot be undone.'))e.preventDefault();}));
if(location.hash==='#new-project')create();
})();
