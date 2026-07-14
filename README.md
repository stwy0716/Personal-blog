# Personal Homepage System

A lightweight personal blog/homepage system based on **PHP + JSON**, no database required.

## Key Features

- **Easy deployment**: No MySQL needed, just PHP
- **Powerful admin panel**: Almost all frontend content can be managed from the backend
- **Journal system**: Rich text, image upload, tags, nested comments
- **Guestbook**: Nested replies + like system
- **Security**: Password hash, login lockout, audit logs, password change
- **Data safety**: One-click backup + restore
- **Mobile friendly**: Responsive design

## Features

### Frontend
- Beautiful homepage + responsive design + dark/light theme
- About me (timeline + skills)
- Journal (search, tags, nested comments)
- Guestbook (post, like, nested replies)
- Custom 404 / 403 / 500 error pages

### Admin Panel
- Unified content management (title, intro, nav, footer, theme, error pages)
- Journal management (rich text editor + image upload)
- Guestbook management (review + delete + export)
- Journal comment management (view + delete)
- Audit logs
- Data backup & restore
- Change admin password

## Deployment

1. Upload the project to your hosting server
2. Ensure `data/` directory is writable
3. Visit `admin/content.php` to login (default password: `admin123`)
4. Start editing content in the admin panel

**Tip**: Change the default password immediately after first login.

## Directory Structure

```
personal-homepage/
├── admin/              # Admin panel
│   ├── index.php       # Dashboard
│   ├── content.php     # Content editor
│   ├── diary.php       # Journal management
│   ├── guestbook.php   # Guestbook management
│   ├── diary_comments.php  # Comment management
│   ├── security.php    # Security center
│   ├── logs.php        # Audit logs
│   ├── backup.php      # Data backup & restore
│   └── auth.php        # Authentication
├── api/                # API endpoints
├── data/               # Data storage (JSON files)
├── includes/           # Shared components
├── uploads/            # Uploaded images
├── diary.php           # Journal list
├── diary-detail.php    # Journal detail
├── guestbook.php       # Guestbook
├── 404.php / 403.php / 500.php
└── index.php           # Homepage
```

## Tech Stack

- PHP 8+
- Tailwind CSS (CDN)
- Quill.js (Rich text editor)
- JSON file storage