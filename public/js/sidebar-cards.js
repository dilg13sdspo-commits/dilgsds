(function () {
  var abbreviations = {
    'Office of the President': 'OP',
    "Secretary's Corner": 'SC',
    'Philippine Transparency Seal': 'TS',
    'GAA Reportorial Requirement': 'GAA',
    'Freedom of Information': 'FOI',
    'Localization of EO 70': 'EO 70',
    'Data Privacy': 'DPA',
    'Gender and Development': 'GAD'
  };

  document.querySelectorAll('.cn-sidebar a.cn-portal-card, .cn-sidebar a.cn-sec-card').forEach(function (card) {
    var image = card.querySelector(':scope > img');
    if (!image) return;

    var label = image.alt.replace(/^Visit the\s+/i, '').trim();
    if (!label) return;
    var mark = document.createElement('span');
    mark.className = 'cn-card-mark';
    mark.setAttribute('aria-hidden', 'true');
    mark.textContent = abbreviations[label] || label.slice(0, 4).toUpperCase();

    card.classList.add('cn-link-card');
    card.replaceChildren(mark);
    if (card.classList.contains('cn-portal-card')) {
      var copy = document.createElement('span');
      copy.className = 'cn-card-copy';
      var title = document.createElement('strong');
      title.textContent = label;
      var description = document.createElement('small');
      description.textContent = label === 'Office of the President' ? 'Visit the official website' : 'Visit the official page';
      copy.append(title, description);
      card.appendChild(copy);
    } else {
      var title = document.createElement('strong');
      title.textContent = label;
      card.appendChild(title);
    }

    var arrow = document.createElement('span');
    arrow.className = 'cn-card-arrow';
    arrow.setAttribute('aria-hidden', 'true');
    arrow.textContent = '↗';
    card.appendChild(arrow);
  });
})();
