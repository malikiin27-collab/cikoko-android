(function(){var form=document.getElementById('pppk-form'),action=document.getElementById('pppk-action');
form.addEventListener('click',function(e){var b=e.target.closest('button[data-action]');if(b)action.value=b.dataset.action;},true);
form.addEventListener('submit',function(e){if(e.submitter&&e.submitter.dataset.action)action.value=e.submitter.dataset.action;if(!form.checkValidity())return;var b=e.submitter;if(b)b.textContent='Menyimpan…';},true);

form.querySelectorAll('[data-pppk-photo]').forEach(function(input){
 var picker=input.closest('.pppk-photo-picker'),actions=picker.querySelector('.pppk-photo-actions'),status=picker.querySelector('.pppk-photo-status'),preview=picker.querySelector('.pppk-photo-preview'),previewUrl=null;
 input.hidden=true;actions.hidden=false;
 actions.addEventListener('click',function(e){
  var button=e.target.closest('[data-pppk-source]');if(!button)return;
  if(button.dataset.pppkSource==='camera')input.setAttribute('capture','environment');else input.removeAttribute('capture');
  input.click();
 });
 input.addEventListener('change',function(){
  var file=input.files&&input.files[0];if(!file)return;
  if(previewUrl){URL.revokeObjectURL(previewUrl);previewUrl=null;}
  preview.hidden=true;preview.removeAttribute('src');
  status.textContent=file.name+' · '+(file.size/1024/1024).toFixed(2)+' MB';
  if(/^image\/(jpeg|png|webp)$/i.test(file.type)){previewUrl=URL.createObjectURL(file);preview.src=previewUrl;preview.hidden=false;}
 });
 form.addEventListener('reset',function(){if(previewUrl)URL.revokeObjectURL(previewUrl);previewUrl=null;preview.hidden=true;preview.removeAttribute('src');status.textContent='Pilih foto kegiatan dari kamera atau galeri.';});
 window.addEventListener('pagehide',function(){if(previewUrl)URL.revokeObjectURL(previewUrl);});
});
document.querySelectorAll('.pppk-progress').forEach(function(select){select.addEventListener('change',function(){var text=this.value==='done'?this.dataset.done:(this.value==='not_done'?this.dataset.notDone:'Keterangan dibuat otomatis sesuai pilihan progres.');this.closest('section').querySelector('.pppk-caption').textContent=text;});});
})();
