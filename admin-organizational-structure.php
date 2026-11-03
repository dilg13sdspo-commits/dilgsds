<?php
require_once __DIR__ . '/auth/middleware.php';
require_role('ADMIN');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Organizational Structure Management - DILG SDS</title>
  <link rel="stylesheet" href="styles.css?v=20261103-org-structure-upload">
  <style>
    .org-admin { width: 100%; }
    .org-admin h1 { margin: 0 0 6px; color: #0f2a6b; }
    .org-admin > p { margin: 0 0 22px; color: #64748b; }
    .org-admin-panel { max-width: 1080px; padding: 22px; border: 1px solid #dfe7f0; border-radius: 12px; background: #fff; box-shadow: 0 8px 24px rgba(15,42,107,.06); }
    .org-admin-panel h2 { margin: 0 0 8px; color: #0f2a6b; font-size: 1.1rem; }
    .org-admin-panel > p { margin: 0 0 16px; color: #64748b; font-size: .86rem; }
    .org-preview { display: grid; place-items: center; min-height: 240px; padding: 12px; border: 1px solid #dfe7f0; border-radius: 8px; background: #f8fafc; }
    .org-preview img { display: block; max-width: 100%; max-height: 62vh; object-fit: contain; }
    .org-upload { display: block; margin-top: 16px; color: #334155; font-size: .8rem; font-weight: 700; }
    .org-upload input { display: block; width: 100%; margin-top: 7px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 7px; background: #fff; font: inherit; }
    .org-upload-hint { display: block; margin-top: 5px; color: #64748b; font-size: .72rem; font-weight: 400; }
    .org-form-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 16px; }
    .org-form-actions button { min-height: 38px; padding: 9px 14px; border: 0; border-radius: 7px; font: inherit; font-size: .8rem; font-weight: 700; cursor: pointer; }
    #org-upload-submit { background: #1f5fcf; color: #fff; }
    #org-upload-submit:disabled { opacity: .6; cursor: wait; }
    #org-upload-reset { background: #e2e8f0; color: #334155; }
    .org-upload-status { min-height: 22px; margin-top: 12px; color: #475569; font-size: .8rem; font-weight: 700; }
    .org-upload-status.is-error { color: #b42318; }
    .org-upload-status.is-success { color: #137a48; }
    @media (max-width: 620px) { .org-admin-panel { padding: 16px; } .org-preview { min-height: 160px; } }
  </style>
</head>
<body class="admin-dashboard-ref-body">
  <div class="admin-portal-shell">
    <?php include __DIR__ . '/admin-sidebar.php'; ?>
    <main class="admin-main">
      <section class="org-admin">
        <h1>Organizational Structure</h1>
        <p>Upload the chart image displayed on the public Organizational Structure page.</p>
        <section class="org-admin-panel" aria-labelledby="org-current-title">
          <h2 id="org-current-title">Current image</h2>
          <p>Upload a replacement image to publish it on the website. The previous image will remain unchanged unless the upload succeeds.</p>
          <div class="org-preview"><img id="org-preview-image" alt="Current DILG Surigao del Sur organizational structure" hidden></div>
          <form id="org-upload-form" enctype="multipart/form-data">
            <label class="org-upload" for="org-image-file">
              Choose replacement image
              <input id="org-image-file" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" required>
              <span class="org-upload-hint">JPG, PNG, GIF, or WEBP; maximum size 10 MB.</span>
            </label>
            <div class="org-form-actions">
              <button id="org-upload-reset" type="button">Cancel selection</button>
              <button id="org-upload-submit" type="submit">Upload and publish</button>
            </div>
            <p class="org-upload-status" id="org-upload-status" role="status" aria-live="polite">Loading current image...</p>
          </form>
        </section>
      </section>
    </main>
  </div>
  <script>
    (function () {
      var api = 'auth/organizational_structure_api.php';
      var form = document.getElementById('org-upload-form');
      var fileInput = document.getElementById('org-image-file');
      var preview = document.getElementById('org-preview-image');
      var status = document.getElementById('org-upload-status');
      var submit = document.getElementById('org-upload-submit');
      var objectUrl = '';

      function setStatus(message, kind) {
        status.textContent = message;
        status.className = 'org-upload-status' + (kind ? ' is-' + kind : '');
      }

      function showImage(path) {
        preview.src = path + (path.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now();
        preview.hidden = false;
      }

      fetch(api, { cache: 'no-store' })
        .then(function (response) {
          if (!response.ok) throw new Error('Unable to load current organizational structure image.');
          return response.json();
        })
        .then(function (data) {
          if (!data.image_url) throw new Error('No organizational structure image is configured.');
          showImage(data.image_url);
          setStatus('Current image loaded.');
        })
        .catch(function (error) {
          console.error(error);
          setStatus(error.message, 'error');
        });

      fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file) return;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.hidden = false;
        setStatus('Previewing ' + file.name + '. Select Upload and publish to save it.');
      });

      document.getElementById('org-upload-reset').addEventListener('click', function () {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = '';
        fileInput.value = '';
        fetch(api, { cache: 'no-store' })
          .then(function (response) {
            if (!response.ok) throw new Error('Unable to reload current image.');
            return response.json();
          })
          .then(function (data) { showImage(data.image_url); setStatus('Selection canceled.'); })
          .catch(function (error) { console.error(error); setStatus(error.message, 'error'); });
      });

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!fileInput.files || !fileInput.files.length) {
          setStatus('Choose an image before uploading.', 'error');
          return;
        }
        submit.disabled = true;
        setStatus('Uploading image...');
        fetch(api, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } })
          .then(function (response) {
            return response.json().then(function (data) {
              if (!response.ok) throw new Error(data.error || 'Unable to upload image.');
              return data;
            });
          })
          .then(function (data) {
            if (!data.image_url) throw new Error('Upload completed without an image path.');
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = '';
            fileInput.value = '';
            showImage(data.image_url);
            setStatus('Organizational structure image uploaded and published.', 'success');
          })
          .catch(function (error) {
            console.error(error);
            setStatus(error.message || 'Unable to upload image.', 'error');
          })
          .finally(function () { submit.disabled = false; });
      });
    }());
  </script>
</body>
</html>
