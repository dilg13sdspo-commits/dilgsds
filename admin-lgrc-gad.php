<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');

$pages = [
    'about-lgrc' => ['label' => 'About LGRC', 'public' => 'about-lgrc.html'],
    'about-gad' => ['label' => 'About GAD', 'public' => 'about-gad.html'],
    'gad-corner' => ['label' => 'GAD Corner', 'public' => 'gad-corner.html'],
    'msac' => ['label' => 'MSAC', 'public' => 'msac.html'],
];
$pageKey = trim((string)($_GET['page'] ?? 'about-lgrc'));
if (!isset($pages[$pageKey])) {
    http_response_code(404);
    exit('Unknown LGRC and GAD page.');
}
$page = $pages[$pageKey];
$isBackendAdmin = strpos(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/backend/api/') !== false;
$apiUrl = $isBackendAdmin ? '../../auth/lgrc_gad_text_api.php' : 'auth/lgrc_gad_text_api.php';
$publicUrl = $isBackendAdmin ? '../../public/pages/' . $page['public'] : 'public/pages/' . $page['public'];
$stylesheetUrl = $isBackendAdmin ? '../../styles.css?v=20260831-lgrc-gad-text' : 'styles.css?v=20260831-lgrc-gad-text';
$defaultLogoUrl = $isBackendAdmin ? '../../lgrc-aurora-logo.jpg' : 'lgrc-aurora-logo.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page['label'], ENT_QUOTES, 'UTF-8'); ?> Text Editor</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($stylesheetUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <style>
    .lgrc-text-admin { max-width: 1100px; margin: 0 auto; padding: 28px; color: #17233c; }
    .lgrc-text-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 22px; }
    .lgrc-text-header h1 { margin: 0 0 8px; color: #102b65; font-size: 1.8rem; }
    .lgrc-text-header p { margin: 0; color: #5e6b83; line-height: 1.55; }
    .lgrc-text-actions { display: flex; flex-wrap: wrap; gap: 10px; }
    .lgrc-text-button { min-height: 42px; padding: 10px 16px; border: 0; border-radius: 8px; background: #174b9b; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
    .lgrc-text-button:disabled { opacity: .6; cursor: wait; }
    .lgrc-text-button--secondary { background: #e7edf7; color: #18345e; }
    .lgrc-text-tools { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; padding: 14px 16px; border: 1px solid #dfe6f0; border-radius: 10px; background: #f7f9fc; }
    .lgrc-text-status { min-height: 1.4em; color: #42526d; font-weight: 600; }
    .lgrc-text-status[data-state="error"] { color: #a52323; }
    .lgrc-text-status[data-state="success"] { color: #167043; }
    .lgrc-text-list { display: grid; gap: 14px; }
    .lgrc-text-field { padding: 16px; border: 1px solid #dfe6f0; border-radius: 10px; background: #fff; box-shadow: 0 2px 8px rgba(20, 42, 77, .04); }
    .lgrc-text-field label { display: block; margin-bottom: 7px; color: #18345e; font-weight: 700; }
    .lgrc-text-context { display: block; margin-bottom: 9px; color: #64728a; font-size: .88rem; line-height: 1.45; overflow-wrap: anywhere; }
    .lgrc-text-field textarea { display: block; width: 100%; min-height: 68px; box-sizing: border-box; padding: 10px 12px; border: 1px solid #c9d3e2; border-radius: 7px; color: #18243a; font: inherit; line-height: 1.5; resize: vertical; }
    .lgrc-text-field textarea:focus { border-color: #2764bc; outline: 3px solid rgba(39, 100, 188, .15); }
    .lgrc-text-empty { padding: 22px; color: #5e6b83; text-align: center; }
    .lgrc-logo-upload { display: flex; align-items: center; gap: 22px; margin: 0 0 22px; padding: 18px; border: 1px solid #dfe6f0; border-radius: 10px; background: #fff; }
    .lgrc-logo-upload__preview { display: grid; place-items: center; width: 160px; height: 130px; flex: 0 0 160px; overflow: hidden; border: 1px solid #e1e7f0; border-radius: 8px; background: #f7f9fc; }
    .lgrc-logo-upload__preview img { display: block; max-width: 100%; max-height: 100%; object-fit: contain; }
    .lgrc-logo-upload__content { min-width: 0; }
    .lgrc-logo-upload__content h2 { margin: 0 0 6px; color: #18345e; font-size: 1.1rem; }
    .lgrc-logo-upload__content p { margin: 0 0 12px; color: #5e6b83; line-height: 1.5; }
    .lgrc-logo-file { display: inline-block; margin: 0 0 10px; color: #174b9b; font-weight: 700; cursor: pointer; }
    #lgrc-logo-file { display: block; max-width: 100%; margin-bottom: 12px; }
    .lgrc-logo-actions { display: flex; flex-wrap: wrap; gap: 10px; }
    @media (max-width: 700px) {
      .lgrc-text-admin { padding: 20px 14px; }
      .lgrc-text-header, .lgrc-text-tools { align-items: stretch; flex-direction: column; }
      .lgrc-text-actions { width: 100%; }
      .lgrc-text-button { flex: 1; }
      .lgrc-logo-upload { align-items: stretch; flex-direction: column; }
      .lgrc-logo-upload__preview { width: 100%; max-width: 240px; flex-basis: 130px; }
    }
  </style>
</head>
<body class="admin-dashboard-ref-body">
  <div class="admin-portal-shell">
    <?php include $isBackendAdmin ? __DIR__ . '/backend/api/admin-sidebar.php' : __DIR__ . '/admin-sidebar.php'; ?>
    <main class="admin-main">
      <section class="lgrc-text-admin">
        <header class="lgrc-text-header">
          <div>
            <h1><?php echo htmlspecialchars($page['label'], ENT_QUOTES, 'UTF-8'); ?> Content</h1>
            <p>Edit the existing headings and text only. Images, page layout, links, and embedded documents will not be changed.</p>
          </div>
          <a class="lgrc-text-button lgrc-text-button--secondary" href="<?php echo htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">View public page</a>
        </header>
        <div class="lgrc-text-tools">
          <div id="lgrc-text-status" class="lgrc-text-status" role="status" aria-live="polite">Loading page text…</div>
          <div class="lgrc-text-actions">
            <button type="button" class="lgrc-text-button lgrc-text-button--secondary" id="lgrc-text-reset" disabled>Reset to original</button>
            <button type="button" class="lgrc-text-button" id="lgrc-text-save" disabled>Save text changes</button>
          </div>
        </div>
        <?php if ($pageKey === 'about-lgrc') : ?>
          <section class="lgrc-logo-upload" aria-labelledby="lgrc-logo-heading">
            <div class="lgrc-logo-upload__preview">
              <img id="lgrc-logo-preview" src="<?php echo htmlspecialchars($defaultLogoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Current About LGRC logo">
            </div>
            <div class="lgrc-logo-upload__content">
              <h2 id="lgrc-logo-heading">About LGRC Logo</h2>
              <p>Upload a replacement logo. This changes only the logo image, not the page layout or text. Accepted formats: JPG, PNG, WebP (up to 5 MB).</p>
              <label class="lgrc-logo-file" for="lgrc-logo-file">Choose logo image</label>
              <input id="lgrc-logo-file" type="file" accept="image/jpeg,image/png,image/webp">
              <div class="lgrc-logo-actions">
                <button type="button" class="lgrc-text-button" id="lgrc-logo-upload" disabled>Upload logo</button>
                <button type="button" class="lgrc-text-button lgrc-text-button--secondary" id="lgrc-logo-reset" disabled>Restore original logo</button>
              </div>
              <div id="lgrc-logo-status" class="lgrc-text-status" role="status" aria-live="polite"></div>
            </div>
          </section>
        <?php endif; ?>
        <form id="lgrc-text-form">
          <div class="lgrc-text-list" id="lgrc-text-fields"></div>
        </form>
      </section>
    </main>
  </div>
  <script>
    (function () {
      var apiUrl = <?php echo json_encode($apiUrl, JSON_UNESCAPED_SLASHES); ?>;
      var page = <?php echo json_encode($pageKey); ?>;
      var fieldsContainer = document.getElementById('lgrc-text-fields');
      var status = document.getElementById('lgrc-text-status');
      var saveButton = document.getElementById('lgrc-text-save');
      var resetButton = document.getElementById('lgrc-text-reset');
      var publicPageUrl = document.querySelector('.lgrc-text-header > a').href;
      var logoFile = document.getElementById('lgrc-logo-file');
      var logoPreview = document.getElementById('lgrc-logo-preview');
      var logoUploadButton = document.getElementById('lgrc-logo-upload');
      var logoResetButton = document.getElementById('lgrc-logo-reset');
      var logoStatus = document.getElementById('lgrc-logo-status');
      var form = document.getElementById('lgrc-text-form');
      var fields = [];

      function setStatus(message, state) {
        status.textContent = message;
        status.dataset.state = state || '';
      }

      function requestJson(url, options) {
        return fetch(url, options).then(function (response) {
          return response.json().catch(function () {
            throw new Error('The server returned an invalid response.');
          }).then(function (body) {
            if (!response.ok) throw new Error(body.error || 'The request could not be completed.');
            return body;
          });
        });
      }

      function renderFields(result) {
        fields = result.fields;
        if (logoPreview && result.logo_url) {
          logoPreview.src = new URL(result.logo_url, publicPageUrl).toString();
          logoResetButton.disabled = !result.has_custom_logo;
          logoUploadButton.disabled = false;
        }
        fieldsContainer.replaceChildren();
        if (!fields.length) {
          var empty = document.createElement('p');
          empty.className = 'lgrc-text-empty';
          empty.textContent = 'No editable text was found on this page.';
          fieldsContainer.appendChild(empty);
          setStatus('No page text is available to edit.', 'error');
          saveButton.disabled = true;
          resetButton.disabled = true;
          return;
        }

        fields.forEach(function (field, index) {
          var wrapper = document.createElement('div');
          wrapper.className = 'lgrc-text-field';

          var label = document.createElement('label');
          var inputId = 'lgrc-text-' + index;
          label.htmlFor = inputId;
          label.textContent = 'Text ' + (index + 1) + ' · ' + field.tag;

          var context = document.createElement('span');
          context.className = 'lgrc-text-context';
          context.textContent = 'Original: ' + (field.preview || '(blank text)');

          var textarea = document.createElement('textarea');
          textarea.id = inputId;
          textarea.name = field.key;
          textarea.value = field.value;
          textarea.setAttribute('aria-label', label.textContent);

          wrapper.appendChild(label);
          wrapper.appendChild(context);
          wrapper.appendChild(textarea);
          fieldsContainer.appendChild(wrapper);
        });
        saveButton.disabled = false;
        resetButton.disabled = false;
        setStatus(fields.length + ' text parts loaded. Changes are saved only when you choose Save.', '');
      }

      requestJson(apiUrl + '?page=' + encodeURIComponent(page), { cache: 'no-store' })
        .then(renderFields)
        .catch(function (error) {
          setStatus(error.message, 'error');
        });

      if (logoFile && logoPreview && logoUploadButton && logoResetButton && logoStatus) {
        logoFile.addEventListener('change', function () {
          var selected = logoFile.files && logoFile.files[0];
          logoUploadButton.disabled = !selected;
          logoStatus.textContent = selected ? selected.name : '';
          if (selected) {
            logoPreview.src = URL.createObjectURL(selected);
          }
        });

        logoUploadButton.addEventListener('click', function () {
          var selected = logoFile.files && logoFile.files[0];
          if (!selected) {
            logoStatus.textContent = 'Choose a logo image first.';
            return;
          }
          var formData = new FormData();
          formData.append('action', 'upload_logo');
          formData.append('logo', selected);
          logoUploadButton.disabled = true;
          logoResetButton.disabled = true;
          logoStatus.textContent = 'Uploading logo…';
          fetch(apiUrl + '?page=' + encodeURIComponent(page), {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
          }).then(function (response) {
            return response.json().then(function (body) {
              if (!response.ok) throw new Error(body.error || 'The logo could not be uploaded.');
              return body;
            });
          }).then(function (result) {
            logoPreview.src = new URL(result.logo_url, publicPageUrl).toString();
            logoFile.value = '';
            logoStatus.textContent = 'Logo uploaded. Refresh the public About LGRC page to see it.';
            logoResetButton.disabled = false;
          }).catch(function (error) {
            logoStatus.textContent = error.message;
            logoStatus.dataset.state = 'error';
          }).finally(function () {
            logoUploadButton.disabled = false;
          });
        });

        logoResetButton.addEventListener('click', function () {
          if (!window.confirm('Restore the original About LGRC logo?')) return;
          logoUploadButton.disabled = true;
          logoResetButton.disabled = true;
          logoStatus.textContent = 'Restoring original logo…';
          requestJson(apiUrl + '?page=' + encodeURIComponent(page), {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: new URLSearchParams({ action: 'reset_logo' })
          }).then(function () {
            return requestJson(apiUrl + '?page=' + encodeURIComponent(page), { cache: 'no-store' });
          }).then(function (result) {
            logoPreview.src = new URL(result.logo_url, publicPageUrl).toString();
            logoStatus.textContent = 'Original logo restored.';
            logoStatus.dataset.state = 'success';
            logoResetButton.disabled = true;
          }).catch(function (error) {
            logoStatus.textContent = error.message;
            logoStatus.dataset.state = 'error';
            logoResetButton.disabled = false;
          }).finally(function () {
            logoUploadButton.disabled = false;
          });
        });
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        saveButton.disabled = true;
        resetButton.disabled = true;
        setStatus('Saving text changes…', '');
        var values = {};
        fields.forEach(function (field, index) {
          values[field.key] = fieldsContainer.querySelector('#lgrc-text-' + index).value;
        });
        requestJson(apiUrl + '?page=' + encodeURIComponent(page), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ action: 'save', fields: values })
        }).then(function () {
          setStatus('Text changes saved. Refresh the public page to see them.', 'success');
        }).catch(function (error) {
          setStatus(error.message, 'error');
        }).finally(function () {
          saveButton.disabled = false;
          resetButton.disabled = false;
        });
      });

      resetButton.addEventListener('click', function () {
        if (!window.confirm('Reset all text on this page to the original wording?')) return;
        saveButton.disabled = true;
        resetButton.disabled = true;
        setStatus('Restoring the original text…', '');
        requestJson(apiUrl + '?page=' + encodeURIComponent(page), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ action: 'reset' })
        }).then(function () {
          return requestJson(apiUrl + '?page=' + encodeURIComponent(page), { cache: 'no-store' });
        }).then(function (result) {
          renderFields(result);
          setStatus('Original text restored.', 'success');
        }).catch(function (error) {
          setStatus(error.message, 'error');
        }).finally(function () {
          saveButton.disabled = false;
          resetButton.disabled = false;
        });
      });
    })();
  </script>
</body>
</html>
