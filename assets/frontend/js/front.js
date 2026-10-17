/**
 * Design Cart 3D Carousel
 * Autor: Paweł Nosko → https://www.designcart.pl/pawel-nosko.html
 * Firma: Design Cart → https://www.designcart.pl/
 */

(function () {
  "use strict";

  function parseConfig(el) {
    var raw = el.getAttribute("data-config");
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function initOne(el) {
    if (!el || el._dc3d) return;
    if (!window.DC3DCarousel || typeof window.DC3DCarousel.create !== "function") return;
    if (typeof THREE === "undefined") return;

    var config = parseConfig(el);
    if (!config) return;

    el._dc3d = window.DC3DCarousel.create(el, config);
  }

  function initAll() {
    document.querySelectorAll("[data-dc-3d-carousel]").forEach(initOne);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }
})();
