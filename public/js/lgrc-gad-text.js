(function () {
  var script = document.currentScript;
  var page = script ? script.getAttribute('data-page') : '';
  var scopes = {
    'about-lgrc': 'main > [class*="lgrc-"]',
    'about-gad': 'main > .gad-hero, main > .gad-page-panel',
    'gad-corner': 'main > .gad-corner-panel',
    'msac': 'main > .msac-page'
  };
  if (!page || !scopes[page] || !script) return;

  function getTextNodes() {
    var nodes = [];
    document.querySelectorAll(scopes[page]).forEach(function (scope) {
      var walker = document.createTreeWalker(scope, NodeFilter.SHOW_TEXT);
      var node;
      while ((node = walker.nextNode())) {
        if (!node.nodeValue.trim()) continue;
        if (node.parentElement && node.parentElement.closest('script, style, svg, button')) continue;
        nodes.push(node);
      }
    });
    return nodes;
  }

  var apiUrl = new URL('../../auth/lgrc_gad_text_api.php?page=' + encodeURIComponent(page), script.src);
  fetch(apiUrl.toString(), { cache: 'no-store' })
    .then(function (response) {
      if (!response.ok) throw new Error('Page text could not be loaded.');
      return response.json();
    })
    .then(function (result) {
      if (!Array.isArray(result.fields)) throw new Error('Page text response is invalid.');
      var nodes = getTextNodes();
      result.fields.forEach(function (field, index) {
        if (field.overridden && nodes[index] && typeof field.value === 'string') {
          nodes[index].nodeValue = field.value;
        }
      });
      if (page === 'about-lgrc' && result.logo_url) {
        var logo = document.querySelector('main > .lgrc-page-panel .lgrc-image-col img');
        if (logo) logo.src = new URL(result.logo_url, window.location.href).toString();
      }
    })
    .catch(function (error) {
      console.error('Unable to load LGRC and GAD text updates:', error);
    });
})();
