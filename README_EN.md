# Personal Blog System

**English** | [中文](./README.md)

A lightweight personal blog/homepage system based on **PHP + JSON**, no database required.

---

## Key Features

- **No Database**: Just PHP, no MySQL needed, JSON file storage
- **Powerful Admin Panel**: Almost all frontend content can be managed from the backend
- **Journal System**: Rich text editing, image upload, tags, nested comments, draft/publish, pinning
- **Guestbook**: Nested replies + like system
- **Security**: CSRF protection, password hashing, login lockout, audit logs, security headers, file upload validation
- **Data Safety**: One-click backup/restore (excludes sensitive data)
- **Multilingual**: Chinese/English bilingual support, switchable from admin panel
- **Theme Customization**: 6 theme colors, glassmorphism effect (adjustable blur and opacity)
- **Mobile Friendly**: Responsive design + dark mode

## Features

### Frontend

- Beautiful homepage + responsive design + dark/light theme toggle
- About Me (timeline + skills showcase)
- Journal (search, tag cloud, table of contents, code highlighting, lazy loading / click to zoom images)
- Guestbook (post, like, nested replies)
- RSS Feed
- Custom 404 / 403 / 500 error pages

### Admin Panel

- Dashboard (statistics + 7-day PV/UV chart + quick access)
- Content Management (site info, SEO, homepage cards, blog intro, about, skills, contact, social links, footer)
- Journal Management (rich text editor + image upload + draft/publish + pinning)
- Guestbook Management (view + delete + export CSV)
- Comment Management (edit + show/hide + delete)
- Music Management (upload + preview + set active track)
- Audit Logs (search + export CSV)
- Data Backup & Restore (auto-excludes password hashes, with Zip Slip protection)
- Settings Center (UI appearance, theme color, language, feature toggles)
- Security Center (change password, requires 8+ chars with letters and numbers)

## Security Features

| Feature | Description |
|---------|-------------|
| CSRF Protection | Token verification on all POST forms and APIs |
| XSS Protection | HTML sanitization (whitelist filtering), CSP headers, output escaping |
| File Upload Security | finfo real type detection, extension whitelist, SVG blocked, rate limiting |
| Auth Security | bcrypt hashing, login lockout, session timeout, secure cookies |
| Data Security | Atomic JSON writes (file lock + temp file rename), backup excludes sensitive data |
| Rate Limiting | Rate limits on posting, replying, liking, and uploading |
| Directory Protection | data/backups/uploads directories blocked from direct access, PHP execution disabled in uploads |

## Deployment

1. Upload the project to your hosting server
2. Ensure `data/` and `uploads/` directories are writable (permission 755)
3. Visit `admin/auth.php` to login (default password: `admin123`)
4. Change password and edit content in the admin panel

> **Important**: Change the default password immediately after first login in "Security Center".

## Directory Structure

```
personal-homepage/
├── admin/                  # Admin panel
│   ├── index.php           # Dashboard
│   ├── content.php         # Content management
│   ├── diary.php           # Journal management
│   ├── guestbook.php       # Guestbook management
│   ├── diary_comments.php  # Comment management
│   ├── music.php           # Music management
│   ├── settings.php        # Settings center
│   ├── security.php        # Security center
│   ├── logs.php            # Audit logs
│   ├── backup.php          # Data backup
│   ├── export.php          # Data export
│   ├── upload.php          # File upload
│   └── auth.php            # Authentication
├── api/                    # API endpoints
│   ├── post_comment.php    # Post message
│   ├── reply_comment.php   # Reply to message
│   └── like_comment.php    # Like message
├── includes/               # Shared components
│   ├── security.php        # Security infrastructure
│   ├── header.php          # Page header
│   ├── footer.php          # Page footer
│   ├── mailer.php          # Email notifications
│   └── toast.php           # Toast component
├── assets/                 # Static assets
│   ├── css/enhancements.css
│   └── js/enhancements.js
├── data/                   # Data storage (JSON)
├── uploads/                # Uploaded files
├── backups/                # Backup files
├── index.php               # Homepage
├── about.php               # About me
├── diary.php               # Journal list
├── diary-detail.php        # Journal detail
├── guestbook.php           # Guestbook
├── tags.php                # Tag cloud
├── search.php              # Search
├── rss.php                 # RSS feed
├── test_security.php       # Security test page
├── 403.php / 404.php / 500.php
└── .htaccess               # Apache config
```

## Tech Stack

- PHP 8+
- Tailwind CSS (CDN)
- Summernote Rich Text Editor
- Prism.js Code Highlighting
- JSON File Storage

## License

MIT License
