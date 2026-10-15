const vm=require('node:vm'),fs=require('node:fs'),assert=require('node:assert/strict');
const code=fs.readFileSync('server-patch/assets/native-sync.js','utf8');
async function scenario(fail){
 const listeners={},sent=[],panels=[],requests=[];let clock=20000;
 const bridge={postMessage(raw){const msg=JSON.parse(raw);sent.push(msg);if(msg.type==='token')queueMicrotask(()=>bridge.onmessage({data:JSON.stringify({type:'token_saved'})}));if(msg.type==='init')queueMicrotask(()=>bridge.onmessage({data:JSON.stringify({type:'init',device_id:'test-device',has_token:false,protocol:2})}));}};
 const document={createElement(){return {style:{},setAttribute(){},append(...c){this.children=(this.children||[]).concat(c);},addEventListener(){},textContent:''};},getElementById(){return {insertAdjacentElement(_,p){panels.push(p);}};},querySelector(q){return {content:q.includes('csrf')?'test-csrf':''};},addEventListener(n,f){listeners[n]=f;},dispatchEvent(e){listeners[e.type]?.(e);}};
 const context={window:{CikokoNative:bridge,CikokoTracking:{status:async()=>({active:true})}},navigator:{userAgent:'CikokoAndroid/11.0'},document,Date:{now:()=>clock},Promise,JSON,URLSearchParams,AbortController,setTimeout,clearTimeout,setInterval(){},CustomEvent:class{constructor(type,o){this.type=type;this.detail=o.detail;}},fetch:async(url,opts)=>{requests.push({url,opts});return {ok:!fail||!url.includes('status'),status:fail&&url.includes('status')?401:200,json:async()=>url.includes('bridge')?{ok:true,token:'test-token'}:{ok:!fail,message:'Sesi aplikasi tidak ditemukan.'}};}};
 vm.runInNewContext(code,context);await new Promise(r=>setTimeout(r,10));
 if(fail){assert.match(panels[0].children[1].textContent,/API_STATUS: HTTP 401/);assert.equal(sent.filter(x=>x.type==='tracking'&&x.active).length,0);}
 else{clock+=11000;listeners['cikoko:tracking-status']({detail:{active:true}});await new Promise(r=>setTimeout(r,10));assert.equal(requests.filter(x=>x.url.includes('bridge')).length,1);assert.equal(sent.filter(x=>x.type==='tracking'&&x.active).length,2);assert.equal(requests[1].opts.credentials,'omit');}
}
(async()=>{await scenario(false);await scenario(true);console.log('PASS: auth once; repeated start; Bearer preflight; visible 401 prevents start');})().catch(e=>{console.error(e);process.exitCode=1;});
