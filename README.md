# 个人主页博客系统

[English](./README_EN.md) | **中文**

一个基于 **PHP + JSON** 的轻量级个人主页/博客系统，无需数据库。

---

## 核心特性

- **零数据库部署**：只需 PHP，无需 MySQL，JSON 文件存储
- **强大后台管理**：几乎所有前台内容均可在后台配置
- **日记系统**：富文本编辑、图片上传、标签、嵌套评论、草稿/发布、置顶
- **留言板**：嵌套回复 + 点赞系统
- **安全防护**：CSRF 防护、密码哈希、登录锁定、审计日志、安全响应头、文件上传校验
- **数据安全**：一键备份/恢复（排除敏感数据）
- **多语言**：中英文双语支持，后台一键切换
- **主题定制**：6 种主题色、毛玻璃效果（可调节模糊度和透明度）
- **移动端适配**：响应式设计 + 深色模式

## 功能一览

### 前台

- 精美首页 + 响应式设计 + 深色/浅色主题切换
- 关于我（时间线 + 技能展示）
- 日记（搜索、标签云、文章目录 TOC、代码高亮、图片懒加载/点击放大）
- 留言板（留言、点赞、嵌套回复）
- RSS 订阅
- 自定义 404 / 403 / 500 错误页面

### 后台管理

- 仪表盘（统计数据 + 近 7 天 PV/UV 图表 + 快捷入口）
- 内容管理（站点信息、SEO、首页卡片、博客简介、关于我、技能、联系方式、社交链接、页脚）
- 日记管理（富文本编辑器 + 图片上传 + 草稿/发布 + 置顶）
- 留言板管理（查看 + 删除 + 导出 CSV）
- 评论管理（编辑 + 显示/隐藏 + 删除）
- 音乐管理（上传 + 在线试听 + 设为活跃曲目）
- 操作日志（搜索 + 导出 CSV）
- 数据备份与恢复（自动排除密码哈希，含 Zip Slip 防护）
- 设置中心（UI 外观、主题色、语言、功能开关）
- 安全中心（修改密码，要求 8 位以上含字母和数字）

## 安全特性

| 特性 | 说明 |
|------|------|
| CSRF 防护 | 所有 POST 表单和 API 均有 Token 验证 |
| XSS 防护 | HTML 消毒（白名单过滤）、CSP 头、输出转义 |
| 文件上传安全 | finfo 真实类型检测、白名单扩展名、禁止 SVG、速率限制 |
| 认证安全 | bcrypt 哈希、登录失败锁定、Session 超时、安全 Cookie |
| 数据安全 | JSON 原子写入（文件锁 + 临时文件 rename）、备份排除敏感数据 |
| 速率限制 | 留言/回复/点赞/上传均有频率限制 |
| 目录保护 | data/backups/uploads 目录禁止直接访问，uploads 禁止执行 PHP |

## 部署方式

1. 将项目上传到服务器
2. 确保 `data/` 和 `uploads/` 目录可写（权限 755）
3. 访问 `admin/auth.php` 登录后台（默认密码：`admin123`）
4. 在后台修改密码和编辑内容

> **重要提示**：首次登录后请立即在「安全中心」修改默认密码。

## 目录结构

```
personal-homepage/
├── admin/                  # 后台管理
│   ├── index.php           # 仪表盘
│   ├── content.php         # 内容管理
│   ├── diary.php           # 日记管理
│   ├── guestbook.php       # 留言板管理
│   ├── diary_comments.php  # 评论管理
│   ├── music.php           # 音乐管理
│   ├── settings.php        # 设置中心
│   ├── security.php        # 安全中心
│   ├── logs.php            # 操作日志
│   ├── backup.php          # 数据备份
│   ├── export.php          # 数据导出
│   ├── upload.php          # 文件上传
│   └── auth.php            # 认证系统
├── api/                    # API 接口
│   ├── post_comment.php    # 发布留言
│   ├── reply_comment.php   # 回复留言
│   └── like_comment.php    # 点赞
├── includes/               # 公共组件
│   ├── security.php        # 安全基础设施
│   ├── header.php          # 页面头部
│   ├── footer.php          # 页面底部
│   ├── mailer.php          # 邮件通知
│   └── toast.php           # 提示组件
├── assets/                 # 静态资源
│   ├── css/enhancements.css
│   └── js/enhancements.js
├── data/                   # 数据存储（JSON）
├── uploads/                # 上传文件
├── backups/                # 备份文件
├── index.php               # 首页
├── about.php               # 关于我
├── diary.php               # 日记列表
├── diary-detail.php        # 日记详情
├── guestbook.php           # 留言板
├── tags.php                # 标签云
├── search.php              # 搜索
├── rss.php                 # RSS 订阅
├── test_security.php       # 安全测试页
├── 403.php / 404.php / 500.php
└── .htaccess               # Apache 配置
```

## 技术栈

- PHP 8+
- Tailwind CSS（CDN）
- Summernote 富文本编辑器
- Prism.js 代码高亮
- JSON 文件存储

## 许可证

MIT License
