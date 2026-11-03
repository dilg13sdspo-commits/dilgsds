/**
 * Set active navigation item based on current page URL.
 * Ensures the correct navbar link/button shows the blue active state
 * when navigating between separate HTML pages.
 */
function setActiveNavByUrl() {
  const path = window.location.pathname.split('/').pop() || 'index.html';
  var navLinks = document.querySelectorAll('.topnav-center .nav-link, .topnav-center .nav-item');

  // Mapping of page filenames to data-page values used in the navbar
  const pageMap = {
    'index.html': 'home',
    'organizational-structure.html': 'about',
    'elected-officials.html': 'about',
    'mission-vision-values.html': 'about',
    'quality-policy.html': 'about',
    'citizen-charter.html': 'citizens-charter',
    'news.html': 'news',
    'joint-circulars.html': 'issuances',
    'memo-circulars.html': 'issuances',
    'presidential-directives.html': 'issuances',
    'republic-acts.html': 'issuances',
    'legal-opinions.html': 'legal-opinion',
    'legal-opinions-dilg-philippines.html': 'legal-opinion',
    'programs-and-services.html': 'programs',
    'contact-us.html': 'contact',
    'Login.html': 'login',
  };

  var targetPage = pageMap[path];

  // First, remove active from ALL nav items unconditionally
  navLinks.forEach(function (link) {
    link.classList.remove('active');
    var parent = link.closest('.nav-item');
    if (parent) parent.classList.remove('active');
  });

  // If we're on index.html with SPA pages, let showPage() handle active state
  var hasSpaPages = document.querySelector('.page.active');
  if (hasSpaPages && path === 'index.html') return;

  if (!targetPage) return;

  // Find the matching nav link by data-page first, then fallback to href match
  var matched = false;
  navLinks.forEach(function (link) {
    var linkPage = link.dataset.page;
    var href = link.getAttribute('href');

    if (linkPage === targetPage) {
      link.classList.add('active');
      var parent = link.closest('.nav-item');
      if (parent) parent.classList.add('active');
      matched = true;
    } else if (href && href.includes(path)) {
      link.classList.add('active');
      var parent = link.closest('.nav-item');
      if (parent) parent.classList.add('active');
      matched = true;
    }
  });

  // Final fallback: iterate again for edge cases
  if (!matched) {
    navLinks.forEach(function (link) {
      var href = link.getAttribute('href');
      if (href && href.includes(path)) {
        link.classList.add('active');
        var parent = link.closest('.nav-item');
        if (parent) parent.classList.add('active');
      }
    });
  }
}

window.PROVINCIAL_NEWS_DATA = [
  {
    title: 'Provincial Rural Improvement Club (PRIC) 2nd Quarter Meeting',
    date: 'June 30, 2026',
    image: 'Logos/PRIC 2ND QUARTER MEETING (1).png',
    imageLink: 'Logos/PRIC 2ND QUARTER MEETING (1).png',
    url: 'https://surigaodelsur.gov.ph/news-single/550',
    paragraphs: [
      'To further strengthen the implementation of programs for Rural Improvement Clubs (RICs) across Surigao del Sur, the Office of the Provincial Agriculturist (OPAg) convened the Provincial Rural Improvement Club (PRIC) 2nd Quarter Meeting on June 30, 2026, at the OPAg Training Hall, Paradise Heights, Telaje, Tandag City.',
      'The activity brought together City and Municipal RIC Coordinators, Provincial RIC Officers, and OPAg personnel to discuss updates, address concerns, and plan activities that will benefit RIC members and their communities.'
    ]
  },
  {
    title: 'Carrying Capacity Development Seminar Workshop',
    date: 'June 30, 2026',
    image: 'Logos/1783319661_6a4b4c6d3aaa7.png',
    imageLink: 'Logos/1783319661_6a4b4c6d3aaa7.png',
    url: 'https://surigaodelsur.gov.ph/news-single/546',
    paragraphs: [
      'TANDAG CITY, Surigao del Sur - The Department of Tourism (DOT)-Caraga, in partnership with the Provincial Tourism Office of Surigao del Sur, conducted a two-day Carrying Capacity Development Seminar-Workshop on June 30 to July 1, 2026, at Uptown Corporate Hotel, Tandag City.',
      'The activity brought together Municipal Tourism Officers and representatives from the Municipal and Provincial Environment and Natural Resources Offices from different local government units in the province.'
    ]
  },
  {
    title: 'Provincial GFPS Strengthens Gender Mainstreaming in 2nd Quarter Meeting',
    date: 'June 25, 2026',
    url: 'https://surigaodelsur.gov.ph/news-single/541',
    paragraphs: [
      'SURIGAO DEL SUR – Strengthening gender-responsive governance requires more than compliance—it demands commitment, collaboration, and a shared understanding that Gender and Development (GAD) is everyone\'s responsibility. This was the central message during the 2nd Quarter Gender and Development (GAD) Focal Point System (GFPS) Meeting held on June 25, 2026, at the Provincial Government Office (PGO) Conference Room.',
      'The quarterly gathering brought together GAD Focal Persons, department representatives, and provincial officials to discuss organizational updates, GAD planning and budgeting, accomplishment reports, and strategies to further institutionalize gender mainstreaming across the Provincial Government of Surigao del Sur.',
      'Opening the meeting, Acting Governor Hon. Manuel D. Alameda Sr. delivered an inspiring welcome message, sharing his personal journey toward appreciating the importance of Gender and Development. Hon. Alameda underscored the need to eliminate discrimination and cultivate a culture where everyone is treated with dignity and respect. He emphasized that opportunities should never be limited by gender, citing an occurrence he witnessed years ago where four women successfully entered CAFGU training—an achievement that proves women are equally capable of excelling in fields traditionally dominated by men.'
    ]
  },
  {
    title: 'Project Launching and Ceremonial Planting on Research and Development and Seed System',
    date: 'April 22, 2026',
    image: 'Logos/1784624492_6a5f356cd4a32.png',
    imageLink: 'Logos/1784624492_6a5f356cd4a32.png',
    url: 'hhttps://surigaodelsur.gov.ph/news-single/535',
    paragraphs: [
      'The Provincial Government of Surigao del Sur, through the Office of the Provincial Agriculturist (OPAg), successfully conducted the Project Launching and Ceremonial Planting on Research and Development and Seed System on April 22–23, 2026, reinforcing its commitment to sustainable agriculture and environmental protection.',
      'The two-day activity gathered personnel from OPAg and representatives from the Department of Agriculture – Caraga, highlighting a strengthened collaboration between provincial and regional stakeholders in advancing agricultural initiatives.'
    ]
  },
  {
    title: '2nd Women\'s Summit 2026',
    date: 'May 28, 2026',
    image: 'Logos/c5769623-dbe4-4a7f-9ac1-02dc7e0a4e56 (1).jfif',
    imageLink: 'Logos/c5769623-dbe4-4a7f-9ac1-02dc7e0a4e56 (1).jfif',
    url: 'https://surigaodelsur.gov.ph/news-single/538',
    paragraphs: [
      'A powerful gathering, a shared purpose, and a celebration of women’s leadership—these defined the success of the 2nd Women’s Summit 2026 held on March 18 at the Bislig City Convention Hall. Warmest congratulations to SP Member Margarita G. Garay, Chairperson of the Committee on Women, Children, and Family, whose leadership and dedication brought this meaningful event to life. With the inspiring theme, “Lead like the Babaylans, Surigaonons!”, the summit honored not only the strength of women today but also the legacy of empowered female leaders rooted in culture and history. The presence of Mayor Roxanne Pimentel added both heart and energy to the occasion. ',
      ' Her genuine compassion for women’s struggles and triumphs was evident—not only in her words, but in her actions, as she joyfully connected with participants on stage, even dancing to “Hawak Ko ang Beat” and sharing spontaneous moments of generosity through prizes. It was a reminder that leadership can be both empathetic and uplifting.'
    ]
  }
];

