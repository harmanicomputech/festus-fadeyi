/* Runs before first paint: enables motion styles unless the visitor prefers reduced motion.
   Safety net: content is revealed after 4s even if the animation scripts fail to load. */
(function () {
  var d = document.documentElement;
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  d.classList.add('js-motion');
  setTimeout(function () { d.classList.add('motion-ready'); }, 4000);
})();
