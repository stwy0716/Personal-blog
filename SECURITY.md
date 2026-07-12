# 安全政策

## 报告安全漏洞

我们非常重视项目的安全问题。如果你发现安全漏洞，请按照以下流程报告。

### 报告方式

**请不要在 GitHub Issues 中公开报告安全漏洞。**

请通过以下方式私密报告：

1. 在 GitHub 上 [提交 Security Advisory](https://github.com/stwy0716/Personal-blog/security/advisories/new)
2. 或发送邮件至：stwy0716@users.noreply.github.com

### 报告内容

请在报告中包含以下信息：

- 漏洞的类型（如 XSS、CSRF、文件上传、SQL 注入等）
- 漏洞所在的具体文件和代码行
- 复现步骤（详细描述如何触发该漏洞）
- 漏洞的影响范围和严重程度
- 如果有修复建议，请一并附上

### 响应时间

- **确认收到**：3 个工作日内
- **初步评估**：7 个工作日内
- **修复发布**：根据严重程度，通常在 14 个工作日内

## 安全措施

本项目已实现以下安全防护：

- CSRF 防护（所有表单和 API）
- XSS 防护（HTML 消毒 + CSP 头）
- 文件上传安全（真实类型检测 + 白名单）
- bcrypt 密码哈希 + 登录锁定
- JSON 原子写入（文件锁防并发损坏）
- 速率限制（防滥用）
- 安全响应头（CSP、X-Frame-Options 等）
- 目录访问控制（data/backups/uploads 保护）

## 安全配置建议

部署时建议采取以下措施：

1. **首次登录后立即修改默认密码**（后台 > 安全中心）
2. **如使用 HTTPS**，取消 `includes/security.php` 中 `session.cookie_secure` 的注释
3. **设置正确的文件权限**：目录 755，文件 644
4. **定期备份数据**（后台 > 数据备份）
5. **生产环境关闭 PHP 错误显示**：`display_errors = Off`
