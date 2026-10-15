const fs=require('fs'),vm=require('vm'),assert=require('assert');
const script=fs.readFileSync('tests/fixtures/pppk-photo-picker.js','utf8');
const handlers={},attributes={},calls=[];
const actions={hidden:true,addEventListener:(n,f)=>handlers[n]=f};
const status={textContent:''},preview={hidden:true,removeAttribute(){}};
const picker={querySelector:s=>s.includes('actions')?actions:s.includes('status')?status:preview};
const input={closest:()=>picker,setAttribute:(k,v)=>attributes[k]=v,removeAttribute:k=>delete attributes[k],click:()=>calls.push({...attributes}),addEventListener(){}};
const form={addEventListener(){},querySelectorAll:()=>[input]};
const document={getElementById:id=>id==='pppk-form'?form:{},querySelectorAll:()=>[]};
vm.runInNewContext(script,{document,window:{addEventListener(){}},URL:{}});
function press(source){handlers.click({target:{closest:()=>({dataset:{pppkSource:source}})}});}
press('camera');press('gallery');press('camera');press('gallery');
assert.equal(calls[0].capture,'environment');
assert.equal(calls[1].capture,undefined);
assert.equal(calls[2].capture,'environment');
assert.equal(calls[3].capture,undefined);
assert.equal(actions.hidden,false);
console.log('PASS: PPPK Kamera/Galeri alternating source selection; gallery has no capture request');
