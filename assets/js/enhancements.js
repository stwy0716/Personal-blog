/**
 * Personal Homepage Enhancement Scripts
 * - Image lightbox
 * - TOC scroll highlighting
 * - Search keyword highlighting
 * - Smooth scroll to anchor
 */

// ==================== Image Lightbox ====================
(function () {
    let lightbox = null;
    let lightboxImg = null;

    function createLightbox() {
        if (lightbox) return;
        lightbox = document.createElement('div');
        lightbox.className = 'lightbox-overlay';
        lightbox.setAttribute('role', 'dialog');
        lightbox.setAttribute('aria-modal', 'true');

        lightboxImg = document.createElement('img');
        lightboxImg.alt = '';
        lightbox.appendChild(lightboxImg);

        const closeBtn = document.createElement('button');
        closeBtn.className = 'lightbox-close';
        closeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        closeBtn.setAttribute('aria-label', 'Close');
        lightbox.appendChild(closeBtn);

        document.body.appendChild(lightbox);

        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox || e.target.closest('.lightbox-close')) {
                closeLightbox();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeLightbox();
        });
    }

    function openLightbox(src) {
        createLightbox();
        lightboxImg.src = src;
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            lightbox.classList.add('active');
        });
    }

    function closeLightbox() {
        if (!lightbox) return;
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function (e) {
        const img = e.target.closest('img');
        if (!img) return;
        // Skip nav/header/player images
        if (img.closest('nav') || img.closest('header') || img.closest('#music-player-container')) return;
        if (img.src) {
            e.preventDefault();
            openLightbox(img.src);
        }
    });
})();

// ==================== TOC Scroll Highlight ====================
(function () {
    function initTocHighlight() {
        const tocContainer = document.querySelector('.toc-container');
        if (!tocContainer) return;

        const headings = document.querySelectorAll('article h2, article h3, .diary-content h2, .diary-content h3, .content h2, .content h3');
        if (!headings.length) return;

        const tocLinks = tocContainer.querySelectorAll('a');
        if (!tocLinks.length) return;

        const observer = new IntersectionObserver(
            function (entries) {
                let visible = [];
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        visible.push(entry.target);
                    }
                });
                if (visible.length) {
                    const topHeading = visible.sort(function (a, b) {
                        return a.getBoundingClientRect().top - b.getBoundingClientRect().top;
                    })[0];
                    const id = topHeading.id;
                    tocLinks.forEach(function (link) {
                        link.classList.toggle('active', link.getAttribute('href') === '#' + id);
                    });
                }
            },
            { rootMargin: '-80px 0px -60% 0px', threshold: 0 }
        );

        headings.forEach(function (h) {
            if (!h.id) {
                h.id = h.textContent.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
            }
            observer.observe(h);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTocHighlight);
    } else {
        initTocHighlight();
    }
})();

// ==================== Search Keyword Highlight ====================
function highlightSearchKeywords(selector, keyword, options) {
    options = options || {};
    const caseSensitive = options.caseSensitive || false;
    const className = options.className || 'search-highlight';

    const container = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!container || !keyword) return 0;

    const flags = caseSensitive ? 'g' : 'gi';
    const regex = new RegExp('(' + keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', flags);

    function walk(node) {
        let count = 0;
        if (node.nodeType === Node.TEXT_NODE) {
            if (node.parentNode && node.parentNode.classList && node.parentNode.classList.contains(className)) return 0;
            const text = node.textContent;
            if (regex.test(text)) {
                regex.lastIndex = 0;
                const frag = document.createDocumentFragment();
                let lastIndex = 0;
                let match;
                while ((match = regex.exec(text)) !== null) {
                    if (match.index > lastIndex) {
                        frag.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
                    }
                    const mark = document.createElement('mark');
                    mark.className = className;
                    mark.textContent = match[0];
                    frag.appendChild(mark);
                    count++;
                    lastIndex = regex.lastIndex;
                }
                if (lastIndex < text.length) {
                    frag.appendChild(document.createTextNode(text.slice(lastIndex)));
                }
                node.parentNode.replaceChild(frag, node);
            }
        } else if (node.nodeType === Node.ELEMENT_NODE && !['SCRIPT', 'STYLE', 'PRE', 'CODE', 'MARK'].includes(node.tagName)) {
            for (let i = 0; i < node.childNodes.length; i++) {
                count += walk(node.childNodes[i]);
            }
        }
        return count;
    }

    return walk(container);
}

function clearHighlights(selector, className) {
    className = className || 'search-highlight';
    const container = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!container) return;

    const marks = container.querySelectorAll('mark.' + className);
    marks.forEach(function (mark) {
        const parent = mark.parentNode;
        parent.insertBefore(document.createTextNode(mark.textContent), mark);
        parent.removeChild(mark);
        parent.normalize();
    });
}

// ==================== Smooth Scroll to Anchor ====================
(function () {
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href^="#"]');
        if (!link) return;
        const targetId = link.getAttribute('href').slice(1);
        if (!targetId) return;
        const target = document.getElementById(targetId);
        if (target) {
            e.preventDefault();
            const offset = 80;
            const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top: top, behavior: 'smooth' });
        }
    });
})();
