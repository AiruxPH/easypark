
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>

  <meta charset="utf-8" />
  <title>hPanel - Hostinger</title>
  <meta name="description"
    content="hPanel is a high availability control panel created and used by Hostinger. Log in and manage your website, email accounts, and WordPress dashboard in one click." />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,shrink-to-fit=no" />
  <meta name="referrer" content="origin" />
  <base href="/" />


  <link rel="icon" type="image/png" href="https://hpanel.hostinger.com/favicons/hostinger.png" />
  <link rel="apple-touch-icon" sizes="180x180" type="image/png" href="https://hpanel.hostinger.com/favicons/hostinger-apple-touch-icon.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

  <link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Roboto:wght@300;400;500;600;700&display=swap"
    rel="stylesheet" />

  
  
  
<script>window.__DEFAULT_IMPORT_MAP__=[{"tag":"script","src":"https://hpanel.hostinger.com/assets/js/index.Boj5hESA.js","type":"module"},{"tag":"link","href":"https://hpanel.hostinger.com/assets/chunk/vendor.CZeCEGCu.js","rel":"modulepreload"},{"tag":"link","href":"https://hpanel.hostinger.com/assets/css/vendor.m2WQ83Bb.css","rel":"stylesheet"},{"tag":"link","href":"https://hpanel.hostinger.com/assets/css/index.pVFne2Tt.css","rel":"stylesheet"}]</script>
<script>(function(){"use strict";const a="3333",i="https://hpanel-prod-pr-{PR_NUMBER}.hostinger.dev",s="preview-import-map.js",o="preview-app",p=()=>{const e=window.location.search,t=new URLSearchParams(e),n={previewApp:"",reset:""};for(const[r,c]of t.entries())n[r]=c;return n},d=()=>{const e=localStorage.getItem(o);if(!e)return;const t=document.createElement("div");t.innerHTML=`Reset preview - ${e}`,t.setAttribute("data-qa","preview-badge"),Object.entries({position:"fixed",bottom:"15px",left:"15px",padding:"0px 4px",fontSize:"10px",fontWeight:"bold",color:"#fff",backgroundColor:"#f00",zIndex:"9999",cursor:"pointer","border-radius":"4px"}).forEach(([r,c])=>{t.style[r]=c}),window.setTimeout(()=>document.body.appendChild(t),500),t.addEventListener("click",()=>{localStorage.removeItem(o),location.reload()})},l=()=>{const e=new URL(window.location.href);e.searchParams.delete("previewApp"),e.searchParams.delete("reset");const t=e.href;window.history.replaceState(null,"",t)},m=e=>e==="local"||/^-?\d+$/.test(e)?e:"",P=()=>{const{previewApp:e,reset:t}=p();t&&localStorage.removeItem(o),e&&localStorage.setItem(o,m(e)),d(),!(!e&&!t)&&l()},u=()=>{const e=localStorage.getItem(o);if(e){if(e==="local")return`http://localhost:${a}`;if(parseInt(e))return i.replace("{PR_NUMBER}",e)}},E={script:e=>{const t=document.createElement("script");return t.src=e.src,t.type=e.type,t.crossOrigin="",t.defer=!0,t},link:e=>{const t=document.createElement("link");return t.rel=e.rel,t.href=e.href,t}},_=async e=>{const t=document.getElementsByTagName("head")[0],n=document.createDocumentFragment();e==null||e.forEach(r=>{const c=E[r.tag],g=c(r);n.appendChild(g)}),t.appendChild(n)},w=e=>new Promise(t=>{if(!e)return t(window.__DEFAULT_IMPORT_MAP__);const n=document.getElementsByTagName("head")[0],r=document.createElement("script");r.src=e,r.crossOrigin="",n.appendChild(r),r.addEventListener("load",()=>t(window.__PREVIEW_IMPORT_MAP__))});(async()=>{P();const e=u(),t=e?`${e}/${s}`:void 0,n=await w(t);_(n)})()})();
</script>
</head>

<style>
  .animation-loader {
    position: relative;
    width: 176px;
    height: 176px;
  }

  .animation-loader img {
    width: 100%;
    height: 100%;
  }

  .animation-loader__wrapper {
    display: flex;
    position: fixed;
    align-items: center;
    justify-content: center;
    background-color: var(--light-blue--100);
    height: 100%;
    width: 100vw;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1;
    background-color: #f4f5ff;
  }

  .animation-loader__circle {
    width: 176px;
    height: 176px;
  }

  .animation-loader__outline {
    position: absolute;
    top: 0;
    left: 0;
    width: 176px;
    height: 176px;
  }
</style>

<body>
  <noscript><img src="./page_not_found.svg" alt="JavaScript is disabled" />
    <h1 class="text-center">JavaScript is disabled on this browser</h1>
    <h4 class="text-center">Please enable JavaScript in order to fully use our panel</h4>
  </noscript>

  <div id="app">
    <div class="animation-loader__wrapper animation-loader__wrapper--absolute">
      <div class="animation-loader">
        <img class="animation-loader__circle" src="https://hpanel.hostinger.com/assets/images/circle-only.svg" alt="" />
        <img class="animation-loader__outline" src="https://hpanel.hostinger.com/assets/images/H-outline-static.svg" alt="" />
      </div>
    </div>
  </div>
</body>

</html>
