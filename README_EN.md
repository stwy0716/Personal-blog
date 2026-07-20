# Personal Homepage System

English | [中文](README.md)

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?logo=tailwind-css&logoColor=white" alt="Tailwind">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
  <img src="https://img.shields.io/badge/Release-v1.2-blue.svg" alt="Release">
</p>

A lightweight personal homepage/blog system based on **PHP + JSON**. No database required, ready to deploy out of the box.

## ✨ Key Features

- **Zero database**: Pure JSON file storage, no MySQL/SQLite needed
- **Powerful admin panel**: Almost all frontend content manageable from the backend
- **Journal system**: Rich text editor, image upload, tags, draft/publish, pin, nested comments
- **Guestbook**: Nested replies + likes + Cookie-based visitor name memory
- **Security hardened**: CSRF protection, atomic writes, input sanitization, rate limiting, secure sessions
- **Data safety**: One-click backup & restore
- **SEO optimized**: Open Graph, JSON-LD structured data, XML Sitemap
- **Responsive design**: Perfect on mobile, tablet, and desktop

## 🚀 Deployment

### Option 1: Traditional Hosting

1. Upload the project to your web server
2. Ensure `data/` and `uploads/` directories are writable
3. Visit `install.php` and follow the setup wizard
4. Start managing content from the admin panel

### Option 2: Docker

```bash
git clone https://github.com/stwy0716/Personal-blog.git
cd Personal-blog
docker compose up -d
# Visit http://localhost:8080
```

### Option 3: Nginx + PHP-FPM

See `nginx.conf.example` in the project root.

> **Tip**: Change the default password immediately after first login!

## 📁 Directory Structure

```
Personal-blog/
├── admin/                  # Admin panel
│   ├── index.php           # Dashboard
│   ├── settings.php        # Settings center
│   ├── content.php         # Content management
│   ├── diary.php           # Journal management
│   ├── guestbook.php       # Guestbook management
│   ├── diary_comments.php  # Comment management
│   ├── music.php           # Music management (track toggles)
│   ├── friends.php         # Friend links management
│   ├── pages.php           # Custom pages management
│   ├── images.php          # Image manager
│   ├── security.php        # Security center
│   ├── logs.php            # Audit logs
│   ├── backup.php          # Backup & restore
│   ├── export.php          # Data export
│   └── auth.php            # Authentication
├── api/                    # API endpoints
│   ├── post_comment.php    # Post comment
│   ├── reply_comment.php   # Reply to comment
│   └── like_comment.php    # Like comment
├── data/                   # Data storage (JSON files)
├── includes/                # Shared components
│   ├── header.php          # Header (nav/OG/JSON-LD)
│   ├── footer.php          # Footer (music player/social links)
│   ├── security.php        # Security infrastructure
│   ├── mailer.php          # Email sender
│   └── toast.php           # Toast notifications
├── uploads/                # Uploaded files
├── index.php               # Homepage
├── about.php               # About page
├── diary.php               # Journal list
├── diary-detail.php        # Journal detail (TOC/code highlight/prev-next)
├── guestbook.php           # Guestbook
├── friends.php             # Friend links
├── search.php              # Full-text search
├── tags.php                # Tag cloud
├── page.php                # Custom pages
├── sitemap.php             # XML sitemap
├── rss.php                 # RSS feed
├── install.php             # Installation wizard
├── Dockerfile              # Docker build file
├── docker-compose.yml      # Docker Compose config
└── nginx.conf.example      # Nginx config example
```

## 🎨 Feature Overview

### Frontend

| Feature | Description |
|---------|-------------|
| Homepage | Card layout + glassmorphism effect |
| About | Timeline + skills showcase |
| Journal | Search, tag cloud, nested comments, code highlighting, TOC |
| Guestbook | Post, like, nested replies, visitor name memory |
| Friend Links | Card-style display |
| Custom Pages | Arbitrary content pages |
| Reading | Reading time, prev/next nav, progress bar, lazy loading |
| Dark Mode | Auto-follow system preference + manual toggle |
| Music Player | Play/pause/volume/progress/shuffle/loop |
| Image Lightbox | Click to zoom, ESC to close |
| Markdown Editor | Dual-mode: Summernote + marked.js live preview |
| URL Rewrite | Pretty URLs: `/diary/slug`, `/archive`, `/hot` |
| Categories | Frontend filter + backend CRUD management |
| Archive / Hot | Group by year/month, Top 20 ranking |
| LaTeX Math | Inline/block math formulas (KaTeX) |
| Social Share | Copy link, Weibo, Twitter |
| Email Subscribe | Visitor subscription + bulk email sending |
| PWA | Offline cache, add to home screen |
| Password Protection | Per-diary password + scheduled publishing |

### Admin Panel

| Feature | Description |
|---------|-------------|
| Dashboard | Visit statistics overview |
| Settings | Glassmorphism, theme color (6 options), language, feature toggles |
| Content | Site title, intro, nav, footer, SEO, social links |
| Journal | Rich text / Markdown dual editor, image position control, cover image, categories, password, scheduled publish |
| Music | Upload, enable/disable, bulk actions, shuffle/loop |
| Categories | Full CRUD (name/slug/description/sort order) |
| Friend Links | CRUD + sort order |
| Pages | CRUD + sort order |
| Images | Grid preview, copy URL, delete, pagination, auto thumbnails |
| Subscribers | Subscriber list, bulk email sending |
| Security | Change password, login lockout |
| Backup | One-click backup/restore (ZIP) |
| Logs | Complete operation history |
| Export | Export CSV/JSON |
| Charts | 14-day PV/UV trends, Top 5 popular articles (Chart.js) |

## 🔒 Security Features

- **CSRF Protection**: Session token + hash_equals timing-safe comparison
- **Atomic Writes**: Temp file + flock + rename to prevent concurrent data corruption
- **Input Sanitization**: Whitelist-based filtering of dangerous HTML tags and event handlers
- **File Upload**: finfo real MIME detection + extension whitelist + SVG blocked
- **Rate Limiting**: Frequency limits on comments, uploads, etc.
- **Session Security**: HttpOnly / SameSite / StrictMode
- **CSP Policy**: Content-Security-Policy to restrict resource loading
- **Access Control**: Sensitive directory .htaccess protection, PHP execution blocked in uploads

## 🛠️ Tech Stack

- **Backend**: PHP 8.3+
- **Frontend**: Tailwind CSS (CDN), vanilla JavaScript
- **Editor**: Summernote (rich text) / marked.js (Markdown)
- **Code Highlighting**: PrismJS
- **Math**: KaTeX
- **Charts**: Chart.js
- **Icons**: Font Awesome 6
- **Storage**: JSON files
- **Deployment**: Apache / Nginx + PHP-FPM / Docker

## 📄 License

This project is licensed under the [MIT License](LICENSE).

## 🤝 Contributing

Issues and Pull Requests are welcome!

## 📮 Security

If you discover a security vulnerability, please see [SECURITY.md](SECURITY.md) for reporting guidelines.