document.addEventListener('DOMContentLoaded', () => {
  // Set active nav item based on current URL
  setActiveNavByUrl();
  const timeEl = document.getElementById('topbar-time')
    || document.querySelector('.topbar-clock-time')
    || document.querySelector('.topbar-time')
    || document.querySelector('.clock .time');
  const dateEl = document.getElementById('topbar-date')
    || document.querySelector('.topbar-clock-date')
    || document.querySelector('.date-text');
  let slides = Array.from(document.querySelectorAll('.slide'));
  const dots = Array.from(document.querySelectorAll('.dot'));
  const prevButton = document.querySelector('.slider-control.prev');
  const nextButton = document.querySelector('.slider-control.next');
  const searchForm = document.querySelector('.search');
  const searchInput = searchForm?.querySelector('input[name="q"]');
  let currentSlide = 0;
  let slideTimer = null;

  const locale = navigator.language || 'en-PH';

  const getQueryParam = (name) => new URLSearchParams(window.location.search).get(name);
  const updateLegalOpinionRegion = () => {
    const region = getQueryParam('region');
    if (!region) return;
    const filterLabel = document.querySelector('.legal-opinions-region-label');
    if (filterLabel) {
      filterLabel.textContent = `Region: ${decodeURIComponent(region)}`;
    }
  };

  const updateClock = () => {
    try {
      const now = new Date();
      const philippineTime = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
      const weekday = philippineTime.toLocaleDateString(locale, {
        weekday: 'long',
        timeZone: 'Asia/Manila'
      });
      const month = philippineTime.toLocaleDateString(locale, {
        month: 'long',
        timeZone: 'Asia/Manila'
      });
      const day = philippineTime.toLocaleDateString(locale, {
        day: 'numeric',
        timeZone: 'Asia/Manila'
      });
      const year = philippineTime.toLocaleDateString(locale, {
        year: 'numeric',
        timeZone: 'Asia/Manila'
      });
      const timeString = philippineTime.toLocaleTimeString(locale, {
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila'
      });

      const dateString = `${weekday}, ${month} ${day}, ${year}`;
      const timeDisplay = `${timeString} Tandag City`;

      if (dateEl) {
        dateEl.textContent = dateString;
      }
      if (timeEl) {
        timeEl.textContent = timeDisplay;
      }
    } catch (error) {
      console.error('Clock update failed:', error);
      if (dateEl) {
        dateEl.textContent = 'Date unavailable';
      }
      if (timeEl) {
        timeEl.textContent = 'Time unavailable';
      }
    }
  };

  const showSlide = (index) => {
    slides.forEach((slide, idx) => {
      slide.classList.toggle('active', idx === index);
    });
    dots.forEach((dot, idx) => {
      dot.classList.toggle('active', idx === index);
    });
    currentSlide = index;
  };

  const nextSlide = () => {
    const nextIndex = (currentSlide + 1) % slides.length;
    showSlide(nextIndex);
  };

  const prevSlide = () => {
    const prevIndex = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(prevIndex);
  };

  const resetTimer = () => {
    if (slideTimer) clearInterval(slideTimer);
    slideTimer = setInterval(nextSlide, 5000);
  };

  if (slides.length > 0 && (dots.length === 0 || dots.length === slides.length)) {
    showSlide(0);
    slideTimer = setInterval(nextSlide, 5000);

    dots.forEach((dot) => {
      dot.addEventListener('click', () => {
        showSlide(Number(dot.dataset.slide));
        resetTimer();
      });
    });
  }

  // Use the records maintained by Home Management when the homepage is served through PHP.
  const sliderEl = document.querySelector('.slider');
  if (sliderEl && slides.length > 0) {
    const appRoot = new URL('../../', window.location.href);
    fetch('../../auth/home_api.php?group=slides', { cache: 'no-store' })
      .then((response) => {
        if (!response.ok) throw new Error('Unable to load managed slides');
        return response.json();
      })
      .then((content) => {
        const managedSlides = Array.isArray(content?.slides) ? content.slides : [];
        if (!managedSlides.length) return;

        const controls = sliderEl.querySelectorAll('.slider-control');
        sliderEl.querySelectorAll('.slide').forEach((slide) => slide.remove());
        managedSlides.forEach((item, index) => {
          const slide = document.createElement('div');
          slide.className = `slide${index === 0 ? ' active' : ''}`;
          const image = document.createElement('img');
          image.src = new URL(item.image_url, appRoot).href;
          image.alt = item.title || `Homepage image ${index + 1}`;
          image.style.objectFit = 'cover';
          image.style.objectPosition = `${Number(item.image_position_x ?? 50)}% ${Number(item.image_position_y ?? 50)}%`;
          image.style.transform = `scale(1.12) translate(${(Number(item.image_position_x ?? 50) - 50) * -0.16}%, ${(Number(item.image_position_y ?? 50) - 50) * -0.16}%)`;
          slide.appendChild(image);
          sliderEl.insertBefore(slide, controls[0] || null);
        });

        slides = Array.from(sliderEl.querySelectorAll('.slide'));
        if (slideTimer) clearInterval(slideTimer);
        showSlide(0);
        slideTimer = setInterval(nextSlide, 5000);
      })
      .catch(() => {
        // Keep the built-in slides when the API is unavailable or the page is opened as a file.
      });
  }

  // Keep the public homepage's managed content in sync with Home Management.
  fetch('../../auth/home_api.php', { cache: 'no-store' })
    .then((response) => response.ok ? response.json() : null)
    .then((content) => {
      if (!content) return;
      const first = (group) => Array.isArray(content[group]) ? content[group][0] : null;
      const updateText = (selector, value) => { const element = document.querySelector(selector); if (element && value !== undefined) element.textContent = value || ''; };
      const updateLink = (selector, value) => { const element = document.querySelector(selector); if (element && value) element.setAttribute('href', value); };
      const setParagraphCopy = (selector, textOrParagraphs) => {
        const element = document.querySelector(selector);
        if (!element) return;
        const paragraphs = Array.isArray(textOrParagraphs)
          ? textOrParagraphs.filter((item) => typeof item === 'string' && item.trim())
          : (typeof textOrParagraphs === 'string' && textOrParagraphs.trim() ? textOrParagraphs.split(/\r?\n|<br\s*\/?>/i).map((p) => p.trim()).filter(Boolean) : []);
        element.querySelectorAll('p').forEach((paragraph) => paragraph.remove());
        if (!paragraphs.length) return;
        paragraphs.forEach((text) => {
          const p = document.createElement('p');
          p.textContent = text;
          element.appendChild(p);
        });
      };
      const isImageLike = (value) => {
        if (!value) return false;
        const trimmed = String(value).trim();
        if (!trimmed) return false;
        if (trimmed.startsWith('data:image/')) return true;
        if (trimmed.startsWith('Logos/') || trimmed.startsWith('uploads/') || trimmed.startsWith('./') || trimmed.startsWith('/')) return true;
        return /\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i.test(trimmed) || /\/image\//i.test(trimmed);
      };
      const updateImage = (selector, value, alt) => { const element = document.querySelector(selector); if (element && isImageLike(value)) { element.setAttribute('src', value); element.setAttribute('alt', alt || ''); } };

      const ticker = first('ticker');
      if (ticker) {
        const tickerLink = document.querySelector('#ticker a');
        if (tickerLink) { tickerLink.textContent = ticker.title || ''; tickerLink.setAttribute('href', ticker.link_url || '#'); }
      }

      const managedCentral = Array.isArray(content.central) ? content.central : [];
      if (managedCentral.length && typeof centralNewsItems === 'undefined') {
        const central = managedCentral[0];
        if (central) {
          updateText('.cn-news-title', central.title);
          updateText('.cn-news-date', central.date);
          updateText('.cn-news-copy', central.description);
          updateLink('.cn-read-more', central.link_url);
          updateImage('.cn-feature-media img', central.image_url, central.title);
        }
      }

      const managedProvincial = Array.isArray(content.provincial) ? content.provincial : [];
      if (managedProvincial.length && typeof provincialNewsItems === 'undefined') {
        const provincial = managedProvincial[0];
        if (provincial) {
          updateText('.rn-news-title', provincial.title);
          updateText('.rn-news-date', provincial.date);
          setParagraphCopy('.rn-news-copy', provincial.description || provincial.paragraphs || provincial.text || []);
          updateLink('.rn-read-more', provincial.link_url);
          updateImage('.rn-feature-media img', provincial.image_url, provincial.title);
        }
      }
    })
    .catch(() => {
      // Keep the built-in homepage content when the managed API is unavailable.
    });

  // Pause carousel on hover/focus and resume on leave
  const carouselEl = document.querySelector('.carousel');
  if (carouselEl) {
    carouselEl.addEventListener('mouseenter', () => { if (slideTimer) clearInterval(slideTimer); });
    carouselEl.addEventListener('mouseleave', () => { resetTimer(); });
    carouselEl.addEventListener('focusin', () => { if (slideTimer) clearInterval(slideTimer); });
    carouselEl.addEventListener('focusout', () => { resetTimer(); });
  }

  if (prevButton) {
    prevButton.addEventListener('click', () => {
      prevSlide();
      resetTimer();
    });
  }

  if (nextButton) {
    nextButton.addEventListener('click', () => {
      nextSlide();
      resetTimer();
    });
  }

  // CENTRAL NEWS carousel
  const cnTitleEl = document.querySelector('.cn-news-title');
  const cnDateEl = document.querySelector('.cn-news-date');
  const cnCopyEl = document.querySelector('.cn-news-copy');
  const cnReadMoreEl = document.querySelector('.cn-read-more');
  const cnFrameEl = document.querySelector('.cn-news-frame');
  const cnDots = Array.from(document.querySelectorAll('.cn-dot'));
  const cnHeadlineListEl = document.querySelector('.cn-headline-list');
  let cnCurrentIndex = 0;
  let cnTimer = null;

  if (cnTitleEl && cnDateEl && cnCopyEl && cnReadMoreEl && cnDots.length > 0) {
    const centralNewsItems = [
      {
        title: "DILG: LGUs' enforcement of Safe Spaces Act key to protecting victims of gender-based sexual harassment",
        date: 'July 20, 2026',
        paragraphs: [
          'The Department of the Interior and Local Government emphasized that strict enforcement of the Safe Spaces Act at the local level is essential to protect victims and strengthen accountability mechanisms in communities.',
          'Local government units are urged to sustain prevention, response, and reporting systems to ensure safer public spaces for everyone.'
        ],
        url: 'https://www.dilg.gov.ph/news/DILG-LGUs-enforcement-of-Safe-Spaces-Act-key-to-protecting-victims-of-gender-based-sexual-harassment/NC-2026-1200'
      },
      {
        title: 'DILG, PCW: Safer Cities initiative reinforces Safe Spaces Act implementation in LGUs',
        date: 'July 16, 2026',
        paragraphs: [
          'DILG and the Philippine Commission on Women highlighted the gains of the Safer Cities initiative in helping local governments operationalize Safe Spaces Act mandates.',
          'The program advances local coordination, reporting pathways, and community-based prevention against gender-based harassment.'
        ],
        url: 'https://www.dilg.gov.ph/news/DILG-PCW-Safer-Cities-initiative-reinforces-Safe-Spaces-Act-implementation-in-LGUs/NC-2026-1199'
      },
      {
        title: "Focus Crimes Drop 20.6% under DILG's Safer Cities Initiative",
        date: 'July 16, 2026',
        paragraphs: [
          'Reported focus crimes declined under the Safer Cities initiative, reflecting stronger local public safety coordination and targeted interventions across participating areas.',
          'DILG underscored continued implementation and monitoring to sustain these gains and improve community security outcomes.'
        ],
        url: 'https://www.dilg.gov.ph/news/Focus-Crimes-Drop-206-under-DILGs-Safer-Cities-Initiative/NC-2026-1198'
      },
      {
        title: "DILG Statement on the BJMP's Custody of Senator Marcoleta",
        date: 'July 15, 2026',
        paragraphs: [
          'The Department reaffirmed that the Bureau of Jail Management and Penology follows established legal procedures and standards in carrying out custodial duties.',
          'DILG emphasized impartiality, professionalism, and equal treatment for all persons deprived of liberty.'
        ],
        url: 'https://www.dilg.gov.ph/news/DILG-Statement-on-the-BJMPs-Custody-of-Senator-Marcoleta/NC-2026-1197'
      },
      {
        title: 'DILG to LGUs: Adopt Revised Child Protection Protocol, Strengthen Response to Abuse Cases',
        date: 'July 14, 2026',
        paragraphs: [
          'DILG directed local government units to adopt the revised child protection protocol to improve case handling and strengthen coordinated response mechanisms.',
          'The directive supports faster intervention, better reporting pathways, and stronger protection outcomes for children at risk.'
        ],
        url: 'https://www.dilg.gov.ph/news/DILG-to-LGUs-Adopt-Revised-Child-Protection-Protocol-Strengthen-Response-to-Abuse-Cases/NC-2026-1196'
      }
    ];

    const cnListItems = [];

    if (cnHeadlineListEl) {
      cnHeadlineListEl.innerHTML = '';

      centralNewsItems.forEach((item, index) => {
        const listItem = document.createElement('li');
        listItem.className = 'cn-headline-item';

        const link = document.createElement('a');
        link.className = 'cn-headline-link';
        link.href = item.url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';

        const text = document.createElement('span');
        text.className = 'headline-text';
        text.textContent = item.title;
        link.appendChild(text);

        link.addEventListener('click', () => {
          cnRender(index);
          cnResetTimer();
        });

        const date = document.createElement('span');
        date.className = 'cn-headline-item-date';
        date.textContent = item.date;

        listItem.appendChild(link);
        listItem.appendChild(date);
        cnHeadlineListEl.appendChild(listItem);
        cnListItems.push(listItem);
      });
    }

    // support for optional central feature media (image) and flexible paragraph sources
    const cnFeatureMediaEl = document.querySelector('.cn-feature-media');
    const cnFeatureImgEl = cnFeatureMediaEl?.querySelector('img');
    const cnFeatureLinkEl = cnFeatureMediaEl?.querySelector('a');

    const cnRender = (index) => {
      const item = centralNewsItems[index];
      if (!item) return;

      cnTitleEl.textContent = item.title || '';
      cnDateEl.textContent = item.date || '';
      cnReadMoreEl.setAttribute('href', item.url || '#');

      // paragraphs: prefer an explicit array, fallback to description string split by newlines
      cnCopyEl.innerHTML = '';
      const paragraphs = Array.isArray(item.paragraphs) ? item.paragraphs : (typeof item.description === 'string' && item.description.trim() ? item.description.split(/\r?\n/).filter(Boolean) : []);
      paragraphs.forEach((text) => {
        const p = document.createElement('p');
        p.textContent = text;
        cnCopyEl.appendChild(p);
      });

      // central feature image (optional) - accept multiple possible fields
      const imageSrc = item.image || item.image_url || item.media_url || '';
      if (cnFeatureMediaEl && cnFeatureImgEl) {
        if (imageSrc) {
          cnFeatureImgEl.setAttribute('src', imageSrc);
          cnFeatureImgEl.setAttribute('alt', item.title || 'Central news image');
          if (cnFeatureLinkEl) cnFeatureLinkEl.setAttribute('href', item.imageLink || item.image_link || item.link_url || item.url || '#');
          cnFeatureMediaEl.style.display = '';
        } else {
          cnFeatureMediaEl.style.display = 'none';
        }
      }

      cnDots.forEach((dot, dotIndex) => {
        dot.classList.toggle('active', dotIndex === index);
      });

      cnListItems.forEach((entry, entryIndex) => {
        entry.classList.toggle('active', entryIndex === index);
      });

      if (cnFrameEl) {
        cnFrameEl.classList.remove('cn-frame-animate');
        void cnFrameEl.offsetWidth;
        cnFrameEl.classList.add('cn-frame-animate');
      }

      cnCurrentIndex = index;
    };

    const cnNext = () => {
      const nextIndex = (cnCurrentIndex + 1) % centralNewsItems.length;
      cnRender(nextIndex);
    };

    const cnResetTimer = () => {
      if (cnTimer) clearInterval(cnTimer);
      cnTimer = setInterval(cnNext, 3500);
    };

    cnDots.forEach((dot, index) => {
      dot.dataset.index = String(index);
      dot.setAttribute('role', 'button');
      dot.setAttribute('tabindex', '0');
      dot.setAttribute('aria-label', `Show CENTRAL NEWS item ${index + 1}`);

      dot.addEventListener('click', () => {
        cnRender(index % centralNewsItems.length);
        cnResetTimer();
      });

      dot.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          cnRender(index % centralNewsItems.length);
          cnResetTimer();
        }
      });
    });

    cnRender(0);
    cnResetTimer();

    // Sync: update CENTRAL NEWS when PROVINCIAL emits change
    document.addEventListener('provincial:change', (e) => {
      try {
        const idx = (e && e.detail && typeof e.detail.index === 'number') ? e.detail.index : 0;
        if (Array.isArray(centralNewsItems) && centralNewsItems.length > 0) {
          cnRender(idx % centralNewsItems.length);
          cnResetTimer();
        }
      } catch (err) {
        // ignore
      }
    });
  }

  // PROVINCIAL NEWS data-driven section
  const provincialNewsItems = Array.isArray(window.PROVINCIAL_NEWS_DATA) ? window.PROVINCIAL_NEWS_DATA : [
    {
      title: 'Provincial Rural Improvement Club (PRIC) 2nd Quarter Meeting',
      date: 'June 30, 2026',
      image: 'Logos/PRIC 2ND QUARTER MEETING (1).png',
      url: 'https://surigaodelsur.gov.ph/news-single/550',
      paragraphs: [
        'To further strengthen the implementation of programs for Rural Improvement Clubs (RICs) across Surigao del Sur, the Office of the Provincial Agriculturist (OPAg) convened the Provincial Rural Improvement Club (PRIC) 2nd Quarter Meeting on June 30, 2026, at the OPAg Training Hall, Paradise Heights, Telaje, Tandag City.',
        'The activity brought together City and Municipal RIC Coordinators, Provincial RIC Officers, and OPAg personnel to discuss updates, address concerns, and plan activities that will benefit RIC members and their communities.'
      ]
    },
    {
      title: 'Carrying Capacity Development Seminar Workshop',
      date: 'June 30, 2026',
      image: 'Logos/1783319661_6a4b4c6d3aaa7.png',
      url: 'https://surigaodelsur.gov.ph/news-single/546',
      paragraphs: [
        'TANDAG CITY, Surigao del Sur - The Department of Tourism (DOT)-Caraga, in partnership with the Provincial Tourism Office of Surigao del Sur, conducted a two-day Carrying Capacity Development Seminar-Workshop on June 30 to July 1, 2026, at Uptown Corporate Hotel, Tandag City.',
        'The activity brought together Municipal Tourism Officers and representatives from the Municipal and Provincial Environment and Natural Resources Offices from different local government units in the province.'
      ]
    },
    {
      title: 'Provincial GFPS Strengthens Gender Mainstreaming in 2nd Quarter Meeting',
      date: 'June 25, 2026',
      image: 'Logos/GFPS.png',
      url: 'https://surigaodelsur.gov.ph/news-single/541',
      paragraphs: [
        'SURIGAO DEL SUR – Strengthening gender-responsive governance requires more than compliance—it demands commitment, collaboration, and a shared understanding that Gender and Development (GAD) is everyone\'s responsibility. This was the central message during the 2nd Quarter Gender and Development (GAD) Focal Point System (GFPS) Meeting held on June 25, 2026, at the Provincial Government Office (PGO) Conference Room.',
        'The quarterly gathering brought together GAD Focal Persons, department representatives, and provincial officials to discuss organizational updates, GAD planning and budgeting, accomplishment reports, and strategies to further institutionalize gender mainstreaming across the Provincial Government of Surigao del Sur.',
        'Opening the meeting, Acting Governor Hon. Manuel D. Alameda Sr. delivered an inspiring welcome message, sharing his personal journey toward appreciating the importance of Gender and Development. Hon. Alameda underscored the need to eliminate discrimination and cultivate a culture where everyone is treated with dignity and respect. He emphasized that opportunities should never be limited by gender, citing an occurrence he witnessed years ago where four women successfully entered CAFGU training—an achievement that proves women are equally capable of excelling in fields traditionally dominated by men.'
      ]
    },
    {
      title: 'DILG Surigao del Sur promotes community-led resilience and disaster preparedness',
      date: 'June 04, 2026',
      image: 'Logos/IMG_0697.jpeg',
      url: 'https://surigaodelsur.gov.ph/news-single/547',
      paragraphs: [
        'The province advanced community resilience and disaster response capacity through province-wide coordination and readiness initiatives.',
        'Local leaders emphasized practical planning, stronger communication pathways, and enhanced public awareness for disaster preparedness.'
      ]
    },
    {
      title: 'Local governance teams review indicators for stronger program performance',
      date: 'May 28, 2026',
      image: 'Logos/IMG_0467.jpeg',
      url: 'https://surigaodelsur.gov.ph/news-single/546',
      paragraphs: [
        'Provincial leaders assessed program performance indicators to guide responsive resource allocation and improve local service outcomes.',
        'The review underscores a collective commitment to measurable results, accountability, and better coordination across all governance levels.'
      ]
    }
  ];

  const rnTitleEl = document.querySelector('.rn-news-title');
  const rnDateEl = document.querySelector('.rn-news-date');
  const rnCopyEl = document.querySelector('.rn-news-copy');
  const rnReadMoreEl = document.querySelector('.rn-read-more');
  const rnFeatureMediaEl = document.querySelector('.rn-feature-media');
  const rnFeatureImageEl = rnFeatureMediaEl?.querySelector('img');
  const rnFeatureImageLinkEl = rnFeatureMediaEl?.querySelector('a');
  const rnFrameEl = document.querySelector('.rn-news-frame');
  const rnDots = Array.from(document.querySelectorAll('.provincial-dot, .rn-dot'));
  const rnHeadlineListEl = document.querySelector('.rn-headline-list');
  let rnCurrentIndex = 0;
  let rnTimer = null;

  if (rnTitleEl && rnDateEl && rnCopyEl && rnReadMoreEl && rnDots.length > 0) {
    const rnListItems = [];

    if (rnHeadlineListEl) {
      rnHeadlineListEl.innerHTML = '';

      provincialNewsItems.forEach((item, index) => {
        const listItem = document.createElement('li');
        listItem.className = 'rn-headline-item';

        const link = document.createElement('button');
        link.type = 'button';
        link.className = 'rn-headline-link';
        link.setAttribute('aria-label', `Show provincial news item ${index + 1}`);

        const text = document.createElement('span');
        text.className = 'headline-text';
        text.textContent = item.title;
        link.appendChild(text);

        link.addEventListener('click', (event) => {
          event.preventDefault();
          rnRender(index);
          rnResetTimer();
        });

        const date = document.createElement('span');
        date.className = 'rn-headline-item-date';
        date.textContent = item.date;

        listItem.appendChild(link);
        listItem.appendChild(date);
        rnHeadlineListEl.appendChild(listItem);
        rnListItems.push(listItem);
      });
    }

    const rnRender = (index) => {
      const item = provincialNewsItems[index];
      if (!item) return;

      rnTitleEl.textContent = item.title;
      rnDateEl.textContent = item.date;
      rnReadMoreEl.setAttribute('href', item.url || '#');
      rnReadMoreEl.setAttribute('target', '_blank');
      rnReadMoreEl.setAttribute('rel', 'noopener noreferrer');

      if (rnFeatureMediaEl && rnFeatureImageEl && rnFeatureImageLinkEl) {
        if (item.image) {
          rnFeatureImageEl.setAttribute('src', item.image);
          rnFeatureImageEl.setAttribute('alt', item.title || 'Provincial news image');
          rnFeatureImageLinkEl.setAttribute('href', item.imageLink || item.url || '#');
          rnFeatureMediaEl.style.display = '';
        } else {
          rnFeatureMediaEl.style.display = 'none';
        }
      }

      rnCopyEl.querySelectorAll('p').forEach((p) => p.remove());
      const rnParagraphs = Array.isArray(item.paragraphs) ? item.paragraphs : (typeof item.description === 'string' && item.description.trim() ? item.description.split(/\r?\n/).filter(Boolean) : []);
      rnParagraphs.forEach((text) => {
        const p = document.createElement('p');
        p.textContent = text;
        rnCopyEl.appendChild(p);
      });

      rnDots.forEach((dot, dotIndex) => {
        const isActive = dotIndex === index;
        dot.classList.toggle('active', isActive);
        dot.style.background = isActive ? '#cc3300' : '#b0b0b0';
      });

      rnListItems.forEach((entry, entryIndex) => {
        entry.classList.toggle('active', entryIndex === index);
      });

      if (rnFrameEl) {
        rnFrameEl.classList.remove('rn-frame-animate');
        void rnFrameEl.offsetWidth;
        rnFrameEl.classList.add('rn-frame-animate');
      }

      rnCurrentIndex = index;
      // notify central news to sync with this provincial index
      try {
        document.dispatchEvent(new CustomEvent('provincial:change', { detail: { index } }));
      } catch (e) {}
    };

    const rnNext = () => {
      const nextIndex = (rnCurrentIndex + 1) % provincialNewsItems.length;
      rnRender(nextIndex);
    };

    const rnResetTimer = () => {
      if (rnTimer) clearInterval(rnTimer);
      rnTimer = setInterval(rnNext, 3500);
    };

    rnDots.forEach((dot, index) => {
      dot.dataset.index = String(index);
      dot.setAttribute('role', 'button');
      dot.setAttribute('tabindex', '0');
      dot.setAttribute('aria-label', `Show PROVINCIAL NEWS item ${index + 1}`);

      dot.addEventListener('click', () => {
        rnRender(index % provincialNewsItems.length);
        rnResetTimer();
      });

      dot.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          rnRender(index % provincialNewsItems.length);
          rnResetTimer();
        }
      });
    });

    rnRender(0);
    rnResetTimer();
  }

  // WHAT'S NEW: mirror the same data-driven carousel pattern as the central and provincial sections
  (async function initWhatsNew() {
    const root = document.querySelector('.whatsnew-featured');
    const indicatorsContainer = document.querySelector('.fw-indicators');
    const prevBtn = document.querySelector('.fw-control.prev');
    const nextBtn = document.querySelector('.fw-control.next');
    const featuredCard = document.querySelector('.featured-card');
    const featuredMediaImg = featuredCard?.querySelector('.featured-media img');
    const featuredMediaLink = featuredCard?.querySelector('.featured-media');
    const featuredTitle = featuredCard?.querySelector('.featured-title');
    const featuredDate = featuredCard?.querySelector('.featured-date');
    const featuredExcerpt = featuredCard?.querySelector('.featured-excerpt');
    const featuredReadMore = document.querySelector('.featured-read-more');
    const listContainer = document.querySelector('.whatsnew-list-items');

    // Load server-driven content first (preferred). If server not available, fall back to central/provincial arrays.
    let items = [];
    const loadServerItems = async () => {
      try {
        const res = await fetch('../../auth/home_api.php?group=featured', { cache: 'no-store' });
        if (!res.ok) throw new Error('No server data');
        const json = await res.json();
        if (json && Array.isArray(json.featured) && json.featured.length) return json.featured;
        if (Array.isArray(json) && json.length) return json;
        if (json && Array.isArray(json.items) && json.items.length) return json.items;
      } catch (e) {
        // ignore, fallback later
      }
      // fallback to existing global arrays
      if (Array.isArray(centralNewsItems) && centralNewsItems.length) return centralNewsItems;
      if (Array.isArray(provincialNewsItems) && provincialNewsItems.length) return provincialNewsItems;
      return [];
    };

    // initialize items (async) and continue
    const initItems = await loadServerItems();
    items = initItems || [];
    if (!root || !items.length) return;

    // If the list container is empty (no static HTML provided), populate it. Otherwise, reuse static list items.
    if (!listContainer.children.length) {
      listContainer.innerHTML = '';
      const compact = items.slice(0, Math.min(items.length, 5));
      compact.forEach((it, idx) => {
        const li = document.createElement('li');
        li.dataset.index = String(idx);

        const link = document.createElement('a');
        link.href = it.url || '#';
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.className = 'whats-link';
        link.textContent = it.title;
        link.addEventListener('click', (event) => {
          event.preventDefault();
          whatsShow(idx % items.length);
          resetWhatsTimer();
        });

        const date = document.createElement('span');
        date.className = 'item-date';
        date.textContent = it.date || '';

        li.appendChild(link);
        li.appendChild(date);
        listContainer.appendChild(li);
      });
    } else {
      // attach event listeners to existing static list items
      Array.from(listContainer.children).forEach((li, idx) => {
        li.dataset.index = String(idx);
        const link = li.querySelector('a.whats-link');
        if (link) {
          link.addEventListener('click', (event) => {
            event.preventDefault();
            whatsShow(idx % items.length);
            resetWhatsTimer();
          });
        }
      });
    }

    // If indicators container already has static buttons, use them; otherwise create indicators dynamically
    if (!indicatorsContainer.children.length) {
      items.forEach((it, i) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('aria-label', `Show WHATS NEW item ${i + 1}`);
        btn.dataset.index = String(i);
        btn.addEventListener('click', () => { whatsShow(i); resetWhatsTimer(); });
        btn.addEventListener('keydown', (ev) => {
          if (ev.key === 'Enter' || ev.key === ' ') {
            ev.preventDefault();
            whatsShow(i);
            resetWhatsTimer();
          }
        });
        indicatorsContainer.appendChild(btn);
      });
    } else {
      // attach event listeners to existing static indicator buttons
      Array.from(indicatorsContainer.children).forEach((btn, i) => {
        btn.dataset.index = String(i);
        // ensure accessibility and clickability
        btn.setAttribute('role', 'button');
        btn.setAttribute('tabindex', '0');
        btn.style.cursor = 'pointer';
        btn.addEventListener('click', (ev) => { ev.preventDefault(); whatsShow(i); resetWhatsTimer(); });
        btn.addEventListener('keydown', (ev) => {
          if (ev.key === 'Enter' || ev.key === ' ') {
            ev.preventDefault();
            whatsShow(i);
            resetWhatsTimer();
          }
        });
      });
    }

    let current = 0;
    let timer = null;

    const whatsShow = (index) => {
      const item = items[index % items.length];
      if (!item) return;
      current = index % items.length;

      if (featuredTitle) featuredTitle.textContent = item.title || '';
      if (featuredDate) featuredDate.textContent = item.date || '';
      if (featuredExcerpt) {
        featuredExcerpt.innerHTML = '';
        const whatsParagraphs = Array.isArray(item.paragraphs) ? item.paragraphs : (typeof item.description === 'string' && item.description.trim() ? item.description.split(/\r?\n/).filter(Boolean) : []);
        whatsParagraphs.forEach((text) => {
          const p = document.createElement('p');
          p.textContent = text;
          featuredExcerpt.appendChild(p);
        });
      }
      if (featuredReadMore) {
        featuredReadMore.textContent = 'Read More';
        featuredReadMore.setAttribute('href', item.url || '#');
        featuredReadMore.setAttribute('target', '_blank');
        featuredReadMore.setAttribute('rel', 'noopener noreferrer');
      }
      if (featuredMediaImg) {
        if (item.image) {
          featuredMediaImg.src = item.image;
          featuredMediaImg.alt = item.title || 'Featured image';
          if (featuredMediaLink) {
            featuredMediaLink.setAttribute('href', item.imageLink || item.url || '#');
            featuredMediaLink.style.display = '';
          }
        } else {
          if (featuredMediaLink) featuredMediaLink.style.display = 'none';
        }
      }

      Array.from(indicatorsContainer.children).forEach((b, i) => {
        b.classList.toggle('active', i === current);
      });

      Array.from(listContainer.children).forEach((li, i) => {
        const itemIndex = Number(li.dataset.index || i);
        li.classList.toggle('active', itemIndex === current);
      });

      if (featuredCard) {
        // Reuse the central news animation class for consistency (preferred CSS animation)
        featuredCard.classList.remove('featured-animate');
        featuredCard.classList.remove('cn-frame-animate');
        void featuredCard.offsetWidth;
        featuredCard.classList.add('cn-frame-animate');

        // JS fallback animation: apply a fade class so environments without the CSS animation still animate
        featuredCard.classList.remove('whats-fade', 'show');
        // ensure reflow
        void featuredCard.offsetWidth;
        featuredCard.classList.add('whats-fade');
        // small timeout to ensure transition applies
        setTimeout(() => featuredCard.classList.add('show'), 20);

        // remove fallback classes after animation completes
        setTimeout(() => {
          featuredCard.classList.remove('whats-fade', 'show');
        }, 500);
      }
    };

    const whatsNext = () => { whatsShow((current + 1) % items.length); };
    const whatsPrev = () => { whatsShow((current - 1 + items.length) % items.length); };
    const resetWhatsTimer = () => { if (timer) clearInterval(timer); timer = setInterval(whatsNext, 3500); };

    if (nextBtn) nextBtn.addEventListener('click', () => { whatsNext(); resetWhatsTimer(); });
    if (prevBtn) prevBtn.addEventListener('click', () => { whatsPrev(); resetWhatsTimer(); });

    root.addEventListener('mouseenter', () => { if (timer) clearInterval(timer); });
    root.addEventListener('mouseleave', () => { resetWhatsTimer(); });
    root.addEventListener('focusin', () => { if (timer) clearInterval(timer); });
    root.addEventListener('focusout', () => { resetWhatsTimer(); });

    whatsShow(0);
    resetWhatsTimer();
  })();

  // Keyboard navigation for carousel
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') {
      nextSlide();
      resetTimer();
    } else if (e.key === 'ArrowLeft') {
      prevSlide();
      resetTimer();
    }
  });

  if (searchForm && searchForm.id !== 'programs-search-form') {
    searchForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const query = searchInput?.value.trim();

      if (!query) {
        alert('Please enter a search term before submitting.');
        return;
      }

      alert(`Search requested for: ${query}`);
    });
  }

  const bindResourceInteractions = () => {
    document.querySelectorAll('.preview-link').forEach((link) => {
      link.removeEventListener('click', link._previewHandler);
      const handler = (event) => {
        event.preventDefault();
        const src = link.dataset.src;
        if (!src) return;
        const modal = document.getElementById('image-modal');
        const modalImage = modal?.querySelector('.modal-image');
        if (!modal || !modalImage) return;

        modalImage.src = src;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
      };

      link._previewHandler = handler;
      link.addEventListener('click', handler);
    });

    document.querySelectorAll('.delete-btn').forEach((btn) => {
      btn.removeEventListener('click', btn._delHandler);
      const handler = () => {
        if (!confirm('Delete this item?')) return;
        const row = btn.closest('.doc-row');
        if (row) row.remove();
      };

      btn._delHandler = handler;
      btn.addEventListener('click', handler);
    });

    const panel = document.querySelector('.resources-panel');
    if (panel) {
      panel.classList.remove('visible');
      void panel.offsetWidth;
      panel.classList.add('visible');
    }
  };

  bindResourceInteractions();

  // Rebind interactions after dynamic UI updates (React panel/tabs/additions)
  document.addEventListener('click', (event) => {
    if (event.target.closest('.category-tab, .add-document, .resources-add-button')) {
      setTimeout(bindResourceInteractions, 0);
    }
  });

  const uploadFileInput = document.getElementById('upload-file-input');
  if (uploadFileInput) {
    let activeButton = null;

    document.addEventListener('click', (event) => {
      const addDocumentButton = event.target.closest('.add-document');
      const addResourceButton = event.target.closest('.resources-add-button');
      if (!addDocumentButton && !addResourceButton) return;

      activeButton = addDocumentButton || addResourceButton;

      if (addResourceButton) {
        const currentTab = document.querySelector('.category-tab.active');
        if (currentTab) {
          window.__resources_active_category = currentTab.textContent.trim();
        }
      }

      uploadFileInput.value = '';
      uploadFileInput.click();
    });

    uploadFileInput.addEventListener('change', () => {
      if (!(uploadFileInput.files && uploadFileInput.files.length > 0) || !activeButton) {
        return;
      }
      const file = uploadFileInput.files[0];

      // Basic client-side validation
      const maxSize = 20 * 1024 * 1024; // 20 MB
      const allowed = /\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|png|jpe?g|gif)$/i;
      if (file.size > maxSize) {
        alert('File is too large. Maximum size is 20 MB.');
        return;
      }
      if (!allowed.test(file.name)) {
        alert('File type not allowed. Allowed: pdf, docx, xls, ppt, txt, images.');
        return;
      }

      // Show inline progress element next to active button
      let progress = activeButton.parentNode.querySelector('.upload-progress');
      if (!progress) {
        progress = document.createElement('div');
        progress.className = 'upload-progress';
        progress.innerHTML = '<div class="bar"><div class="fill" style="width:0%"></div></div><div class="label">Uploading…</div>';
        activeButton.parentNode.insertBefore(progress, activeButton.nextSibling);
      }

      const createCard = (opts) => {
        const grid = document.querySelector('.legal-opinions-grid') || document.querySelector('.issuance-list-wrapper .legal-opinions-grid') || document.querySelector('.issuance-list-wrapper');
        if (!grid) return;

        const card = document.createElement('div');
        card.className = 'doc-row';
        card.setAttribute('data-category', opts.category || 'Uploaded');

        const year = document.createElement('div');
        year.className = 'doc-year';
        year.textContent = opts.year || (new Date().getFullYear());

        const info = document.createElement('div');
        info.className = 'doc-info';
        const meta = document.createElement('div');
        meta.className = 'issuance-meta';
        meta.textContent = opts.number || opts.filename || '';
        const title = document.createElement('h4');
        title.className = 'issuance-title';
        title.textContent = opts.title || opts.filename || 'Uploaded Document';
        const link = document.createElement('a');
        link.className = 'preview-link';
        link.href = opts.url || '#';
        link.dataset.src = opts.url || '';
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = opts.host || 'View document';

        info.appendChild(meta);
        info.appendChild(title);
        info.appendChild(link);

        const actions = document.createElement('div');
        actions.className = 'issuance-actions';
        const openBtn = document.createElement('a');
        openBtn.className = 'open-link';
        openBtn.href = opts.url || '#';
        openBtn.target = '_blank';
        openBtn.rel = 'noopener';
        openBtn.innerHTML = 'View Document <span aria-hidden="true">↗</span>';
        const del = document.createElement('button');
        del.className = 'delete-btn';
        del.setAttribute('aria-label', 'Delete');
        del.textContent = '🗑️';
        del.addEventListener('click', () => card.remove());

        actions.appendChild(openBtn);
        actions.appendChild(del);

        card.appendChild(year);
        card.appendChild(info);
        card.appendChild(actions);

        // If the upload originates from the resources toolbar, place into the resources panel for the matching category
        const resourceCategoryMap = {
          'PBAC': 'PBAC',
          'Quality Management System': 'Quality',
          'Annual Report': 'Annual',
          'Privacy Notice': 'Privacy'
        };
        const isResource = opts.category && Object.values(resourceCategoryMap).includes(opts.category);
        let container = grid.querySelector('.legal-opinions-grid') || grid;
        if (isResource) {
          const resourcesPanel = document.querySelector('.issuance-list-wrapper .resources-panel.open');
          if (resourcesPanel) container = resourcesPanel;
          else {
            const visiblePanel = document.querySelector('.issuance-list-wrapper .resources-panel:not([aria-hidden="true"])');
            if (visiblePanel) container = visiblePanel;
          }
        }

        container.insertBefore(card, container.firstChild);
      };

      const xhr = new XMLHttpRequest();
      const uploadUrl = 'upload.php';
      const fd = new FormData();
      fd.append('file', file);

      let handled = false;
      xhr.open('POST', uploadUrl, true);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.upload.addEventListener('progress', (ev) => {
        if (ev.lengthComputable) {
          const pct = Math.round((ev.loaded / ev.total) * 100);
          progress.querySelector('.fill').style.width = pct + '%';
          progress.querySelector('.label').textContent = `Uploading ${pct}%`;
        }
      });
      xhr.onreadystatechange = () => {
            if (xhr.readyState === 4) {
          try {
              const res = JSON.parse(xhr.responseText || '{}');
              if (xhr.status >= 200 && xhr.status < 300 && res.success && res.url) {
                const category = (activeButton && activeButton.dataset && activeButton.dataset.category) || window.__resources_active_category;
                createCard({ filename: file.name, title: res.title || file.name, url: res.url, number: res.number, year: res.year, host: (new URL(res.url, location.href)).hostname, category });
                handled = true;
              }
          } catch (err) {
          }
              if (!handled) {
                const blobUrl = URL.createObjectURL(file);
                const category = (activeButton && activeButton.dataset && activeButton.dataset.category) || window.__resources_active_category;
                createCard({ filename: file.name, title: file.name, url: blobUrl, host: 'local', category });
              }
          if (progress && progress.parentNode) progress.parentNode.removeChild(progress);
              // clear the temporary global marker
              try { window.__resources_active_category = undefined; } catch (e) {}
        }
      };
      xhr.onerror = () => {
        const blobUrl = URL.createObjectURL(file);
        const category = (activeButton && activeButton.dataset && activeButton.dataset.category) || window.__resources_active_category;
        createCard({ filename: file.name, title: file.name, url: blobUrl, host: 'local', category });
        if (progress && progress.parentNode) progress.parentNode.removeChild(progress);
        try { window.__resources_active_category = undefined; } catch (e) {}
      };
      try {
        xhr.send(fd);
      } catch (err) {
        const blobUrl = URL.createObjectURL(file);
        const category = (activeButton && activeButton.dataset && activeButton.dataset.category) || window.__resources_active_category;
        createCard({ filename: file.name, title: file.name, url: blobUrl, host: 'local', category });
        if (progress && progress.parentNode) progress.parentNode.removeChild(progress);
        try { window.__resources_active_category = undefined; } catch (e) {}
      }
    });
  }

  updateClock();
  setInterval(updateClock, 1000);
  updateLegalOpinionRegion();

  const orgImagePanel = document.getElementById('org-image-panel');
  const orgImageToggle = document.getElementById('org-image-toggle');
  if (orgImagePanel && orgImageToggle) {
    orgImageToggle.addEventListener('click', () => {
      const expanded = orgImagePanel.classList.toggle('expanded');
      orgImageToggle.setAttribute('aria-expanded', expanded.toString());
    });
  }

  // Image preview modal and delete functionality
  const modal = document.getElementById('image-modal');
  const modalImage = modal?.querySelector('.modal-image');
  const modalClose = modal?.querySelector('.modal-close');

  const openModal = (src) => {
    if (!modal || !modalImage) return;
    modalImage.src = src;
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  };

  const closeModal = () => {
    if (!modal || !modalImage) return;
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    modalImage.src = '';
  };

  document.querySelectorAll('.preview-link').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const src = link.dataset.src;
      if (src) openModal(src);
    });
  });

  // --- Authentication UI injection and handlers ---
  (function initAuthUI(){
    const navList = document.querySelector('.topnav-center .nav-list');
    if (!navList) return;

    const createNavItem = (html, id) => {
      const li = document.createElement('li');
      li.className = 'nav-item';
      li.id = id;
      li.innerHTML = html;
      return li;
    };

    const loginBtn = createNavItem('<a href="#" id="login-open" class="nav-link"><span class="nav-label">Login</span></a>', 'login-nav-item');

    const ensureLoginVisible = () => {
      // Place Login as a normal nav-list item immediately after the LGRC and GAD menu
      if (!navList) return;
      const existing = document.querySelector('#login-nav-item');
      if (existing) return;

      // Find LGRC item and insert after it; otherwise append at end
      const lgrc = navList.querySelector('a[data-page="lgrc-gad"]');
      if (lgrc && lgrc.closest('li')) {
        const ref = lgrc.closest('li');
        ref.parentNode.insertBefore(loginBtn, ref.nextSibling);
      } else {
        navList.appendChild(loginBtn);
      }
    };

    const modalHtml = `
      <div id="auth-modal" class="modal auth-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="auth-modal-title">
        <div class="modal-backdrop"></div>
        <div class="modal-content auth-panel" role="document" aria-labelledby="auth-modal-title">
          <button type="button" class="modal-close" aria-label="Close">✕</button>

          <div class="auth-avatar" aria-hidden="true">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5z" fill="#fff"/>
              <path d="M4 20c0-3.314 2.686-6 6-6h4c3.314 0 6 2.686 6 6v1H4v-1z" fill="#fff"/>
            </svg>
          </div>

          <h2 id="auth-modal-title" class="auth-heading">Sign In</h2>

          <form id="auth-form" class="login-card-form" novalidate>
            <div class="input-row input-with-icon">
              <label class="form-label" for="auth-username">Username or Email</label>
              <div class="input-wrap">
                <span class="input-icon" aria-hidden="true">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5z" fill="currentColor"/><path d="M4 20c0-3.314 2.686-6 6-6h4c3.314 0 6 2.686 6 6v1H4v-1z" fill="currentColor"/></svg>
                </span>
                <input id="auth-username" name="username" type="text" autocomplete="username" required aria-describedby="auth-username-help" />
              </div>
              <div class="field-help" id="auth-username-help" aria-hidden="true"></div>
            </div>

            <div class="input-row input-with-icon">
              <label class="form-label" for="auth-password">Password</label>
              <div class="input-wrap">
                <span class="input-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 8V7a5 5 0 0 0-10 0v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="3" y="10" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <input id="auth-password" name="password" type="password" autocomplete="current-password" required />
                <button type="button" id="auth-toggle-pwd" class="pwd-toggle" aria-pressed="false" aria-label="Show password">Show</button>
              </div>
            </div>

            <div class="login-options-row">
              <label class="checkbox checkbox-inline"><input type="checkbox" id="auth-remember" name="remember"> Remember me</label>
              <span class="forgot-link">Contact an administrator to reset your password</span>
            </div>

            <div id="auth-error" class="form-error" role="alert" aria-live="assertive"></div>

            <button type="submit" id="auth-submit" class="login-submit">
              <span class="btn-text">LOGIN</span>
              <span class="btn-loader" aria-hidden="true"></span>
            </button>
          </form>
        </div>
      </div>`;

    ensureLoginVisible();
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    const authModal = document.getElementById('auth-modal');
    const authForm = document.getElementById('auth-form');
    const authSubmit = document.getElementById('auth-submit');
    const authError = document.getElementById('auth-error');
    const authToggle = document.getElementById('auth-toggle-pwd');
    const authPassword = document.getElementById('auth-password');
    const authUsername = document.getElementById('auth-username');

    const openModal = () => {
      authModal.classList.add('open');
      authModal.setAttribute('aria-hidden','false');
      setTimeout(()=> authUsername.focus(), 50);
      // trap focus
      document.addEventListener('focus', trapFocus, true);
    };

    const closeModal = () => {
      authModal.classList.remove('open');
      authModal.setAttribute('aria-hidden','true');
      document.removeEventListener('focus', trapFocus, true);
    };

    const loginTrigger = document.getElementById('login-open');
    loginTrigger?.addEventListener('click', (e)=>{ e.preventDefault(); openModal();});
    authModal.querySelector('.modal-close').addEventListener('click', closeModal);

    // Close when clicking the backdrop
    authModal.querySelector('.modal-backdrop').addEventListener('click', closeModal);

    // Password toggle
    authToggle?.addEventListener('click', (e)=>{
      const isShown = authPassword.type === 'text';
      authPassword.type = isShown ? 'password' : 'text';
      authToggle.textContent = isShown ? 'Show' : 'Hide';
      authToggle.setAttribute('aria-pressed', String(!isShown));
    });

    // Basic focus trap
    function trapFocus(e){
      if (!authModal.classList.contains('open')) return;
      if (!authModal.contains(e.target)) {
        e.stopPropagation();
        authUsername.focus();
      }
    }

    // Close on Escape
    document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape' && authModal.classList.contains('open')) closeModal(); });

    // Ensure Login opens the modal even if the anchor was added dynamically — use event delegation on navList
    if (navList) {
      navList.addEventListener('click', (e) => {
        const target = e.target.closest('#login-open');
        if (target) { e.preventDefault(); openModal(); }
      });
    }

    async function refreshAuthState(){
      // Simply ensure Login button is always visible
      // Users log in via modal and are redirected to their dashboard
      ensureLoginVisible();
    }

    authForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      authError.textContent = '';
      const u = authUsername.value.trim();
      const p = authPassword.value;
      if (!u) { authError.textContent = 'Please enter username or email'; authUsername.focus(); return; }
      if (!p) { authError.textContent = 'Please enter password'; authPassword.focus(); return; }

      try {
        // set loading state
        authSubmit.disabled = true;
        authSubmit.classList.add('loading');

        const payload = { username: u, password: p, remember: !!document.getElementById('auth-remember')?.checked };
        
        let res;
        try {
          res = await fetch('../../auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
        } catch (networkErr) {
          // Network connectivity issue
          authError.textContent = 'Cannot connect to server. Check your internet connection.';
          authSubmit.disabled = false;
          authSubmit.classList.remove('loading');
          console.error('Network error:', networkErr);
          return;
        }

        let j;
        try {
          j = await res.json();
        } catch (jsonErr) {
          // Server responded but with invalid JSON (likely PHP error)
          authError.textContent = 'Server error. Please try again later.';
          authSubmit.disabled = false;
          authSubmit.classList.remove('loading');
          console.error('Invalid server response:', jsonErr, 'Status:', res.status);
          return;
        }

        if (!res.ok) {
          // API returned an error (4xx/5xx)
          authError.textContent = j.error || 'Login failed. Please check your credentials.';
          authSubmit.disabled = false;
          authSubmit.classList.remove('loading');
          return;
        }

        // successful login — redirect by role
        if (j.role === 'ADMIN') location.href = '../../admin-dashboard.php';
        else if (j.role === 'FO USER') location.href = '../../user-portals/fo/fo-dashboard.php';
        else if (j.role === 'PO USER') location.href = '../../user-portals/po/po-dashboard.php';
        else location.href = 'index.html';
      } catch (err) {
        authError.textContent = 'An unexpected error occurred. Please try again.';
        authSubmit.disabled = false;
        authSubmit.classList.remove('loading');
        console.error('Unexpected error:', err);
      }
    });

    refreshAuthState();
    setInterval(refreshAuthState, 60*1000);
  })();

  modal?.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-backdrop')) closeModal();
  });

  modalClose?.addEventListener('click', closeModal);
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

  document.querySelectorAll('.delete-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!confirm('Delete this item?')) return;
      const row = btn.closest('.doc-row');
      if (row) row.remove();
    });
  });

  const pages = Array.from(document.querySelectorAll('.page'));
  const navLinks = Array.from(document.querySelectorAll('.topnav-center a'));

  const showPage = (pageId) => {
    pages.forEach((page) => {
      const isActive = page.id === pageId;
      page.classList.toggle('active', isActive);
      page.style.display = isActive ? '' : 'none';
      page.setAttribute('aria-hidden', isActive ? 'false' : 'true');
    });
    navLinks.forEach((link) => {
      link.classList.toggle('active', link.dataset.page === pageId.replace('page-', ''));
    });
    
    // Trigger animations for About Us page
    if (pageId === 'page-about') {
      setTimeout(() => {
        const missionSections = document.querySelectorAll('.mission-vision-section');
        missionSections.forEach((section) => {
          section.classList.remove('visible');
          void section.offsetWidth;
          section.classList.add('visible');
        });
      }, 50);
    }

    if (pageId === 'page-issuances') {
      window.filterCategory('All');
    }

    if (pageId === 'page-programs') {
      const programsAccordion = document.getElementById('programs-accordion');
      if (programsAccordion) {
        // Ensure first accordion item is expanded
        const firstItem = programsAccordion.querySelector('.accordion-item');
        const otherItems = programsAccordion.querySelectorAll('.accordion-item:not(:first-child)');
        
        if (firstItem) {
          firstItem.classList.add('active');
          const trigger = firstItem.querySelector('.accordion-trigger');
          if (trigger) trigger.setAttribute('aria-expanded', 'true');
        }
        
        // Collapse other items
        otherItems.forEach((item) => {
          item.classList.remove('active');
          const trigger = item.querySelector('.accordion-trigger');
           if (trigger) trigger.setAttribute('aria-expanded', 'false');
        });
      }
     }

    // Trigger heading animation for Core Values page
    if (pageId === 'page-core-values') {
      const heading = document.querySelector('.core-values-heading');
      if (heading) {
        heading.classList.remove('animate-on');
        void heading.offsetWidth;
        setTimeout(() => heading.classList.add('animate-on'), 30);
      }
    }

    // Reload Facebook widgets for News page if present
    if (pageId === 'page-news' && window.FB && window.FB.XFBML) {
      setTimeout(() => {
        window.FB.XFBML.parse();
      }, 100);
    }

    // Scroll to top when navigating to a new page
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  navLinks.forEach((link) => {
    link.addEventListener('click', (event) => {
      const href = link.getAttribute('href');
      const isHashLink = !href || href === '#';
      // If the nav link is a top-level item with a dropdown (e.g., About Us), do not navigate —
      // let the dropdown toggle behavior (handled on the parent .nav-item) take over.
      if (link.closest('.has-dropdown')) {
        if (!isHashLink) {
          return;
        }
        event.preventDefault();
        return;
      }
      const page = link.dataset.page;
      if (!isHashLink && page) {
        return;
      }
      if (page && isHashLink) {
        event.preventDefault();
        showPage(`page-${page}`);
        if (typeof closeAllDropdowns === 'function') closeAllDropdowns();
      }
    });
  });

  // Dropdown item handlers (About subsections and Issuances categories)
  document.querySelectorAll('.dropdown-item').forEach((item) => {
    item.addEventListener('click', (e) => {
      const action = item.dataset.action;
      const href = item.getAttribute('href');
      const isDirectLink = href && href !== '#';
      const label = item.textContent.trim().toLowerCase();

      if ((label === 'joint circulars' || href?.includes('joint-circulars.html')) && !isDirectLink) {
        e.preventDefault();
        window.location.href = 'joint-circulars.html';
        return;
      }

      if (!action || isDirectLink) {
        return;
      }
      e.preventDefault();
      if (action === 'about') {
        const target = item.dataset.target;
        showPage('page-about');
        setTimeout(() => {
          const el = document.getElementById(target);
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 60);
      } else if (action === 'core-values') {
        showPage('page-core-values');
      } else if (action === 'issuances') {
        const category = item.dataset.category;
        const targetPages = {
          'Joint Circulars': 'joint-circulars.html',
          'Memorandum Circulars': 'memo-circulars.html',
          'Memo Circulars': 'memo-circulars.html',
          'Presidential Directives': 'presidential-directives.html',
          'Republic Acts': 'republic-acts.html',
        };
        const targetPage = targetPages[category];
        if (targetPage) {
          window.location.href = targetPage;
          return;
        }
        showPage('page-issuances');
        setTimeout(() => {
          if (window.filterCategory) window.filterCategory(category);
        }, 60);
      } else if (action === 'citizens') {
        const cc = item.dataset.cc;
        showPage('page-citizens-charter');
        setTimeout(() => {
          if (cc) {
            const panel = document.getElementById(`cc-${cc}`);
            if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }, 60);
      } else if (action === 'legal-opinion') {
        const region = item.dataset.region;
        showPage('page-legal-opinion');
        if (region) {
          const filterLabel = document.querySelector('.legal-opinions-region-label');
          if (filterLabel) {
            filterLabel.textContent = `Region: ${region}`;
          }
        }
      }
      if (typeof closeAllDropdowns === 'function') closeAllDropdowns();
    });
  });

  // Dropdowns open on hover/focus and can be toggled on touch devices.
  const navItems = Array.from(document.querySelectorAll('.topnav-center .nav-item'));
  const navItemsWithDropdown = navItems.filter((ni) => ni.querySelector('.nav-dropdown'));
  const submenuWrappers = Array.from(document.querySelectorAll('.dropdown-item-with-submenu'));
  let navHoverPrevActive = null; // store previously active nav link while hovering dropdowns

  const closeAllDropdowns = () => {
    navItemsWithDropdown.forEach((ni) => {
      ni.classList.remove('open');
      ni.querySelector('.nav-link')?.setAttribute('aria-expanded', 'false');
      const dd = ni.querySelector('.nav-dropdown');
      if (dd) dd.setAttribute('aria-hidden', 'true');
    });

    submenuWrappers.forEach((wrapper) => {
      wrapper.classList.remove('open');
      wrapper.setAttribute('aria-expanded', 'false');
      const child = wrapper.querySelector('.child-dropdown');
      if (child) child.setAttribute('aria-hidden', 'true');
    });
    // If a dropdown was open via hover and we saved a previous active link,
    // restore it when dropdowns are closed programmatically (click outside,
    // etc.).
    if (typeof navHoverPrevActive !== 'undefined' && navHoverPrevActive) {
      navHoverPrevActive.classList.add('active');
      navHoverPrevActive = null;
    }
  };

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.topnav')) {
      closeAllDropdowns();
    }
  });

  navItemsWithDropdown.forEach((navItem) => {
    const dropdown = navItem.querySelector('.nav-dropdown');
    const toggleLink = navItem.querySelector('.nav-link');
    if (toggleLink) {
      toggleLink.setAttribute('aria-haspopup', 'true');
      toggleLink.setAttribute('aria-expanded', 'false');
    }
    if (dropdown) dropdown.setAttribute('aria-hidden', 'true');

    navItem.addEventListener('mouseenter', () => {
      // Close any open dropdowns first so closeAllDropdowns doesn't overwrite our save
      closeAllDropdowns();
      // When hovering a dropdown, temporarily remove any existing `active` marker
      // so only the hovered menu shows the blue state. Save the previous active
      // link to restore it later if the page remains the same.
      const currentActive = document.querySelector('.topnav-center a.active, .topnav-center .nav-item.active');
      if (currentActive && currentActive !== toggleLink) {
        navHoverPrevActive = currentActive;
        currentActive.classList.remove('active');
      }
      navItem.classList.add('open');
      toggleLink?.setAttribute('aria-expanded', 'true');
      if (dropdown) dropdown.setAttribute('aria-hidden', 'false');
    });
    navItem.addEventListener('mouseleave', () => {
      // Close dropdown and restore previous active state if the page hasn't changed.
      navItem.classList.remove('open');
      toggleLink?.setAttribute('aria-expanded', 'false');
      if (dropdown) dropdown.setAttribute('aria-hidden', 'true');

      if (navHoverPrevActive) {
        navHoverPrevActive.classList.add('active');
        navHoverPrevActive = null;
      }
    });
    navItem.addEventListener('click', (e) => {
      if (e.target.closest('.nav-dropdown')) return;
      e.preventDefault();
      const isOpen = navItem.classList.contains('open');
      closeAllDropdowns();
      if (isOpen) {
        navItem.classList.remove('open');
        toggleLink?.setAttribute('aria-expanded', 'false');
        if (dropdown) dropdown.setAttribute('aria-hidden', 'true');
      } else {
        // When opening via click, temporarily remove any other `active` marker
        const currentActive = document.querySelector('.topnav-center a.active, .topnav-center .nav-item.active');
        if (currentActive && currentActive !== toggleLink) {
          navHoverPrevActive = currentActive;
          currentActive.classList.remove('active');
        }
        navItem.classList.add('open');
        toggleLink?.setAttribute('aria-expanded', 'true');
        if (dropdown) dropdown.setAttribute('aria-hidden', 'false');
      }
    });
  });

  submenuWrappers.forEach((wrapper) => {
    const childDropdown = wrapper.querySelector('.child-dropdown');
    wrapper.setAttribute('aria-haspopup', 'true');
    wrapper.setAttribute('aria-expanded', 'false');
    if (childDropdown) childDropdown.setAttribute('aria-hidden', 'true');

    wrapper.addEventListener('mouseenter', () => {
      submenuWrappers.forEach((other) => {
        if (other !== wrapper) {
          other.classList.remove('open');
          other.setAttribute('aria-expanded', 'false');
          const otherChild = other.querySelector('.child-dropdown');
          if (otherChild) otherChild.setAttribute('aria-hidden', 'true');
        }
      });
      wrapper.classList.add('open');
      wrapper.setAttribute('aria-expanded', 'true');
      if (childDropdown) childDropdown.setAttribute('aria-hidden', 'false');
    });

    wrapper.addEventListener('mouseleave', () => {
      wrapper.classList.remove('open');
      wrapper.setAttribute('aria-expanded', 'false');
      if (childDropdown) childDropdown.setAttribute('aria-hidden', 'true');
    });

    wrapper.addEventListener('click', (e) => {
      const directLink = e.target.closest('a[href]');
      if (directLink && directLink.getAttribute('href') && directLink.getAttribute('href') !== '#') {
        return;
      }
      if (e.target.closest('.child-dropdown')) return;
      e.preventDefault();
      e.stopPropagation();
      const isOpen = wrapper.classList.contains('open');
      submenuWrappers.forEach((other) => {
        if (other !== wrapper) {
          other.classList.remove('open');
          other.setAttribute('aria-expanded', 'false');
          const otherChild = other.querySelector('.child-dropdown');
          if (otherChild) otherChild.setAttribute('aria-hidden', 'true');
        }
      });
      if (isOpen) {
        wrapper.classList.remove('open');
        wrapper.setAttribute('aria-expanded', 'false');
        if (childDropdown) childDropdown.setAttribute('aria-hidden', 'true');
      } else {
        wrapper.classList.add('open');
        wrapper.setAttribute('aria-expanded', 'true');
        if (childDropdown) childDropdown.setAttribute('aria-hidden', 'false');
      }
    });
  });

  // Close dropdowns when clicking outside the nav
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.topnav-center')) {
      closeAllDropdowns();
    }
  });

  const contactConfigUrl = 'contact-config.json';
  const contactForm = document.getElementById('contact-form');
  const contactStatus = document.getElementById('contact-form-status');
  const contactPhone = document.getElementById('contact-phone');
  const contactEmail = document.getElementById('contact-email');
  const contactFacebook = document.getElementById('contact-facebook');
  const contactLocation = document.getElementById('contact-location');
  const contactHours = document.getElementById('contact-hours');
  const contactNotes = document.getElementById('contact-notes');
  const viewMapLink = document.getElementById('view-map-link');
  const contactMapEmbed = document.getElementById('contact-map-embed');
  const recaptchaTokenInput = document.getElementById('contact-recaptcha-token');
  const botField = document.getElementById('bot-field');
  let recaptchaKey = '';

  const setStatus = (message, success = true) => {
    if (!contactStatus) return;
    contactStatus.textContent = message;
    contactStatus.classList.toggle('success', success);
    contactStatus.classList.toggle('error', !success);
  };

  const loadContactConfig = async () => {
    try {
      const response = await fetch(contactConfigUrl, { cache: 'no-store' });
      if (!response.ok) throw new Error('Unable to load contact content.');
      const config = await response.json();
      if (contactPhone) {
        contactPhone.textContent = config.phone || '';
        contactPhone.href = `tel:${(config.phone || '').replace(/[^0-9+]/g, '')}`;
      }
      if (contactEmail) {
        contactEmail.textContent = config.email || '';
        contactEmail.href = `mailto:${config.email || ''}`;
      }
      if (contactFacebook) {
        contactFacebook.textContent = config.facebook || '';
        contactFacebook.href = config.facebook || '#';
      }
      if (contactLocation) contactLocation.textContent = config.officeLocation || '';
      if (contactHours) contactHours.textContent = config.officeHours || '';
      if (contactNotes) contactNotes.textContent = config.additionalInfo || '';
      if (viewMapLink) viewMapLink.href = config.mapUrl || 'https://www.google.com/maps';
      if (contactMapEmbed) contactMapEmbed.src = config.mapEmbedUrl || contactMapEmbed.src;
      recaptchaKey = config.recaptchaSiteKey || '';
      if (recaptchaKey && window.grecaptcha) {
        grecaptcha.ready(() => {
          grecaptcha.execute(recaptchaKey, { action: 'contact' }).then((token) => {
            if (recaptchaTokenInput) recaptchaTokenInput.value = token;
          });
        });
      }
    } catch (error) {
      setStatus('Unable to load contact details at this time.', false);
    }
  };

  const validateContactForm = () => {
    if (!contactForm) return false;
    const firstName = contactForm.querySelector('#first-name');
    const lastName = contactForm.querySelector('#last-name');
    const email = contactForm.querySelector('#email');
    const subject = contactForm.querySelector('#subject');
    const message = contactForm.querySelector('#message');
    if (botField && botField.value.trim()) {
      setStatus('Submission blocked by spam protection.', false);
      return false;
    }
    if (!firstName?.value.trim() || !lastName?.value.trim() || !email?.value.trim() || !subject?.value.trim() || !message?.value.trim()) {
      setStatus('Please fill in all required fields before submitting.', false);
      return false;
    }
    const emailValue = email.value.trim();
    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue);
    if (!emailValid) {
      setStatus('Please enter a valid email address.', false);
      return false;
    }
    return true;
  };

  const submitContactForm = async (payload) => {
    if (!contactForm) return;
    const submitBtn = contactForm.querySelector('.submit-btn');
    const btnText = submitBtn?.querySelector('.btn-text');
    try {
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
      }
      setStatus('Sending your message, please wait...');
      const response = await fetch('contact-submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Submission failed.');
      }
      setStatus(result.message || 'Your message has been sent successfully.', true);
      contactForm.reset();
      if (recaptchaKey && window.grecaptcha) {
        grecaptcha.ready(() => {
          grecaptcha.execute(recaptchaKey, { action: 'contact' }).then((token) => {
            if (recaptchaTokenInput) recaptchaTokenInput.value = token;
          });
        });
      }
      return true;
    } catch (error) {
      setStatus(error.message || 'Unable to submit the form. Please try again later.', false);
      return false;
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
      }
    }
  };

  if (contactForm) {
    contactForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!validateContactForm()) return;
      const payload = {
        firstName: contactForm.querySelector('#first-name')?.value.trim(),
        lastName: contactForm.querySelector('#last-name')?.value.trim(),
        email: contactForm.querySelector('#email')?.value.trim(),
        subject: contactForm.querySelector('#subject')?.value.trim(),
        message: contactForm.querySelector('#message')?.value.trim(),
        subscribe: contactForm.querySelector('#subscribe')?.checked ? 1 : 0,
        botField: botField?.value || '',
        recaptchaToken: recaptchaTokenInput?.value || ''
      };
      await submitContactForm(payload);
    });

    loadContactConfig();
  }

  // Login page behavior: tabs and show-password
  const loginTabs = Array.from(document.querySelectorAll('.login-tab'));
  if (loginTabs.length) {
    loginTabs.forEach((tab) => {
      tab.addEventListener('click', (e) => {
        const which = tab.dataset.tab;
        loginTabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected','false'); });
        tab.classList.add('active');
        tab.setAttribute('aria-selected','true');
        const heading = document.querySelector('.login-heading');
        const submit = document.querySelector('.login-submit');
        if (which === 'signin') {
          if (heading) heading.textContent = 'Sign in for existing users';
          if (submit) submit.textContent = 'Sign in';
        } else {
          if (heading) heading.textContent = 'Create an account';
          if (submit) submit.textContent = 'Create account';
        }
      });
    });
  }

  const showPwd = document.getElementById('show-password');
  const pwdInput = document.getElementById('login-password');
  if (showPwd && pwdInput) {
    showPwd.addEventListener('change', () => {
      pwdInput.type = showPwd.checked ? 'text' : 'password';
    });
  }

  const signinForm = document.getElementById('signin-form');

  const officialsCarouselContainer = document.getElementById('officials-carousel');
  const officialsCarouselCurrent = document.getElementById('officials-carousel-current');
  const officialsCarouselTotal = document.getElementById('officials-carousel-total');
  let officialsActiveIndex = 0;

  const renderOfficialsCarousel = () => {
    if (!officialsCarouselContainer || !officialsCarouselCurrent || !officialsCarouselTotal || !window.officialsPageData?.carousel?.length) return;
    const slides = Array.from(officialsCarouselContainer.querySelectorAll('.official-slide'));
    officialsCarouselTotal.textContent = String(window.officialsPageData.carousel.length);
    slides.forEach((slide, index) => {
      slide.classList.toggle('active', index === officialsActiveIndex);
    });
    officialsCarouselCurrent.textContent = (officialsActiveIndex + 1).toString();
  };

  const changeOfficialsSlide = (direction) => {
    if (!window.officialsPageData?.carousel?.length) return;
    const count = window.officialsPageData.carousel.length;
    if (direction === 'next') {
      officialsActiveIndex = (officialsActiveIndex + 1) % count;
    } else if (direction === 'prev') {
      officialsActiveIndex = (officialsActiveIndex - 1 + count) % count;
    }
    renderOfficialsCarousel();
  };

  const startOfficialsAutoSlide = () => {
    if (!window.officialsPageData?.carousel?.length) return null;
    return setInterval(() => changeOfficialsSlide('next'), 6000);
  };

  let officialsAutoSlide = null;
  if (officialsCarouselContainer && window.officialsPageData?.carousel?.length) {
    renderOfficialsCarousel();
    officialsAutoSlide = startOfficialsAutoSlide();
    officialsCarouselContainer.addEventListener('mouseenter', () => {
      if (officialsAutoSlide) clearInterval(officialsAutoSlide);
    });
    officialsCarouselContainer.addEventListener('mouseleave', () => {
      if (officialsAutoSlide) officialsAutoSlide = startOfficialsAutoSlide();
    });
  }

  document.querySelectorAll('.officials-carousel-controls .carousel-btn').forEach((button) => {
    button.addEventListener('click', () => {
      const action = button.dataset.action;
      if (action) {
        changeOfficialsSlide(action);
        if (officialsAutoSlide) {
          clearInterval(officialsAutoSlide);
          officialsAutoSlide = startOfficialsAutoSlide();
        }
      }
    });
  });

  document.querySelectorAll('.official-slide').forEach((slide) => {
    slide.addEventListener('click', (event) => {
      const href = slide.getAttribute('href');
      if (href) {
        window.open(href, '_blank');
      }
    });
  });

  const programsAccordion = document.getElementById('programs-accordion');
  const programSearchInput = document.getElementById('programs-search');
  const programsSearchForm = document.getElementById('programs-search-form');
  const programsCount = document.getElementById('programs-outcome-count');
  const programsNoResults = document.getElementById('programs-no-results');

  const updateAccordionCount = () => {
    const visibleItems = programsAccordion ? programsAccordion.querySelectorAll('.accordion-item:not(.hidden)') : [];
    if (programsCount) programsCount.textContent = visibleItems.length;
    if (programsNoResults) {
      programsNoResults.hidden = visibleItems.length > 0;
    }
  };

  const setAccordionItemOpen = (item, open) => {
    const trigger = item.querySelector('.accordion-trigger');
    item.classList.toggle('active', open);
    if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
  };

  const closeAllAccordionItems = () => {
    if (!programsAccordion) return;
    programsAccordion.querySelectorAll('.accordion-item').forEach((item) => {
      setAccordionItemOpen(item, false);
    });
  };

  if (programsAccordion) {
    programsAccordion.querySelectorAll('.accordion-item').forEach((item) => {
      const trigger = item.querySelector('.accordion-trigger');
      const panel = item.querySelector('.accordion-panel');
      if (!trigger || !panel) return;

      trigger.addEventListener('click', () => {
        const isActive = item.classList.contains('active');
        closeAllAccordionItems();
        if (!isActive) {
          setAccordionItemOpen(item, true);
        }
      });
    });
  }

  if (programsSearchForm) {
    programsSearchForm.addEventListener('submit', (event) => {
      event.preventDefault();
    });
  }

  if (programSearchInput) {
    const filterPrograms = () => {
      const query = programSearchInput.value.trim().toLowerCase();
      const items = programsAccordion?.querySelectorAll('.accordion-item') || [];
      let firstMatch = null;

      items.forEach((item) => {
        const title = item.querySelector('.accordion-title')?.textContent.trim().toLowerCase() || '';
        const programs = Array.from(item.querySelectorAll('.program-link'));
        let programMatch = false;

        programs.forEach((link) => {
          const text = link.textContent.trim().toLowerCase();
          const show = !query || text.includes(query);
          link.classList.toggle('is-hidden', !show);
          if (show) programMatch = true;
        });

        const shouldShow = !query || title.includes(query) || programMatch;
        item.classList.toggle('hidden', !shouldShow);

        if (shouldShow && query) {
          setAccordionItemOpen(item, true);
          if (!firstMatch) firstMatch = item;
        } else if (!query) {
          setAccordionItemOpen(item, item === items[0]);
        } else {
          setAccordionItemOpen(item, false);
        }
      });

      updateAccordionCount();
    };

    programSearchInput.addEventListener('input', filterPrograms);
  }


  if (signinForm) {
    signinForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const btn = signinForm.querySelector('.login-submit');
      if (btn) { btn.disabled = true; btn.textContent = (btn.textContent || 'Sign in') + '...'; }
      setTimeout(() => {
        alert('Signed in (simulated).');
        if (btn) { btn.disabled = false; btn.textContent = btn.textContent.replace('...',''); }
      }, 900);
    });
  }


  const addOfficialBtn = document.getElementById('add-official-btn');
  const officialsModal = document.getElementById('officials-modal');
  const officialsDirectory = document.getElementById('officialsDirectory');
  const officialsEmpty = document.getElementById('officials-empty');
  const officialsCount = document.getElementById('officials-count');
  const officialsModalClose = document.getElementById('officials-modal-close');
  const officialsCancelBtn = document.getElementById('officials-cancel-btn');
  const officialsForm = document.getElementById('officials-form');
  const officialNameInput = document.getElementById('official-name');
  const officialPositionInput = document.getElementById('official-position');
  const officialDistrictInput = document.getElementById('official-district');
  const officialAffiliationInput = document.getElementById('official-affiliation');
  const officialTermInput = document.getElementById('official-term');
  const officialContactInput = document.getElementById('official-contact');
  const officialAddressInput = document.getElementById('official-address');
  const officialUrlFbInput = document.getElementById('official-url-fb');
  const officialUrlWebInput = document.getElementById('official-url-web');
  const officialUrlProfileInput = document.getElementById('official-url-profile');
  const officialNoteInput = document.getElementById('official-note');
  const officialsModalTitle = document.getElementById('officials-modal-title');

  const OFFICIALS_STORAGE_KEY = 'dilgElectedOfficials';
  let editingOfficialId = null;

  const defaultOfficials = [
    {
      id: '1', name: 'Governor Alexander T. Pimentel', position: 'Provincial Governor',
      district: 'Surigao del Sur', affiliation: 'PDP-Laban', term: '2025–2028',
      contact: '(086) 214-3470', address: 'Provincial Capitol, Capitol Hills, Tandag City',
      urlFb: 'https://www.facebook.com/DILGsds', urlWeb: 'https://surigaodelsur.gov.ph',
      urlProfile: '#', note: 'Current term 2025–2028'
    },
    {
      id: '2', name: 'Vice Governor Manuel C. Alameda', position: 'Provincial Vice Governor',
      district: 'Surigao del Sur', affiliation: 'PDP-Laban', term: '2025–2028',
      contact: '(086) 214-3471', address: 'Provincial Capitol, Capitol Hills, Tandag City',
      urlFb: 'https://www.facebook.com/DILGsds', urlWeb: 'https://surigaodelsur.gov.ph',
      urlProfile: '#', note: 'Presides over the Sangguniang Panlalawigan'
    },
    {
      id: '3', name: 'Board Member Maria L. Santos', position: 'Sangguniang Panlalawigan Member',
      district: '1st District', affiliation: 'PDP-Laban', term: '2025–2028',
      contact: '(086) 214-3472', address: 'Provincial Capitol, Capitol Hills, Tandag City',
      urlFb: 'https://www.facebook.com/DILGsds', urlWeb: '#', urlProfile: '#', note: ''
    },
    {
      id: '4', name: 'Board Member Jose R. Mercado', position: 'Sangguniang Panlalawigan Member',
      district: '2nd District', affiliation: 'Lakas-CMD', term: '2025–2028',
      contact: '(086) 214-3473', address: 'Provincial Capitol, Capitol Hills, Tandag City',
      urlFb: 'https://www.facebook.com/DILGsds', urlWeb: '#', urlProfile: '#', note: ''
    }
  ];

  const loadOfficialsData = () => {
    try {
      const stored = localStorage.getItem(OFFICIALS_STORAGE_KEY);
      if (stored) {
        const saved = JSON.parse(stored);
        if (Array.isArray(saved) && saved.length > 0) return saved;
      }
    } catch (error) {
      // ignore parse errors
    }
    return [...defaultOfficials];
  };

  let officialsData = loadOfficialsData();

  const saveOfficialsData = () => {
    try {
      localStorage.setItem(OFFICIALS_STORAGE_KEY, JSON.stringify(officialsData));
    } catch (error) {
      console.warn('Unable to save officials data:', error);
    }
  };

  const renderOfficialsDirectory = () => {
    if (!officialsDirectory || !officialsEmpty) return;

    // Remove existing cards but keep empty state
    officialsDirectory.querySelectorAll('.official-card-row').forEach(el => el.remove());

    if (officialsData.length === 0) {
      officialsEmpty.style.display = 'block';
      if (officialsCount) officialsCount.textContent = '0';
      return;
    }

    officialsEmpty.style.display = 'none';
    if (officialsCount) officialsCount.textContent = officialsData.length;

    officialsData.forEach((official, index) => {
      const card = document.createElement('div');
      card.className = 'official-card-row' + (index % 2 === 0 ? ' card-row-light' : ' card-row-white');
      card.dataset.id = official.id;

      const photoUrl = official.photo || 'DILG Seal.png';

      card.innerHTML = `
        <!-- Left: Photo + Identity -->
        <div class="card-left">
          <div class="official-photo-col">
            <img src="${photoUrl}" alt="${official.name}" class="official-photo-img" onerror="this.src='DILG Seal.png'" />
          </div>
          <div class="official-identity-col">
            <div class="official-position-badge">${official.position}</div>
            <h3 class="official-name-text">${official.name}</h3>
            <div class="official-meta">
              <span class="meta-item"><span class="meta-label">District:</span> ${official.district}</span>
              <span class="meta-item"><span class="meta-label">Party:</span> ${official.affiliation || 'N/A'}</span>
            </div>
          </div>
        </div>

        <!-- Center: Expandable Details -->
        <div class="card-center">
          <button type="button" class="details-toggle" aria-expanded="false" data-id="${official.id}">
            <span class="toggle-text">View Details</span>
            <span class="toggle-arrow">▾</span>
          </button>
          <div class="details-content" style="display:none;">
            ${official.term ? `<div class="detail-row"><span class="detail-label">Term:</span><span>${official.term}</span></div>` : ''}
            ${official.contact ? `<div class="detail-row"><span class="detail-label">Contact:</span><span>${official.contact}</span></div>` : ''}
            ${official.address ? `<div class="detail-row"><span class="detail-label">Address:</span><span>${official.address}</span></div>` : ''}
            ${official.note ? `<div class="detail-row"><span class="detail-label">Notes:</span><span>${official.note}</span></div>` : ''}
          </div>
        </div>

        <!-- Right: Action Buttons -->
        <div class="card-right">
          <div class="action-buttons-stack">
            ${official.urlFb ? `<a href="${official.urlFb}" target="_blank" rel="noopener noreferrer" class="action-btn action-btn-fb"><span class="action-icon">f</span> Facebook</a>` : ''}
            ${official.urlWeb ? `<a href="${official.urlWeb}" target="_blank" rel="noopener noreferrer" class="action-btn action-btn-web"><span class="action-icon">🌐</span> Website</a>` : ''}
            ${official.urlProfile ? `<a href="${official.urlProfile}" target="_blank" rel="noopener noreferrer" class="action-btn action-btn-profile"><span class="action-icon">👤</span> Profile</a>` : ''}
            <button type="button" class="action-btn action-btn-contact" data-action="contact" data-id="${official.id}"><span class="action-icon">📞</span> Contact</button>
            <button type="button" class="action-btn action-btn-edit" data-action="edit" data-id="${official.id}"><span class="action-icon">✏️</span> Edit</button>
            <button type="button" class="action-btn action-btn-delete" data-action="delete" data-id="${official.id}"><span class="action-icon">🗑️</span> Delete</button>
          </div>
        </div>
      `;

      officialsDirectory.appendChild(card);
    });

    // Attach toggle event listeners
    officialsDirectory.querySelectorAll('.details-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const content = btn.nextElementSibling;
        const isOpen = content.style.display !== 'none';
        content.style.display = isOpen ? 'none' : 'block';
        btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        btn.querySelector('.toggle-arrow').style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
      });
    });

    // Attach card action event listeners via delegation
    handleOfficialsCardActions();
  };

  const handleOfficialsCardActions = () => {
    if (!officialsDirectory) return;

    // Contact button - show contact alert
    officialsDirectory.querySelectorAll('.action-btn-contact').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const id = btn.dataset.id;
        const official = officialsData.find(o => o.id === id);
        if (official && official.contact) {
          alert(`Contact ${official.name}:\n${official.contact}\n${official.address || ''}`);
        } else if (official) {
          alert(`Contact information for ${official.name} is not available.`);
        }
      });
    });
  };

  const openOfficialsModal = (mode, official = null) => {
    if (!officialsModal) return;
    editingOfficialId = mode === 'edit' && official ? official.id : null;
    officialsModalTitle.textContent = mode === 'edit' ? 'Update Official' : 'Add Official';
    if (official) {
      officialNameInput.value = official.name || '';
      officialPositionInput.value = official.position || '';
      officialDistrictInput.value = official.district || '';
      officialAffiliationInput.value = official.affiliation || '';
      officialTermInput.value = official.term || '';
      officialContactInput.value = official.contact || '';
      officialAddressInput.value = official.address || '';
      officialUrlFbInput.value = official.urlFb || '';
      officialUrlWebInput.value = official.urlWeb || '';
      officialUrlProfileInput.value = official.urlProfile || '';
      officialNoteInput.value = official.note || '';
    } else {
      officialsForm.reset();
    }

    officialsModal.classList.add('open');
    officialsModal.setAttribute('aria-hidden', 'false');
    officialNameInput.focus();
  };

  const closeOfficialsModal = () => {
    if (!officialsModal) return;
    officialsModal.classList.remove('open');
    officialsModal.setAttribute('aria-hidden', 'true');
    officialsForm.reset();
    editingOfficialId = null;
  };

  const updateOfficialsData = (event) => {
    event.preventDefault();
    const name = officialNameInput.value.trim();
    const position = officialPositionInput.value.trim();
    const district = officialDistrictInput.value.trim();
    const affiliation = officialAffiliationInput.value.trim();
    const term = officialTermInput.value.trim();
    const contact = officialContactInput.value.trim();
    const address = officialAddressInput.value.trim();
    const urlFb = officialUrlFbInput.value.trim();
    const urlWeb = officialUrlWebInput.value.trim();
    const urlProfile = officialUrlProfileInput.value.trim();
    const note = officialNoteInput.value.trim();

    if (!name || !position || !district) {
      alert('Please complete the name, position, and district fields.');
      return;
    }

    if (editingOfficialId) {
      officialsData = officialsData.map((item) => {
        if (item.id !== editingOfficialId) return item;
        return { ...item, name, position, district, affiliation, term, contact, address, urlFb, urlWeb, urlProfile, note };
      });
    } else {
      const newOfficial = {
        id: Date.now().toString(),
        name, position, district, affiliation, term, contact, address,
        urlFb, urlWeb, urlProfile, note
      };
      officialsData.unshift(newOfficial);
    }

    saveOfficialsData();
    renderOfficialsDirectory();
    closeOfficialsModal();
  };

  const handleOfficialsCardClick = (event) => {
    const button = event.target.closest('.action-btn-edit, .action-btn-delete');
    if (!button) return;
    const action = button.classList.contains('action-btn-edit') ? 'edit' : 'delete';
    const id = button.dataset.id;
    const official = officialsData.find((item) => item.id === id);
    if (action === 'edit' && official) {
      openOfficialsModal('edit', official);
      return;
    }
    if (action === 'delete' && official) {
      const confirmed = confirm(`Delete ${official.name}? This cannot be undone.`);
      if (!confirmed) return;
      officialsData = officialsData.filter((item) => item.id !== id);
      saveOfficialsData();
      renderOfficialsDirectory();
    }
  };

  if (addOfficialBtn) {
    addOfficialBtn.addEventListener('click', () => openOfficialsModal('add'));
  }

  if (officialsModalClose) {
    officialsModalClose.addEventListener('click', closeOfficialsModal);
  }

  if (officialsCancelBtn) {
    officialsCancelBtn.addEventListener('click', closeOfficialsModal);
  }

  if (officialsForm) {
    officialsForm.addEventListener('submit', updateOfficialsData);
  }

  if (officialsModal) {
    officialsModal.addEventListener('click', (event) => {
      if (event.target.classList.contains('modal-backdrop')) {
        closeOfficialsModal();
      }
    });
  }

  if (officialsDirectory) {
    officialsDirectory.addEventListener('click', handleOfficialsCardClick);
  }

  renderOfficialsDirectory();

  const initialActivePageId = document.querySelector('.page.active')?.id || (document.getElementById('page-home') ? 'page-home' : null);
  if (initialActivePageId) {
    showPage(initialActivePageId);
  }

  let newsRevealObserver = null;

  const initNewsRevealAnimations = () => {
    const newsContainer = document.querySelector('#page-news .news-container');
    if (!newsContainer) return;

    if (newsRevealObserver) {
      newsRevealObserver.disconnect();
      newsRevealObserver = null;
    }

    const firstInfoBanner = newsContainer.querySelector('.katon-heading-banner.katon-two-column');
    const revealTargets = Array.from(
      newsContainer.querySelectorAll('.ini-videos, .katon-section, .katon-heading-banner.katon-two-column, .video-card, .katon-card')
    ).filter((element) => {
      if (element.matches('.katon-heading-banner.katon-two-column') && element === firstInfoBanner) {
        return false;
      }
      return !element.closest('.site-footer');
    });

    if (!revealTargets.length) return;

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    revealTargets.forEach((element, index) => {
      element.classList.remove('visible');
      element.classList.add('news-reveal');
      if (element.matches('.video-card, .katon-card')) {
        element.classList.add('news-reveal-staggered');
        const cardIndex = Array.from(element.parentElement.children).indexOf(element);
        const staggerDelay = Math.min(cardIndex * 80, 240);
        element.style.setProperty('--reveal-delay', `${staggerDelay}ms`);
      } else {
        element.classList.add('news-reveal-staggered');
        element.style.setProperty('--reveal-delay', `${Math.min(index * 100, 240)}ms`);
      }
    });

    if (reduceMotion) {
      revealTargets.forEach((element) => element.classList.add('visible'));
      return;
    }

    newsRevealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        } else {
          entry.target.classList.remove('visible');
        }
      });
    }, {
      threshold: 0.16,
      rootMargin: '0px 0px -8% 0px'
    });

    revealTargets.forEach((element) => {
      if (element.getBoundingClientRect().top < window.innerHeight * 0.9) {
        element.classList.add('visible');
        return;
      }
      newsRevealObserver.observe(element);
    });
  };

  initNewsRevealAnimations();
  window.addEventListener('pageshow', initNewsRevealAnimations);

  // Scroll-triggered animations for Mission, Vision, and Core Values
  const observerOptions = {
    threshold: 0.2,
    rootMargin: '0px 0px -100px 0px'
  };

  const animationObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const section = entry.target;
        section.classList.remove('visible');
        void section.offsetWidth;
        section.classList.add('visible');
      }
    });
  }, observerOptions);

  // Observe all mission-vision sections for scroll animations
  document.querySelectorAll('.mission-vision-section').forEach((section) => {
    animationObserver.observe(section);
  });

  window.filterCategory = (category) => {
    document.querySelectorAll('.category-tab').forEach((tab) => {
      tab.classList.toggle('active', tab.dataset.category === category);
    });

    document.querySelectorAll('.doc-row').forEach((row) => {
      if (category === 'All' || row.dataset.category === category) {
        row.style.display = 'flex';
      } else {
        row.style.display = 'none';
      }
    });
  };

  // Attach click listeners to category-tab buttons (safer than inline onclick)
  document.querySelectorAll('.category-tab').forEach((tab) => {
    tab.addEventListener('click', (e) => {
      const category = tab.dataset.category;
      if (category) window.filterCategory(category);
    });
  });

  // Activate the first category tab when page loads so a section is visible immediately.
  const firstTab = document.querySelector('.category-tab');
  if (firstTab) {
    window.filterCategory(firstTab.dataset.category);
  }

  const backToTop = document.querySelector('.back-to-top');
  const toggleBackToTop = () => {
    if (!backToTop) return;
    if (window.scrollY > 240) {
      backToTop.classList.add('visible');
    } else {
      backToTop.classList.remove('visible');
    }
  };

  backToTop?.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  window.addEventListener('scroll', toggleBackToTop);
  toggleBackToTop();

  // Citizen's Charter Accordion Functionality (delegated handler)
  const accordionList = document.querySelector('.accordion-list');
  let currentExpandedItem = null;

  // Initialize ARIA states based on any pre-set data-expanded attributes
  document.querySelectorAll('.accordion-item').forEach(item => {
    const header = item.querySelector('.accordion-header');
    const body = item.querySelector('.accordion-body');
    if (!header) return;
    if (item.getAttribute('data-expanded') === 'true') {
      header.setAttribute('aria-expanded', 'true');
      if (body) { body.classList.add('open'); body.style.maxHeight = body.scrollHeight + 'px'; body.style.display = 'block'; body.style.opacity = '1'; currentExpandedItem = item; }
    } else {
      header.setAttribute('aria-expanded', 'false');
      if (body) { body.classList.remove('open'); body.style.maxHeight = null; body.style.display = null; body.style.opacity = null; }
    }
  });

  if (accordionList) {
    accordionList.addEventListener('click', (e) => {
      const header = e.target.closest('.accordion-header');
      if (!header || !accordionList.contains(header)) return;
      const item = header.closest('.accordion-item');
      const body = item.querySelector('.accordion-body');
      const isExpanded = item.getAttribute('data-expanded') === 'true';

      // Close previously opened item if different
      if (currentExpandedItem && currentExpandedItem !== item) {
        currentExpandedItem.setAttribute('data-expanded', 'false');
        const prevHeader = currentExpandedItem.querySelector('.accordion-header');
        const prevBody = currentExpandedItem.querySelector('.accordion-body');
        if (prevHeader) prevHeader.setAttribute('aria-expanded', 'false');
        if (prevBody) {
          prevBody.style.maxHeight = null;
          prevBody.classList.remove('open');
          prevBody.style.opacity = null;
          prevBody.style.display = null;
        }
        currentExpandedItem = null;
      }

      if (isExpanded) {
        item.setAttribute('data-expanded', 'false');
        header.setAttribute('aria-expanded', 'false');
        if (body) {
          body.style.maxHeight = null;
          body.classList.remove('open');
          body.style.opacity = null;
          body.style.display = null;
        }
        currentExpandedItem = null;
      } else {
        item.setAttribute('data-expanded', 'true');
        header.setAttribute('aria-expanded', 'true');
        if (body) {
          body.classList.add('open');
          body.style.display = 'block';
          // force reflow to ensure transition
          void body.offsetHeight;
          body.style.maxHeight = body.scrollHeight + 'px';
          body.style.opacity = '1';
        }
        currentExpandedItem = item;
        setTimeout(() => header.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
      }
    });
  }
});
