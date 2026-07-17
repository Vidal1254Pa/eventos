// assets/js/app.js
document.addEventListener('click', (e)=>{
  const el = e.target.closest('[data-confirm]');
  if(el){
    const msg = el.getAttribute('data-confirm') || '¿Confirmar?';
    if(!confirm(msg)) e.preventDefault();
  }
});


// --- Barcode friendly helpers ---
document.addEventListener('DOMContentLoaded', ()=>{
  const bc = document.querySelector('[data-barcode-input]');
  if(bc){
    bc.focus();
    // Many scanners send an ENTER at the end. This will submit on Enter.
    bc.addEventListener('keydown', (e)=>{
      if(e.key === 'Enter'){
        const form = bc.closest('form');
        if(form) form.submit();
      }
    });
  }

  // Simple toast
  const toast = document.querySelector('[data-toast]');
  if(toast){
    setTimeout(()=>toast.classList.add('show'), 10);
    setTimeout(()=>toast.classList.remove('show'), 3500);
  }
});
