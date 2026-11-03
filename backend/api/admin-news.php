<?php
require_once __DIR__ . '/../auth/middleware.php';
require_role('ADMIN');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>News Management - DILG SDS</title>
<link rel="stylesheet" href="styles.css?v=20260828-news-cms-01">
<style>
.news-admin{max-width:1280px;margin:auto;width:100%}.news-admin h1{color:#0f2a6b;margin:0 0 6px}.news-admin>p{color:#64748b;margin:0 0 22px}.news-admin-grid{display:grid;grid-template-columns:minmax(280px,380px) 1fr;gap:22px;align-items:start}.news-form,.news-list{background:#fff;border:1px solid #dfe7f0;border-radius:12px;padding:22px;box-shadow:0 8px 24px rgba(15,42,107,.06)}.news-form h2,.news-list h2{color:#0f2a6b;margin:0 0 18px;font-size:1.15rem}.news-form label{display:block;color:#334155;font-weight:700;font-size:.78rem;margin:12px 0 5px}.news-form input,.news-form textarea,.news-form select{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:7px;padding:10px 11px;font:inherit;color:#1e293b}.news-form textarea{min-height:105px;resize:vertical}.news-form input[type=file]{padding:7px}.form-actions{display:flex;gap:9px;margin-top:18px}.form-actions button,.news-row button{border:0;border-radius:7px;padding:9px 13px;cursor:pointer;font-weight:700}#save-news{background:#1f5fcf;color:#fff}#clear-news{background:#e2e8f0;color:#334155}.news-row{display:grid;grid-template-columns:90px minmax(0,1fr) auto;gap:13px;align-items:center;padding:12px 0;border-bottom:1px solid #e2e8f0}.news-row:last-child{border-bottom:0}.news-row img{width:90px;height:58px;object-fit:cover;border-radius:7px;background:#eef3f8}.news-row strong{display:block;color:#1e293b}.news-row small{color:#64748b}.news-row button{background:#e8eef8;color:#0f2a6b;margin-left:5px}.news-row .delete{color:#b42318;background:#fff0ef}.status{color:#64748b;padding:18px 0;font-weight:700}@media(max-width:850px){.news-admin-grid{grid-template-columns:1fr}}
</style>
<style>
.news-admin { max-width: none; margin: 0; }
.news-admin-grid { display: block; }
.news-list { width: 100%; box-sizing: border-box; padding: 20px; }
.news-list-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.news-list-header h2 { margin: 0; }
.add-news { border: 1px solid #8db5f4; border-radius: 7px; background: #fff; color: #1f5fcf; padding: 9px 13px; font-weight: 700; cursor: pointer; }
.news-filters { display: grid; grid-template-columns: minmax(220px, 1fr) repeat(2, minmax(140px, 170px)); gap: 8px; margin-bottom: 12px; }
.news-filters label { display: flex; flex-direction: column; gap: 5px; color: #475569; font-size: .7rem; font-weight: 700; }
.news-filters input, .news-filters select { min-height: 36px; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 7px; padding: 8px 10px; font: inherit; color: #334155; background: #fff; }
.news-hint { margin-bottom: 10px; padding: 9px 11px; border: 1px solid #d9e6fa; border-radius: 6px; background: #f1f6ff; color: #2457a6; font-size: .72rem; font-weight: 700; }
.news-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
.news-table { width: 100%; min-width: 760px; border-collapse: collapse; table-layout: fixed; }
.news-table th { padding: 10px 9px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #64748b; text-align: left; font-size: .62rem; letter-spacing: .04em; text-transform: uppercase; }
.news-table td { padding: 10px 9px; border-bottom: 1px solid #e2e8f0; color: #334155; font-size: .74rem; vertical-align: middle; }
.news-table th:nth-child(1) { width: 7%; } .news-table th:nth-child(2) { width: 13%; } .news-table th:nth-child(3) { width: 35%; } .news-table th:nth-child(4) { width: 15%; } .news-table th:nth-child(5) { width: 13%; } .news-table th:nth-child(6) { width: 17%; }
.news-order { display: inline-grid; place-items: center; width: 27px; height: 27px; border-radius: 6px; background: #f1f5fa; color: #29466e; font-weight: 800; }
.news-thumb { width: 72px; height: 44px; display: block; object-fit: cover; border-radius: 6px; background: #eef3f8; }
.news-title { display: block; color: #1e365c; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.news-meta { display: block; margin-top: 3px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.news-status { display: inline-flex; align-items: center; gap: 5px; padding: 5px 8px; border-radius: 5px; background: #e4f7ec; color: #137a48; font-size: .65rem; font-weight: 800; }
.news-status:before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.news-status--draft { background: #fff2d5; color: #a96b00; }
.news-actions { display: flex; gap: 5px; justify-content: flex-end; }
.news-actions button { display: inline-flex; align-items: center; justify-content: center; width: 32px; min-height: 30px; padding: 6px; border: 1px solid #bdd1f4; border-radius: 6px; background: #fff; color: #1f5fcf; font-size: 0; cursor: pointer; }
.news-actions button svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.news-actions .delete { border-color: #f2bebe; color: #c33434; }
.news-row [data-edit], .news-row [data-delete] { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 30px; padding: 6px; font-size: 0; }
.news-row [data-edit]::before { content: '✎'; font-size: 16px; }
.news-row [data-delete]::before { content: '▣'; font-size: 15px; }
.news-table-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding-top: 12px; color: #64748b; font-size: .72rem; }
.news-pagination { display: flex; gap: 5px; }
.news-pagination button { width: 30px; height: 30px; border: 1px solid #d8e2ef; border-radius: 6px; background: #fff; color: #334155; cursor: pointer; }
.news-pagination button.active, .news-pagination button:hover { background: #2f6cdf; border-color: #2f6cdf; color: #fff; }
.news-editor { position: fixed; inset: 0 0 0 auto; z-index: 1100; width: min(400px, 100vw); overflow-y: auto; background: #fff; box-shadow: -10px 0 28px rgba(15,42,107,.16); transform: translateX(100%); visibility: hidden; transition: transform .22s ease, visibility .22s ease; }
.news-editor.is-open { transform: translateX(0); visibility: visible; }
.news-editor .news-form { min-height: 100vh; box-sizing: border-box; border: 0; border-radius: 0; box-shadow: none; padding: 18px 20px 0; }
.news-editor .news-form h2 { display: flex; align-items: center; justify-content: space-between; font-size: 1.05rem; }
.news-close { border: 0; background: transparent; color: #64748b; font-size: 1.4rem; cursor: pointer; }
.news-editor .news-form label { margin: 9px 0 4px; font-size: .73rem; }
.news-editor .news-form input, .news-editor .news-form textarea, .news-editor .news-form select { min-height: 36px; padding: 8px 10px; font-size: .82rem; }
.news-editor .news-form textarea { min-height: 80px; }
.news-editor .form-actions { position: sticky; bottom: 0; margin: 16px -20px 0; padding: 12px 20px 16px; border-top: 1px solid #e2e8f0; background: rgba(255,255,255,.98); }
.news-editor .image-preview { display: block; width: 100%; max-width: 160px; height: 90px; object-fit: cover; margin: 8px auto; border-radius: 7px; background: #eef3f8; }
.news-editor .news-upload { position: relative; display: block; padding: 12px; border: 1px dashed #b9c9df; border-radius: 8px; background: #f7faff; text-align: center; color: #46658e; cursor: pointer; }
.news-editor .news-upload input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; }
.news-editor .check-field { display: flex; align-items: center; gap: 8px; }.news-editor .check-field input { width: auto; min-height: auto; }
.news-editor .news-form { position: relative; }.news-editor .news-form h2 { padding-right: 28px; }.news-editor .news-close { position: absolute; top: 17px; right: 20px; }
.news-tabs { display: flex; gap: 4px; margin-bottom: 16px; padding: 4px; border: 1px solid #dfe7f0; border-radius: 8px; background: #fff; overflow-x: auto; }
.news-tab { flex: 1 0 auto; border: 0; border-bottom: 2px solid transparent; border-radius: 5px; background: transparent; color: #465d7e; padding: 10px 14px; font-size: .76rem; font-weight: 700; cursor: pointer; white-space: nowrap; }
.news-tabs { width: 100%; box-sizing: border-box; }
.news-tab { flex: 1 1 0; min-width: 0; text-align: center; }
.news-admin-grid, .news-list, .news-list-header, .news-filters, .news-hint, .news-table-wrap { width: 100%; box-sizing: border-box; }
.news-row { grid-template-columns: 90px minmax(0, 1fr) auto; min-height: 82px; box-sizing: border-box; }
.news-tab:hover, .news-tab.is-active { background: #edf4ff; border-bottom-color: #1f5fcf; color: #1f5fcf; }
.news-tab:focus { outline: none; }
.news-tab:focus-visible { outline: 2px solid #8db5f4; outline-offset: -2px; }
.news-tab.is-active:focus-visible { outline-color: #1f5fcf; }
.news-row > div:nth-child(2) { min-width: 0; }
.news-row strong { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.news-row small { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.news-row img { width: 90px; height: 58px; object-fit: cover; flex-shrink: 0; }
.news-row button { min-width: 0; min-height: 32px; padding: 8px 11px; }
.news-admin { max-width: 1280px; margin: 0 auto; width: 100%; }
.news-admin-grid { display: block; }
.news-list { width: 100%; }
.news-row-media { width: 90px; height: 58px; display: block; border: 0; border-radius: 7px; background: #eef3f8; object-fit: cover; }
@media(max-width:850px){.news-filters{grid-template-columns:1fr 1fr}.news-filters label:first-child{grid-column:1/-1}}@media(max-width:560px){.news-filters{grid-template-columns:1fr}.news-filters label:first-child{grid-column:auto}.news-list-header{align-items:flex-start;flex-direction:column}.news-table-footer{align-items:flex-start;flex-direction:column}}
.news-admin { max-width: 1280px; margin: 0 auto; width: 100%; }
.news-admin-grid { display: block; }
.news-list { width: 100%; }
.news-row-media { width: 90px; height: 58px; display: block; border: 0; border-radius: 7px; background: #eef3f8; object-fit: cover; }
.admin-portal-shell { grid-template-columns: 292px minmax(0, 1fr); }
.admin-sidebar { width: 292px !important; min-width: 292px !important; padding: 20px 14px !important; }
.admin-sidebar__title { padding: 8px 10px 18px; }
.admin-sidebar__brand-logo { width: 40px; height: 40px; }
.admin-sidebar__brand-name { font-size: .7rem; }
.admin-sidebar__brand-subtitle { font-size: .56rem; }
.admin-main { padding: 26px 44px 24px; gap: 16px; }
.news-admin { max-width: none; margin: 0; width: 100%; }
.news-breadcrumb { display: flex; gap: 8px; margin: 0 0 16px; color: #7b8ca5; font-size: .72rem; }
.news-breadcrumb__current { color: #66758d; }
.news-admin h1 { font-size: 2rem; line-height: 1.15; }
.news-list { padding: 20px 22px; border-radius: 10px; }
.add-news { background: #1764d9; color: #fff; border-color: #1764d9; padding: 9px 14px; }
.news-filters { grid-template-columns: minmax(220px, 1fr) repeat(2, minmax(150px, 1fr)) auto; gap: 14px; }
.news-row { grid-template-columns: minmax(0, 1fr) 110px 110px auto; min-height: 66px; padding: 8px 10px; }
.news-row-media { width: 86px; height: 52px; }
.news-row > div:first-of-type { display: flex; align-items: center; gap: 14px; min-width: 0; overflow: hidden; }
.news-row > div:first-of-type > div { min-width: 0; overflow: hidden; }
.news-row strong { display: block; max-width: 100%; font-size: .83rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.news-row-status, .news-row-section { color: #61728d; font-size: .72rem; }
.news-row-status { display: inline-flex; align-items: center; width: fit-content; max-width: 100%; padding: 4px 8px; border-radius: 5px; background: #e4f7ec; color: #198754; font-weight: 700; white-space: nowrap; }
.news-row-status::before { content: ''; display: inline-block; width: 6px; height: 6px; margin-right: 6px; border-radius: 50%; background: currentColor; }
.news-row-status--draft { background: #fff2d5; color: #a96b00; }
.news-row-status--archived { background: #eef1f5; color: #64748b; }
.news-row-section { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.news-actions { display: flex; gap: 6px; justify-content: flex-end; }
.news-table-head { display: grid; grid-template-columns: minmax(0, 1fr) 110px 110px auto; gap: 16px; padding: 10px; border: 1px solid #e2e8f0; border-bottom: 0; border-radius: 8px 8px 0 0; background: #f8fafc; color: #64748b; font-size: .62rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
.news-reset { align-self: end; min-height: 37px; padding: 8px 12px; border: 1px solid #d8e2ef; border-radius: 7px; background: #fff; color: #526783; font: inherit; font-size: .72rem; font-weight: 700; cursor: pointer; }
.news-reset:hover { border-color: #8db5f4; color: #1f5fcf; }
@media(max-width:1100px){.admin-main { padding-left: 28px; padding-right: 28px; }.news-filters { grid-template-columns: minmax(180px, 1fr) repeat(2, minmax(130px, 1fr)); }}
@media(max-width:860px){.admin-portal-shell { grid-template-columns: 1fr; }.admin-sidebar { width: 100%; min-width: 0; }.admin-main { padding: 22px 18px; }}
.admin-main { width: 100% !important; min-width: 0; }
.admin-main > .news-admin { width: 100% !important; max-width: none !important; margin-left: 0 !important; margin-right: 0 !important; box-sizing: border-box; }
.admin-main > .news-admin .news-tabs, .admin-main > .news-admin .news-list { width: 100% !important; box-sizing: border-box; }
</style></head>
<body class="admin-dashboard-ref-body"><div class="admin-portal-shell"><?php include __DIR__ . '/admin-sidebar.php'; ?>
<main class="admin-main"><section class="news-admin"><nav class="news-breadcrumb" aria-label="Breadcrumb"><span>Website Content</span><span aria-hidden="true">/</span><span class="news-breadcrumb__current">News Management</span></nav><h1>News Management</h1><p>Create, edit, reorder, publish, or remove website news items.</p><div class="news-admin-grid"><form class="news-form" id="news-form" enctype="multipart/form-data"><h2 id="form-title">Add News</h2><input type="hidden" name="id" id="news-id"><label for="news-type">Content type</label><select id="news-type" name="type">
  <option value="video">Ini sa SurSur</option>
  <option value="katon">KATON sa SurSur</option>
  <option value="beyond">Beyond the Desk</option>
  <option value="ganap">Ganap sa SurSur</option>
</select><label for="news-title">Title</label><input id="news-title" name="title" required><label for="news-date">Published date</label><input id="news-date" name="published_date" placeholder="July 8, 2026"><label for="news-description">Description</label><textarea id="news-description" name="description"></textarea><label for="media-url">Media URL or path</label><input id="media-url" name="media_url" placeholder="https://... or Logos/example.jpg"><label for="news-image">Upload image</label><input id="news-image" name="image" type="file" accept="image/*"><label for="link-url">Facebook / source link</label><input id="link-url" name="link_url" type="url"><label for="news-order">Display order</label><input id="news-order" name="sort_order" type="number" min="1" value="1"><label><input id="news-active" name="is_active" type="checkbox" checked> Show on public News page</label><div class="form-actions"><button id="save-news" type="submit">Save News</button><button id="clear-news" type="button">Clear</button></div><div class="status" id="form-status" role="status"></div></form><section class="news-list"><h2>News Records</h2><div id="news-records"><div class="status">Loading...</div></div></section></div></section></main></div>
<script>(function(){var api='auth/news_api.php',form=document.getElementById('news-form'),root=document.getElementById('news-records'),status=document.getElementById('form-status'),sectionNames={video:'Ini sa SurSur',katon:'KATON sa SurSur',beyond:'Beyond the Desk',ganap:'Ganap sa SurSur'};function esc(v){return String(v||'').replace(/[&<>"']/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c]})}function load(){fetch(api).then(function(r){return r.json()}).then(function(d){render(d.news||[])}).catch(function(){root.innerHTML='<div class="status">Unable to load news records.</div>'})}function render(rows){if(!rows.length){root.innerHTML='<div class="status">No news records.</div>';return}root.innerHTML=rows.map(function(n){return '<div class="news-row"><img src="'+esc(n.media_url)+'" alt=""><div><strong>'+esc(n.title)+'</strong><small>'+esc(n.published_date||sectionNames[n.type]||n.type)+'</small></div><div><button type="button" data-edit="'+n.id+'">Edit</button><button type="button" class="delete" data-delete="'+n.id+'">Delete</button></div></div>'}).join('')}function clearForm(){form.reset();document.getElementById('news-id').value='';document.getElementById('form-title').textContent='Add News';document.getElementById('news-active').checked=true;status.textContent=''}root.addEventListener('click',function(e){var id=e.target.getAttribute('data-edit')||e.target.getAttribute('data-delete');if(!id)return;if(e.target.hasAttribute('data-delete')){if(!confirm('Delete this news item?'))return;fetch(api,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'delete',id:id})}).then(function(){load()});return}fetch(api).then(function(r){return r.json()}).then(function(d){var n=(d.news||[]).find(function(x){return String(x.id)===String(id)});if(!n)return;['type','title','published_date','description','media_url','link_url','sort_order'].forEach(function(k){document.getElementById(k==='type'?'news-type':'news-'+k.replace('_','-')).value=n[k]||''});document.getElementById('news-id').value=n.id;document.getElementById('news-active').checked=!!n.is_active;document.getElementById('form-title').textContent='Edit News';window.scrollTo({top:0,behavior:'smooth'})})});form.addEventListener('submit',function(e){e.preventDefault();var data=new FormData(form);data.append('action',document.getElementById('news-id').value?'update':'create');fetch(api,{method:'POST',body:data}).then(function(r){return r.json()}).then(function(d){if(d.error)throw new Error(d.error);status.textContent='Saved.';clearForm();load()}).catch(function(err){status.textContent=err.message})});document.getElementById('clear-news').addEventListener('click',clearForm);document.getElementById('logout-btn').addEventListener('click',function(e){e.preventDefault();fetch('../auth/logout.php',{method:'POST'}).finally(function(){location.href='index.html'})});load()}())</script>
<div class="settings-modal"> id="settings-modal" aria-hidden="true">
  <div class="settings-modal__backdrop" data-close-settings></div>
  <div class="settings-modal__panel" role="dialog" aria-modal="true" aria-labelledby="settings-title">
    <div class="settings-modal__header">
      <h2 id="settings-title">System Settings</h2>
      <button type="button" class="settings-modal__close" aria-label="Close settings" data-close-settings>×</button>
    </div>
    <form class="settings-form" id="system-settings-form">
      <label>Website Title<input type="text" name="websiteTitle" value="DILG SDS" /></label>
      <div class="settings-form__row">
        <label>Contact Email<input type="email" name="contactEmail" value="info@dilg.gov.ph" /></label>
        <label>Default Timezone<select name="timezone"><option value="Asia/Manila" selected>Asia/Manila</option><option value="UTC">UTC</option><option value="America/New_York">America/New_York</option></select></label>
      </div>
      <div class="settings-form__row">
        <label>Maintenance Mode<select name="maintenanceMode"><option value="off" selected>Off</option><option value="on">On</option></select></label>
        <label>Default Publishing<select name="publishState"><option value="published" selected>Published</option><option value="draft">Draft</option></select></label>
      </div>
      <div class="settings-modal__actions"><button type="button" class="settings-btn settings-btn--secondary" data-close-settings>Cancel</button><button type="submit" class="settings-btn settings-btn--primary">Save Settings</button></div>
    </form>
    <p class="settings-status" id="settings-status" aria-live="polite"></p>
  </div>
</div>
<script>
const settingsModal = document.getElementById('settings-modal');
const settingsForm = document.getElementById('system-settings-form');
const settingsStatus = document.getElementById('settings-status');
function openSettingsModal() { if (!settingsModal) return; settingsModal.classList.add('is-open'); settingsModal.setAttribute('aria-hidden', 'false'); if (settingsStatus) settingsStatus.textContent = ''; }
function closeSettingsModal() { if (!settingsModal) return; settingsModal.classList.remove('is-open'); settingsModal.setAttribute('aria-hidden', 'true'); }
document.querySelectorAll('[data-open-settings]').forEach(function(button){button.addEventListener('click', openSettingsModal);});
document.querySelectorAll('[data-close-settings]').forEach(function(button){button.addEventListener('click', closeSettingsModal);});
if (settingsModal) { settingsModal.addEventListener('click', function(event) { if (event.target === settingsModal || event.target.classList.contains('settings-modal__backdrop')) { closeSettingsModal(); } }); }
document.addEventListener('keydown', function(event) { if (event.key === 'Escape' && settingsModal && settingsModal.classList.contains('is-open')) { closeSettingsModal(); } });
if (settingsForm) {
  const savedSettings = JSON.parse(localStorage.getItem('dilgSystemSettings') || '{}');
  if (savedSettings.websiteTitle) settingsForm.websiteTitle.value = savedSettings.websiteTitle;
  if (savedSettings.contactEmail) settingsForm.contactEmail.value = savedSettings.contactEmail;
  if (savedSettings.timezone) settingsForm.timezone.value = savedSettings.timezone;
  if (savedSettings.maintenanceMode) settingsForm.maintenanceMode.value = savedSettings.maintenanceMode;
  if (savedSettings.publishState) settingsForm.publishState.value = savedSettings.publishState;
  settingsForm.addEventListener('submit', function(event) { event.preventDefault(); const formData = new FormData(settingsForm); const values = Object.fromEntries(formData.entries()); localStorage.setItem('dilgSystemSettings', JSON.stringify(values)); if (settingsStatus) settingsStatus.textContent = 'Settings saved successfully.'; closeSettingsModal(); });
}
</script>
<script>
(function () {
  var form = document.getElementById('news-form');
  var list = document.getElementById('news-records');
  var formTitle = document.getElementById('form-title');
  if (!form || !list) return;
  form.classList.add('news-editor-form');
  var closeButton = document.createElement('button');
  closeButton.type = 'button'; closeButton.className = 'news-close'; closeButton.textContent = '×'; closeButton.setAttribute('aria-label', 'Close news editor');
  formTitle.parentNode.insertBefore(closeButton, formTitle.nextSibling);
  var editor = form.closest('.news-form') || form;
  editor.classList.add('news-editor');
  function closeEditor() { editor.classList.remove('is-open'); editor.setAttribute('aria-hidden', 'true'); }
  function openEditor() { editor.classList.add('is-open'); editor.setAttribute('aria-hidden', 'false'); }
  closeButton.addEventListener('click', closeEditor);
  document.getElementById('clear-news').addEventListener('click', closeEditor);
  list.addEventListener('click', function (event) { if (event.target.closest('[data-edit]')) openEditor(); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeEditor(); });
  var toolbar = document.createElement('div');
  var tabs = document.createElement('nav');
  tabs.className = 'news-tabs';
  tabs.setAttribute('aria-label', 'News categories');
  tabs.innerHTML = '<button type="button" class="news-tab is-active" data-news-tab="video">Ini sa SurSur</button><button type="button" class="news-tab" data-news-tab="katon">KATON sa SurSur</button><button type="button" class="news-tab" data-news-tab="beyond">Beyond the Desk</button><button type="button" class="news-tab" data-news-tab="ganap">Ganap sa SurSur</button>';
  var mainSection = editor.parentNode.parentNode;
  if (mainSection) mainSection.insertBefore(tabs, mainSection.querySelector('.news-admin-grid'));
  toolbar.className = 'news-list-header';
  toolbar.innerHTML = '<h2>News Records</h2><button type="button" class="add-news" id="add-news">+ Add News</button>';
  var filters = document.createElement('div');
  filters.className = 'news-filters';
  filters.innerHTML = '<label><span>Search news</span><input id="news-search" type="search" placeholder="Search content..."></label><label>Status<select id="news-status-filter"><option value="all">All Status</option><option value="published">Published</option><option value="draft">Draft</option></select></label><label>Section<select id="news-section-filter"><option value="all">All Sections</option><option value="video">Ini sa SurSur</option><option value="katon">KATON sa SurSur</option><option value="beyond">Beyond the Desk</option><option value="ganap">Ganap sa SurSur</option></select></label>';
  var oldHeading = editor.parentNode.querySelector('.news-list > h2');
  if (oldHeading) oldHeading.remove();
  var newsList = editor.parentNode.querySelector('.news-list');
  if (newsList) { newsList.insertBefore(toolbar, newsList.firstChild); newsList.insertBefore(filters, newsList.children[1]); var hint = document.createElement('div'); hint.className = 'news-hint'; hint.textContent = 'ⓘ Drag and drop the rows to reorder news items.'; newsList.insertBefore(hint, list); }
  var search = document.getElementById('news-search'), statusFilter = document.getElementById('news-status-filter'), sectionFilter = document.getElementById('news-section-filter');
  sectionFilter.value = 'video';
  var statusById = {}, sectionById = {};
  function syncStatus() { fetch('../auth/news_api.php').then(function (response) { return response.json(); }).then(function (data) { (data.news || []).forEach(function (item) { statusById[String(item.id)] = item.is_active ? 'published' : 'draft'; sectionById[String(item.id)] = item.type || ''; }); filterRows(); }); }
  function filterRows() { var term = search.value.toLowerCase().trim(), statusValue = statusFilter.value, sectionValue = sectionFilter.value; list.querySelectorAll('.news-row').forEach(function (row) { var edit = row.querySelector('[data-edit]'), id = edit ? edit.getAttribute('data-edit') : ''; var text = row.textContent.toLowerCase(); var visible = (!term || text.indexOf(term) !== -1) && (sectionValue === 'all' || sectionById[id] === sectionValue) && (statusValue === 'all' || statusById[id] === statusValue); row.style.display = visible ? '' : 'none'; }); }
  search.addEventListener('input', filterRows); statusFilter.addEventListener('change', filterRows); sectionFilter.addEventListener('change', filterRows);
  tabs.querySelectorAll('[data-news-tab]').forEach(function (tab) { tab.addEventListener('click', function () { tabs.querySelectorAll('.news-tab').forEach(function (item) { item.classList.remove('is-active'); }); tab.classList.add('is-active'); sectionFilter.value = tab.getAttribute('data-news-tab'); filterRows(); }); });
  var initialAdd = document.getElementById('add-news');
  if (initialAdd) initialAdd.addEventListener('click', function () { openEditor(); });
  new MutationObserver(syncStatus).observe(list, { childList: true });
  syncStatus();
}());
</script>
<script>
(function () {
  function repairMedia(image) {
    var source = image.getAttribute('src') || '';
    if (source.indexOf('facebook.com/plugins/video.php') === -1) return;
    var frame = document.createElement('iframe');
    frame.className = 'news-row-media';
    frame.src = source;
    frame.title = 'News video';
    frame.loading = 'lazy';
    image.replaceWith(frame);
  }
  function repairRows() {
    document.querySelectorAll('#news-records .news-row img').forEach(function (image) {
      if (image.complete && image.naturalWidth === 0) repairMedia(image);
      image.addEventListener('error', function () { repairMedia(image); }, { once: true });
    });
  }
  new MutationObserver(repairRows).observe(document.getElementById('news-records'), { childList: true, subtree: true });
  repairRows();
}());
</script>
<script>
(function () {
  var records = document.getElementById('news-records');
  if (!records) return;
  var sectionNames = { video: 'Ini sa SurSur', katon: 'KATON sa SurSur', beyond: 'Beyond the Desk', ganap: 'Ganap sa SurSur' };
  var list = records.closest('.news-list');
  if (list) {
    var tableHead = document.createElement('div');
    tableHead.className = 'news-table-head';
    tableHead.innerHTML = '<span>News Item</span><span>Status</span><span>Section</span><span>Actions</span>';
    list.insertBefore(tableHead, records);
    var filters = list.querySelector('.news-filters');
    if (filters && !filters.querySelector('.news-reset')) {
      var reset = document.createElement('button');
      reset.type = 'button';
      reset.className = 'news-reset';
      reset.textContent = 'Reset Filters';
      reset.addEventListener('click', function () {
        filters.querySelectorAll('input').forEach(function (input) { input.value = ''; });
        filters.querySelectorAll('select').forEach(function (select) { select.selectedIndex = 0; });
        filters.querySelector('input')?.dispatchEvent(new Event('input'));
      });
      filters.appendChild(reset);
    }
  }
  function formatRows(items) {
    var byId = {};
    items.forEach(function (item) { byId[String(item.id)] = item; });
    records.querySelectorAll('.news-row').forEach(function (row) {
      if (row.dataset.referenceLayout === 'true') return;
      var action = row.querySelector('[data-edit]');
      var item = action ? byId[action.getAttribute('data-edit')] : null;
      var image = row.children[0];
      var info = row.children[1];
      var actions = row.children[2];
      if (!image || !info || !actions) return;
      var main = document.createElement('div');
      main.className = 'news-row-main';
      main.appendChild(image);
      main.appendChild(info);
      var state = document.createElement('div');
      state.className = 'news-row-status' + (item && !item.is_active ? ' news-row-status--draft' : '');
      state.textContent = item && !item.is_active ? 'Draft' : 'Published';
      var section = document.createElement('div');
      section.className = 'news-row-section';
      section.textContent = item ? (sectionNames[item.type] || item.type || '') : '';
      actions.className = 'news-actions';
      row.appendChild(main);
      row.appendChild(state);
      row.appendChild(section);
      row.appendChild(actions);
      row.dataset.referenceLayout = 'true';
    });
  }
  fetch('../auth/news_api.php').then(function (response) { return response.json(); }).then(function (data) {
    formatRows(data.news || []);
    new MutationObserver(function () { formatRows(data.news || []); }).observe(records, { childList: true });
  });
}());
</script>
</body></html>

