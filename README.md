# Personal Homepage System

[English](README_EN.md) | 中文

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?logo=tailwind-css&logoColor=white" alt="Tailwind">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
  <img src="https://img.shields.io/badge/Release-v1.2-blue.svg" alt="Release">
</p>

基于 **PHP + JSON** 的轻量级个人主页/博客系统，无需数据库，开箱即用。

## ✨ 核心特性

- **零数据库**：纯 JSON 文件存储，无需 MySQL/SQLite
- **强大后台**：几乎所有前台内容都可以在后台管理和控制
- **日记系统**：富文本编辑、图片上传、标签、草稿/发布、置顶、嵌套评论
- **留言板**：嵌套回复 + 点赞 + Cookie 记忆访客昵称
- **安全加固**：CSRF 防护、原子写入、输入消毒、速率限制、安全 Session
- **数据安全**：一键备份与恢复
- **SEO 优化**：Open Graph、JSON-LD 结构化数据、XML Sitemap
- **响应式设计**：完美适配手机/平板/桌面端

## 🚀 部署方式

### 方式一：传统部署

1. 上传项目到你的主机
2. 确保 `data/` 和 `uploads/` 目录可写
3. 访问 `install.php` 按向导完成安装
4. 开始使用后台管理内容

### 方式二：Docker 部署

```bash
# 克隆项目
git clone https://github.com/stwy0716/Personal-blog.git
cd Personal-blog

# 构建并启动
docker compose up -d

# 访问 http://localhost:8080
```

### 方式三：Nginx + PHP-FPM

参考项目中的 `nginx.conf.example` 配置文件。

> **提示**：首次登录后请立即修改默认密码！

## 📁 目录结构

```
Personal-blog/
├── admin/                  # 后台管理
│   ├── index.php           # 仪表盘
│   ├── settings.php        # 设置中心
│   ├── content.php         # 内容管理
│   ├── diary.php           # 日记管理
│   ├── guestbook.php       # 留言板管理
│   ├── diary_comments.php  # 评论管理
│   ├── music.php           # 音乐管理（曲目开关）
│   ├── friends.php         # 友情链接管理
│   ├── pages.php           # 自定义页面管理
│   ├── images.php          # 图片管理器
│   ├── security.php        # 安全中心
│   ├── logs.php            # 操作日志
│   ├── backup.php          # 数据备份与恢复
│   ├── export.php          # 数据导出
│   └── auth.php            # 登录认证
├── api/                    # API 接口
│   ├── post_comment.php    # 发表评论
│   ├── reply_comment.php   # 回复评论
│   └── like_comment.php    # 点赞评论
├── data/                   # 数据存储（JSON）
├── includes/                # 公共组件
│   ├── header.php          # 页头（导航/OG/JSON-LD）
│   ├── footer.php          # 页脚（音乐播放器/社交链接）
│   ├── security.php        # 安全基础设施
│   ├── mailer.php          # 邮件发送
│   └── toast.php           # 消息提示
├── uploads/                # 上传文件
├── index.php               # 首页
├── about.php               # 关于页
├── diary.php               # 日记列表
├── diary-detail.php        # 日记详情（TOC/代码高亮/上下篇）
├── guestbook.php           # 留言板
├── friends.php             # 友情链接
├── search.php              # 全文搜索
├── tags.php                # 标签云
├── page.php                # 自定义页面
├── sitemap.php             # XML 站点地图
├── rss.php                 # RSS 订阅
├── install.php             # 安装向导
├── Dockerfile              # Docker 构建文件
├── docker-compose.yml      # Docker Compose 配置
└── nginx.conf.example      # Nginx 配置示例
```

## 🎨 功能一览

### 前台

| 功能 | 说明 |
|------|------|
| 美观首页 | 卡片式布局 + 毛玻璃效果 |
| 关于页 | 时间线 + 技能展示 |
| 日记系统 | 搜索、标签云、嵌套评论、代码高亮、目录导航 |
| 留言板 | 发帖、点赞、嵌套回复、访客昵称记忆 |
| 友情链接 | 卡片式展示 |
| 自定义页面 | 支持任意自定义内容页 |
| 阅读体验 | 阅读时长、上下篇导航、进度条、图片懒加载 |
| 深色模式 | 自动跟随系统 + 手动切换 |
| 音乐播放器 | 播放/暂停/音量/进度/随机/循环 |
| 图片灯箱 | 点击放大、ESC 关闭 |
| Markdown 编辑 | 后台支持 Markdown 实时预览与富文本双模式 |
| URL 重写 | 伪静态地址：`/diary/slug`、`/archive`、`/hot` |
| 分类系统 | 前台筛选 + 后台 CRUD 管理 |
| 归档/热门 | 按年月归档、Top 20 热门排行 |
| LaTeX 公式 | 支持行内/块级数学公式（KaTeX） |
| 社交分享 | 复制链接、微博、Twitter 分享 |
| 邮件订阅 | 访客订阅 + 后台批量发送 |
| PWA | 离线缓存、添加到主屏幕 |
| 密码保护 | 单篇日记密码访问 + 定时发布 |

### 后台

| 功能 | 说明 |
|------|------|
| 仪表盘 | 访问统计概览 |
| 设置中心 | 毛玻璃、主题色（6种）、语言、功能开关 |
| 内容管理 | 站点标题、简介、导航、页脚、SEO、社交链接 |
| 日记管理 | 富文本/Markdown 双编辑器、图片位置控制、封面图、分类、密码、定时发布 |
| 音乐管理 | 上传、启用/禁用、批量操作、随机/循环 |
| 分类管理 | 完整 CRUD（名称/别名/描述/排序） |
| 友链管理 | CRUD + 排序 |
| 页面管理 | CRUD + 排序 |
| 图片管理 | 网格预览、复制 URL、删除、分页、自动缩略图 |
| 订阅管理 | 订阅者列表、批量邮件发送 |
| 安全中心 | 修改密码、登录锁定 |
| 数据备份 | 一键备份/恢复（ZIP） |
| 操作日志 | 完整操作记录 |
| 数据导出 | 导出 CSV/JSON |
| 数据图表 | 近 14 日 PV/UV 趋势、Top 5 热门文章（Chart.js） |

## 🔒 安全特性

- **CSRF 防护**：Session Token + hash_equals 时间安全比较
- **原子写入**：临时文件 + flock 文件锁 + rename，防止并发数据损坏
- **输入消毒**：白名单方式过滤危险 HTML 标签和事件处理器
- **文件上传**：finfo 真实 MIME 检测 + 扩展名白名单 + 禁止 SVG
- **速率限制**：评论、上传等操作频率限制
- **Session 安全**：HttpOnly / SameSite / StrictMode
- **CSP 策略**：Content-Security-Policy 限制资源加载
- **访问控制**：敏感目录 .htaccess 保护、uploads 禁止 PHP 执行

## 🛠️ 技术栈

- **后端**：PHP 8.3+
- **前端**：Tailwind CSS（CDN）、原生 JavaScript
- **编辑器**：Summernote（富文本）/ marked.js（Markdown）
- **代码高亮**：PrismJS
- **数学公式**：KaTeX
- **图表**：Chart.js
- **图标**：Font Awesome 6
- **数据存储**：JSON 文件
- **部署**：Apache / Nginx + PHP-FPM / Docker

## 📄 开源协议

本项目基于 [MIT License](LICENSE) 开源。

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📮 安全

如果你发现安全漏洞，请查看 [SECURITY.md](SECURITY.md) 了解报告方式。
