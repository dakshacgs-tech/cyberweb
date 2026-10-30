'use strict';
document.getElementById('year').textContent = new Date().getFullYear();
const form = document.getElementById('signupForm');
const ids = ['firstName','lastName','email','password','confirmPassword'];
const $ = id => document.getElementById(id);
const cleanError = id => {$(id+'Error').textContent='';$(id).removeAttribute('aria-invalid')};
const setError = (id,message) => {$(id+'Error').textContent=message;$(id).setAttribute('aria-invalid','true')};
for(const id of ids){$(id).addEventListener('input',()=>cleanError(id))}
document.querySelectorAll('.reveal').forEach(button=>button.addEventListener('click',()=>{
 const input=$(button.dataset.target),shown=input.type==='text';input.type=shown?'password':'text';button.setAttribute('aria-pressed',String(!shown));button.setAttribute('aria-label',(shown?'Show':'Hide')+' password');
}));
const scorePassword = text => {let score=0;if(text.length>=8)score++;if(text.length>=12)score++;if(/[a-z]/.test(text)&&/[A-Z]/.test(text))score++;if(/\d/.test(text)&&/[^\w\s]/.test(text))score++;return score};
$('password').addEventListener('input',()=>{const value=$('password').value,score=value?scorePassword(value):0;document.querySelector('.strength').dataset.score=String(score);$('strengthText').textContent=value?['Too weak — use more character types','Weak — add more variety','Fair — you can make it stronger','Good password','Strong password'][score]:'Use uppercase, lowercase, numbers and symbols.'});
document.querySelectorAll('[data-unavailable]').forEach(link=>link.addEventListener('click',event=>{event.preventDefault();$('formStatus').textContent=link.dataset.unavailable+' page needs to be added before publishing.'}));
form.addEventListener('submit',event=>{
 let valid=true;ids.forEach(cleanError);$('termsError').textContent='';$('formStatus').textContent='';
 for(const id of ['firstName','lastName']){if(!$(id).value.trim()){setError(id,'Please enter your '+(id==='firstName'?'first':'last')+' name.');valid=false}}
 const email=$('email').value.trim();if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){setError('email','Enter a valid email address.');valid=false}
 const password=$('password').value;if(password.length<12){setError('password','Password must have at least 12 characters.');valid=false}
 if($('confirmPassword').value!==password||!$('confirmPassword').value){setError('confirmPassword','Passwords must match.');valid=false}
 if(!$('terms').checked){$('termsError').textContent='Please accept the terms before continuing.';valid=false}
 if(!valid){event.preventDefault();form.querySelector('[aria-invalid="true"]')?.focus();return}
 $('formStatus').textContent='Creating your account…';
});
