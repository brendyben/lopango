      </div><!-- /main-content -->
    </div><!-- /content-area -->
  </div><!-- /main-body -->
</div><!-- /app -->

<!-- ══════════════════════════════════════ TOAST CONTAINER -->
<div id="toast-container"></div>

<!-- ══════════════════════════════════════ CORE JS -->
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
<script src="<?= ASSETS_URL ?>/js/qr.js"></script>

<?php if (!empty($pageScripts)): ?>
<!-- ══════════════════════════════════════ PAGE SCRIPTS -->
<script>
<?= $pageScripts ?>
</script>
<?php endif; ?>

<?php /* ── SPLASH JS (si premier chargement) ── */ ?>
<?php if (!empty($_SESSION['run_splash'])): ?>
<?php unset($_SESSION['run_splash']); ?>
<script>
(function(){
  const STEPS_SP=[
    {lbl:"Vérification des certificats…", pct:18,delay:120},
    {lbl:"Chargement des communes…",      pct:38,delay:680},
    {lbl:"Connexion Google Sheets…",      pct:62,delay:1280},
    {lbl:"Chargement des biens…",         pct:81,delay:1880},
    {lbl:"Prêt.",                         pct:100,delay:2500},
  ];
  function buildWord(){
    const el=document.getElementById("sp-wordmark");if(!el)return;
    el.innerHTML="";
    "LOPANGO".split("").forEach((ch,i)=>{
      const s=document.createElement("span");s.textContent=ch;
      s.style.cssText=`animation-delay:${.35+i*.055}s;animation-duration:.3s;animation-fill-mode:both;animation-timing-function:cubic-bezier(.34,1.56,.64,1);animation-name:sp-letter`;
      el.appendChild(s);
    });
  }
  function animCnt(id,target,dur,dec){
    const el=document.getElementById(id);if(!el)return;
    const t0=performance.now();
    (function tick(now){
      const p=Math.min((now-t0)/dur,1);const e=1-Math.pow(1-p,3);const v=target*e;
      el.textContent=dec?v.toFixed(dec):Math.round(v).toLocaleString("fr-FR");
      if(p<1)requestAnimationFrame(tick);
      else el.textContent=dec?target.toFixed(dec):target.toLocaleString("fr-FR");
    })(t0);
  }
  function setProg(pct,lbl,idx){
    const f=document.getElementById("sp-fill");if(f)f.style.width=pct+"%";
    const pl=document.getElementById("sp-lbl");if(pl)pl.textContent=lbl;
    const pp=document.getElementById("sp-pct");if(pp)pp.textContent=pct+"%";
    for(let i=0;i<5;i++){const d=document.getElementById("sd"+i);if(!d)continue;d.className="sp-dot"+(i<idx?" done":i===idx?" active":"");}
  }
  function run(){
    buildWord();
    setTimeout(()=>animCnt("sp-biens",<?= array_sum(array_column(db_get_communes(),'biens')) ?>,900),1200);
    setTimeout(()=>animCnt("sp-communes",<?= count(db_get_communes()) ?>,700),1200);
    setTimeout(()=>animCnt("sp-irl",89.4,900,1),1400);
    STEPS_SP.forEach((s,i)=>setTimeout(()=>setProg(s.pct,s.lbl,i),s.delay));
    setTimeout(()=>{
      const sp=document.getElementById("splash");if(!sp)return;
      sp.classList.add("exit");
      setTimeout(()=>{sp.style.display="none";},680);
    },3350);
  }
  document.addEventListener("DOMContentLoaded",()=>setTimeout(run,150));
})();
</script>
<?php endif; ?>

<script>
// ── INIT TOASTS VIA FLASH PHP ──────────────────────────────────────────────
<?php foreach (flash_get() as $flash): ?>
LopangoApp.toast(<?= json_encode($flash['message']) ?>, '<?= $flash['type'] ?>');
<?php endforeach; ?>
</script>

</body>
</html>
