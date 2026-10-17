/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

(function (global) {
  'use strict';

/* ================= USTAWIENIA DOMYŚLNE =================
   Każdą opcję można nadpisać: createCarousel(element, { layers: 2, wheel: 'ctrl' })  */
const DEFAULTS = {
  height: '80vh',        // wysokość kontenera: liczba (px) albo CSS ('600px', '70vh'); null = ustala CSS strony
  aspectRatio: null,     // zamiast height, np. '16 / 9' (wtedy height: null)
  layers: 3,             // liczba rzędów
  imagesPerLayer: 24,    // zdjęć na obwodzie jednego rzędu
  imageHeight: 2.4,      // wysokość zdjęcia (jednostki sceny)
  aspect: 1.5,           // proporcje zdjęć (szer./wys.), zdjęcia kadrowane "cover"
  gap: 1.4,              // odstęp między zdjęciami wzdłuż obwodu
  layerGap: 0.4,         // odstęp pionowy między rzędami
  diameter: null,        // średnica koła; null = wylicz z liczby zdjęć i odstępu
  images: [],            // adresy zdjęć; puste = kolorowe zdjęcia zastępcze
  imageAlts: [],         // teksty alt odpowiadające images[] (popup / dostępność)
  zoom: 1,               // zbliżenie kamery (>1 bliżej)
  cameraHeight: 1,       // 0 = kamera na wysokości zdjęć, >1 = patrzy z góry
  maxPixelRatio: 2,      // limit gęstości pikseli (niżej = lżej dla telefonów)
  wheel: 'hover',        // 'hover' rolka nad karuzelą | 'ctrl' Ctrl + rolka | 'off'
  scrollSpeed: 0.0018,   // czułość rolki (rad/px)
  smoothing: 6,          // płynność dochodzenia do celu
  snap: false,           // po przewinięciu dociągnij do najbliższego zdjęcia
  autoplay: false,
  autoplaySpeed: 0.07,   // rad/s
  pauseOnHover: true,    // autoplay stoi, gdy kursor jest nad karuzelą
  dim: 1,                // siła przyciemnienia zdjęć z tyłu (0 = brak, 1 = pełna)
  focusSpeed: 9,         // szybkość ustawiania rzędów po kliknięciu
  popup: true,           // klik otwiera powiększenie
  popupDelay: 380,       // ms
  buttons: true,         // przyciski prev / next
  bgColor: '#140c2b',
  transparent: false,    // true = bez koloru tła, tylko mgła na tle strony
  mistColors: ['#8b3dff', '#ff4fb8'],
  mistSpeed: 1,          // 0 = mgła stoi
  mistIntensity: 1,      // 0 = bez mgły
  labels: { prev: 'Poprzednie', next: 'Następne', close: 'Zamknij' },
  onSelect: null         // funkcja({layer, index, src}) po kliknięciu zdjęcia
};

/* ================= KARUZELA ================= */
function createCarousel(host, opts) {
  const C = Object.assign({}, DEFAULTS, opts);
  C.labels = Object.assign({}, DEFAULTS.labels, opts && opts.labels);
  const TWO = Math.PI * 2;
  const REBUILD = ['layers', 'imagesPerLayer', 'imageHeight', 'aspect', 'gap', 'layerGap', 'images'];
  const reduce = !!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
  const px = v => typeof v === 'number' ? v + 'px' : v;
  const wrap = a => ((a + Math.PI) % TWO + TWO) % TWO - Math.PI;

  let L, N, W, H, STEP, totalH, R, Rt, geo;
  let zoom = C.zoom, zoomT = C.zoom, cur = 0, target = 0;
  let hover = false, dragX = null, down = null;
  let popupTimer = 0, autoPause = 0, snapAt = 0, raf = 0, visible = true, dead = false;
  const layers = [];

  function dims() {
    L = Math.max(1, C.layers | 0); N = Math.max(3, C.imagesPerLayer | 0);
    H = C.imageHeight; W = H * C.aspect; STEP = TWO / N;
    totalH = L * H + (L - 1) * C.layerGap;
  }
  const minR = () => N * W / TWO;   // najmniejszy promień bez nakładania zdjęć
  dims();
  R = Rt = Math.max(C.diameter != null ? C.diameter / 2 : N * (W + C.gap) / TWO, minR());

  /* DOM */
  host.classList.add('c3d'); host.tabIndex = 0;
  if (C.height != null) host.style.height = px(C.height);
  if (C.aspectRatio) host.style.aspectRatio = C.aspectRatio;
  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.outputEncoding = THREE.sRGBEncoding;
  const cv = renderer.domElement; host.appendChild(cv);
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(50, 1, 0.1, 200);
  const clock = new THREE.Clock();

  function mkBtn(cls, label, d, fn) {
    const b = document.createElement('button');
    b.className = 'c3d-nav ' + cls; b.setAttribute('aria-label', label); b.hidden = !C.buttons;
    b.innerHTML = '<svg viewBox="0 0 24 24"><path d="' + d + '"/></svg>';
    b.addEventListener('click', fn); host.appendChild(b); return b;
  }
  const bPrev = mkBtn('c3d-prev', C.labels.prev, 'M15 5l-7 7 7 7', () => go(1));
  const bNext = mkBtn('c3d-next', C.labels.next, 'M9 5l7 7-7 7', () => go(-1));

  const lb = document.createElement('div');
  lb.className = 'c3d-lb'; lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-modal', 'true'); lb.setAttribute('aria-hidden', 'true');
  lb.innerHTML = '<div class="c3d-lbf"><img alt=""><button class="c3d-lbx">×</button></div>';
  document.body.appendChild(lb);
  const lbImg = lb.querySelector('img'), lbX = lb.querySelector('button');
  lbX.setAttribute('aria-label', C.labels.close);

  /* tekstury */
  const maxAniso = renderer.capabilities.getMaxAnisotropy();
  const prep = t => { t.encoding = THREE.sRGBEncoding; t.anisotropy = maxAniso; return t; };
  const phCache = {}, phSet = new Set();   // tylko 24 różne tekstury zastępcze
  function placeholder(i) {
    i %= 24;
    if (phCache[i]) return phCache[i];
    const c = document.createElement('canvas');
    c.width = 768; c.height = Math.round(768 / C.aspect);
    const g = c.getContext('2d'), h = (i * 47) % 360;
    const gr = g.createLinearGradient(0, 0, c.width, c.height);
    gr.addColorStop(0, 'hsl(' + h + ',70%,58%)'); gr.addColorStop(1, 'hsl(' + ((h + 60) % 360) + ',70%,28%)');
    g.fillStyle = gr; g.fillRect(0, 0, c.width, c.height);
    g.fillStyle = 'rgba(255,255,255,.18)';
    g.beginPath(); g.arc(c.width * .75, c.height * .3, c.height * .35, 0, 7); g.fill();
    g.beginPath(); g.arc(c.width * .2, c.height, c.height * .5, 0, 7); g.fill();
    g.fillStyle = '#fff'; g.font = '700 ' + Math.round(c.height * .4) + 'px system-ui,sans-serif';
    g.textAlign = 'center'; g.textBaseline = 'middle';
    g.fillText(String(i + 1).padStart(2, '0'), c.width / 2, c.height / 2);
    const t = prep(new THREE.CanvasTexture(c)); phCache[i] = t; phSet.add(t); return t;
  }
  const loader = new THREE.TextureLoader(); loader.setCrossOrigin('anonymous');
  function loadInto(mat, url) {
    loader.load(url, tex => {
      if (dead) { tex.dispose(); return; }
      prep(tex);
      const ia = tex.image.width / tex.image.height;   // kadrowanie "cover"
      if (ia > C.aspect) { tex.repeat.x = C.aspect / ia; tex.offset.x = (1 - tex.repeat.x) / 2; }
      else { tex.repeat.y = ia / C.aspect; tex.offset.y = (1 - tex.repeat.y) / 2; }
      if (mat.map && !phSet.has(mat.map)) mat.map.dispose();
      mat.map = tex; mat.needsUpdate = true;
    });
  }

  /* tło: animowana mgła (shader na całym kontenerze) */
  const mistU = {
    uTime: { value: 0 }, uRes: { value: new THREE.Vector2(1, 1) },
    uBase: { value: new THREE.Color(C.bgColor) },
    uC1: { value: new THREE.Color(C.mistColors[0]) }, uC2: { value: new THREE.Color(C.mistColors[1]) },
    uInt: { value: C.mistIntensity }, uTr: { value: 0 }
  };
  const mistMat = new THREE.ShaderMaterial({
    uniforms: mistU, depthTest: false, depthWrite: false,
    vertexShader: 'void main(){ gl_Position = vec4(position.xy, 0.0, 1.0); }',
    fragmentShader: `
uniform float uTime, uInt, uTr; uniform vec2 uRes; uniform vec3 uBase, uC1, uC2;
float hash(vec2 p){ p = fract(p * vec2(123.34, 456.21)); p += dot(p, p + 45.32); return fract(p.x * p.y); }
float noise(vec2 p){
  vec2 i = floor(p), f = fract(p); f = f * f * (3.0 - 2.0 * f);
  return mix(mix(hash(i), hash(i + vec2(1.0, 0.0)), f.x), mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), f.x), f.y);
}
float fbm(vec2 p){
  float v = 0.0, a = 0.5; mat2 m = mat2(1.6, 1.2, -1.2, 1.6);
  for (int i = 0; i < 4; i++) { v += a * noise(p); p = m * p; a *= 0.5; }
  return v;
}
void main(){
  vec2 uv = gl_FragCoord.xy / uRes.y; float t = uTime;
  vec2 p1 = uv * 1.2 + vec2(t * 0.09, t * 0.015);
  vec2 q1 = vec2(fbm(p1 + vec2(0.0, t * 0.05)), fbm(p1 + vec2(5.2, 1.3) - t * 0.03));
  float f1 = fbm(p1 + 1.8 * q1);
  vec2 p2 = uv * 1.8 + vec2(-t * 0.12, t * 0.025) + 7.3;
  vec2 q2 = vec2(fbm(p2 + t * 0.06), fbm(p2 - t * 0.04 + 3.1));
  float f2 = fbm(p2 + 1.5 * q2);
  float a1 = smoothstep(0.25, 0.8, f1) * 0.8 * uInt, a2 = smoothstep(0.3, 0.8, f2) * 0.65 * uInt;
  if (uTr > 0.5) { gl_FragColor = vec4(uC1 * a1 * (1.0 - a2) + uC2 * a2, 1.0 - (1.0 - a1) * (1.0 - a2)); return; }
  vec3 col = mix(mix(uBase, uC1, a1), uC2, a2);
  vec2 c = gl_FragCoord.xy / uRes - 0.5; c.x *= uRes.x / uRes.y;
  col += uC1 * 0.10 * uInt * (1.0 - smoothstep(0.0, 0.7, length(c)));
  gl_FragColor = vec4(min(col, vec3(1.0)), 1.0);
}`
  });
  const mistGeo = new THREE.BufferGeometry();
  mistGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array([-1, -1, 0, 3, -1, 0, -1, 3, 0]), 3));
  const mist = new THREE.Mesh(mistGeo, mistMat);
  mist.frustumCulled = false; mist.renderOrder = -1000; scene.add(mist);
  function applyBg() {
    mistU.uBase.value.set(C.bgColor); mistU.uTr.value = C.transparent ? 1 : 0;
    host.style.backgroundColor = C.transparent ? 'transparent' : C.bgColor;
  }

  /* zdjęcia na okręgach */
  function build() {
    for (const l of layers) {
      scene.remove(l.group);
      for (const m of l.meshes) { if (m.material.map && !phSet.has(m.material.map)) m.material.map.dispose(); m.material.dispose(); }
    }
    layers.length = 0;
    if (geo) geo.dispose();
    geo = new THREE.PlaneGeometry(W, H);
    for (let k = 0; k < L; k++) {
      const group = new THREE.Group(), meshes = [];
      const dir = ((L - 1 - k) % 2 === 0) ? 1 : -1;   // sąsiednie rzędy jadą w przeciwne strony
      group.position.y = (k - (L - 1) / 2) * (H + C.layerGap);
      for (let i = 0; i < N; i++) {
        const idx = k * N + i;
        const mat = new THREE.MeshBasicMaterial({ map: placeholder(idx), side: THREE.DoubleSide });
        const src = C.images.length ? C.images[idx % C.images.length] : null;
        const alt = (C.imageAlts && C.imageAlts.length && src)
          ? (C.imageAlts[idx % C.imageAlts.length] || '')
          : '';
        if (src) loadInto(mat, src);
        const mesh = new THREE.Mesh(geo, mat), a = i * STEP + (k % 2 ? STEP / 2 : 0);
        mesh.position.set(Math.sin(a) * R, 0, Math.cos(a) * R); mesh.rotation.y = a;
        mesh.userData = { angle: a, layer: k, index: i, src: src, alt: alt };
        group.add(mesh); meshes.push(mesh);
      }
      scene.add(group); layers.push({ group, dir, meshes, adj: 0, adjT: 0 });
    }
  }
  function setRadius() {
    for (const l of layers) for (const m of l.meshes) m.position.set(Math.sin(m.userData.angle) * R, 0, Math.cos(m.userData.angle) * R);
    placeCamera();
  }

  /* kamera i rozmiar kontenera */
  function placeCamera() {
    const dist = (7 + R * 0.5 + totalH * 0.5) * Math.max(1, 1.6 / camera.aspect) / zoom;
    camera.position.set(0, (1.5 + totalH * 0.18) * C.cameraHeight, R + dist);
    camera.lookAt(0, 0, R);   // środek frontu karuzeli = środek kontenera
  }
  function fit() {
    const w = host.clientWidth || 300, h = host.clientHeight || 300;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, C.maxPixelRatio));
    renderer.setSize(w, h, false);
    renderer.getDrawingBufferSize(mistU.uRes.value);
    camera.aspect = w / h; camera.updateProjectionMatrix(); placeCamera();
  }

  /* sterowanie */
  function go(n) { target = Math.round(target / STEP) * STEP + n * STEP; }
  host.addEventListener('wheel', e => {
    if (C.wheel === 'off' || isOpen()) return;
    if (C.wheel === 'ctrl' && !(e.ctrlKey || e.metaKey)) return;   // bez Ctrl strona przewija się normalnie
    e.preventDefault();
    const d = (Math.abs(e.deltaY) >= Math.abs(e.deltaX) ? e.deltaY : e.deltaX) * (e.deltaMode === 1 ? 16 : 1);
    target -= d * C.scrollSpeed; snapAt = performance.now() + 180;
  }, { passive: false });
  host.addEventListener('keydown', e => {
    if (e.key === 'ArrowRight') go(-1);
    if (e.key === 'ArrowLeft') go(1);
  });
  host.addEventListener('pointerenter', () => { hover = true; });
  host.addEventListener('pointerleave', () => { hover = false; });

  const ray = new THREE.Raycaster(), ndc = new THREE.Vector2();
  function pick(e) {
    const r = cv.getBoundingClientRect();
    ndc.set(((e.clientX - r.left) / r.width) * 2 - 1, -((e.clientY - r.top) / r.height) * 2 + 1);
    ray.setFromCamera(ndc, camera);
    const hit = ray.intersectObjects(layers.flatMap(l => l.meshes), false)[0];
    return hit ? hit.object : null;
  }
  cv.addEventListener('pointerdown', e => {
    dragX = e.clientX; down = { x: e.clientX, y: e.clientY, t: performance.now() };
    cv.style.cursor = ''; cv.setPointerCapture(e.pointerId);
  });
  cv.addEventListener('pointermove', e => {
    if (dragX === null) { cv.style.cursor = pick(e) ? 'pointer' : ''; return; }
    target += (e.clientX - dragX) * 0.005; dragX = e.clientX;
  });
  cv.addEventListener('pointerup', e => {
    const d = down; dragX = null; down = null; snapAt = performance.now() + 180;
    if (d && Math.hypot(e.clientX - d.x, e.clientY - d.y) < 6 && performance.now() - d.t < 600) {
      const m = pick(e); if (m) focusOn(m);
    }
  });
  cv.addEventListener('pointercancel', () => { dragX = null; down = null; });

  /* ustawianie rzędów + popup */
  function align(mesh) {   // każdy rząd obraca się najkrótszą drogą, by jedno jego zdjęcie stanęło na środku
    for (let k = 0; k < layers.length; k++) {
      const l = layers[k], rot = l.dir * target + l.adjT;
      let d;
      if (k === mesh.userData.layer) d = wrap(mesh.userData.angle + rot);
      else { d = Infinity; for (const m of l.meshes) { const x = wrap(m.userData.angle + rot); if (Math.abs(x) < Math.abs(d)) d = x; } }
      l.adjT -= d;
    }
  }
  function focusOn(mesh) {
    align(mesh);
    const u = mesh.userData;
    if (C.onSelect) C.onSelect({ layer: u.layer, index: u.index, src: u.src });
    if (!C.popup) return;
    autoPause = performance.now() + C.popupDelay + 600;
    clearTimeout(popupTimer); popupTimer = setTimeout(() => openPopup(mesh), C.popupDelay);
  }
  const isOpen = () => lb.classList.contains('open');
  const onKey = e => { if (e.key === 'Escape') closePopup(); };
  function openPopup(mesh) {
    const img = mesh.material.map.image;
    lbImg.src = img.src || img.toDataURL();
    lbImg.alt = (mesh.userData && mesh.userData.alt) ? mesh.userData.alt : '';
    lb.classList.add('open'); lb.setAttribute('aria-hidden', 'false');
    document.addEventListener('keydown', onKey); lbX.focus({ preventScroll: true });
  }
  function closePopup() {
    clearTimeout(popupTimer);
    lb.classList.remove('open'); lb.setAttribute('aria-hidden', 'true');
    document.removeEventListener('keydown', onKey);
  }
  lb.addEventListener('click', e => { if (e.target === lb || e.target === lbX) closePopup(); });

  /* pętla: działa tylko, gdy karuzela jest widoczna na ekranie i karta jest aktywna */
  const wp = new THREE.Vector3();
  function shade() {   // zdjęcia z tyłu są przyciemnione
    for (const l of layers) for (const m of l.meshes) {
      m.getWorldPosition(wp);
      const nl = Math.hypot(wp.x, wp.z) || 1, tx = camera.position.x - wp.x, tz = camera.position.z - wp.z, tl = Math.hypot(tx, tz) || 1;
      const f = (wp.x * tx + wp.z * tz) / (nl * tl), b = f > 0 ? 0.6 + 0.4 * f : 0.3;
      m.material.color.setScalar(1 - C.dim * (1 - b));
    }
  }
  function frame() {
    raf = requestAnimationFrame(frame);
    const dt = Math.min(clock.getDelta(), 0.1), now = performance.now();
    mistU.uTime.value += dt * C.mistSpeed * (reduce ? 0.25 : 1);
    if (zoom !== zoomT) {
      zoom += (zoomT - zoom) * (1 - Math.exp(-dt * 8));
      if (Math.abs(zoomT - zoom) < 0.0005) zoom = zoomT;
      placeCamera();
    }
    if (R !== Rt) {
      R += (Rt - R) * (1 - Math.exp(-dt * 8));
      if (Math.abs(Rt - R) < 0.001) R = Rt;
      setRadius();
    }
    if (C.autoplay && !isOpen() && dragX === null && now > autoPause && !(C.pauseOnHover && hover)) target -= C.autoplaySpeed * dt;
    else if (C.snap && !C.autoplay && snapAt && now > snapAt && dragX === null) { target = Math.round(target / STEP) * STEP; snapAt = 0; }
    cur += (target - cur) * (1 - Math.exp(-dt * C.smoothing));
    for (const l of layers) {
      l.adj += (l.adjT - l.adj) * (1 - Math.exp(-dt * C.focusSpeed));
      l.group.rotation.y = l.dir * cur + l.adj;
    }
    shade(); renderer.render(scene, camera);
  }
  function sync() {
    const run = visible && !document.hidden && !dead;
    if (run && !raf) { clock.getDelta(); raf = requestAnimationFrame(frame); }
    if (!run && raf) { cancelAnimationFrame(raf); raf = 0; }
  }
  const onVis = () => sync();
  document.addEventListener('visibilitychange', onVis);
  const io = new IntersectionObserver(es => { visible = es[es.length - 1].isIntersecting; sync(); });
  io.observe(host);
  const ro = new ResizeObserver(fit); ro.observe(host);

  /* API */
  function update(o) {
    Object.assign(C, o);
    if (o.zoom != null) zoomT = o.zoom;
    if (o.diameter != null) Rt = Math.max(o.diameter / 2, minR());
    if (REBUILD.some(k => k in o)) { closePopup(); dims(); Rt = Math.max(Rt, minR()); build(); placeCamera(); }
    if (o.mistColors) { mistU.uC1.value.set(C.mistColors[0]); mistU.uC2.value.set(C.mistColors[1]); }
    if ('bgColor' in o || 'transparent' in o) applyBg();
    if ('mistIntensity' in o) mistU.uInt.value = C.mistIntensity;
    if ('buttons' in o) bPrev.hidden = bNext.hidden = !C.buttons;
    if ('height' in o) { host.style.height = C.height == null ? '' : px(C.height); fit(); }
    if ('aspectRatio' in o) { host.style.aspectRatio = C.aspectRatio || ''; fit(); }
  }
  function destroy() {
    dead = true; sync(); clearTimeout(popupTimer); closePopup();
    document.removeEventListener('visibilitychange', onVis); io.disconnect(); ro.disconnect();
    for (const l of layers) for (const m of l.meshes) { if (m.material.map && !phSet.has(m.material.map)) m.material.map.dispose(); m.material.dispose(); }
    phSet.forEach(t => t.dispose()); geo.dispose(); mistGeo.dispose(); mistMat.dispose();
    renderer.dispose(); renderer.forceContextLoss();
    cv.remove(); bPrev.remove(); bNext.remove(); lb.remove(); host.classList.remove('c3d');
  }

  fit(); applyBg(); build(); sync();
  return {
    next: () => go(-1), prev: () => go(1), update, destroy,
    goTo(index, layer) { const l = layers[layer || 0], m = l && l.meshes[((index % N) + N) % N]; if (m) align(m); },
    info: () => ({ diameter: Math.round(Rt * 2), minDiameter: Math.ceil(N * W / Math.PI), imagesPerLayer: N, layers: L })
  };
}

  global.DC3DCarousel = {
    DEFAULTS: DEFAULTS,
    create: createCarousel
  };
})(typeof window !== 'undefined' ? window : this);
