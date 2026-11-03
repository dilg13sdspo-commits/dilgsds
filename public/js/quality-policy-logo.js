(function () {
  var script = document.currentScript;
  if (!script) return;

  var apiUrl = new URL('../../auth/quality_policy_logo_api.php', script.src);
  fetch(apiUrl.toString(), { cache: 'no-store' })
    .then(function (response) {
      if (!response.ok) throw new Error('Quality Policy logo could not be loaded.');
      return response.json();
    })
    .then(function (result) {
      if (!result.logo_url) throw new Error('Quality Policy logo response is invalid.');
      document.querySelectorAll('.topbar .logo-row img.header-logo:first-child, .quality-logo img').forEach(function (image) {
        image.src = result.logo_url;
      });
    })
    .catch(function (error) {
      console.error('Unable to load the Quality Policy logo:', error);
    });
})();
