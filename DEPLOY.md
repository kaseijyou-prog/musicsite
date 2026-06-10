# MusicSite 部署文档

> 适用于 CentOS / Ubuntu 服务器 + 宝塔面板（PHPStudy 用户可直接参照）

---

## 一、环境要求

| 组件 | 推荐版本 | 说明 |
|------|---------|------|
| PHP | 7.4 | 需开启 `pdo_mysql`、`mbstring`、`json`、`fileinfo` 扩展 |
| MySQL | 5.7+ / 8.0 | 推荐 8.0 |
| Nginx | 1.18+ | 宝塔面板默认已安装 |
| 操作系统 | CentOS 7+/Ubuntu 20+ | 64 位 |

---

## 二、宝塔面板部署步骤

### 2.1 创建网站

1. 登录宝塔面板 → **网站** → **添加站点**
2. 填写域名（如 `music.example.com`）
3. PHP 版本选择 **7.4**
4. 数据库选择 **MySQL 8.0**
5. 记录下数据库名、用户名、密码

### 2.2 上传代码

进入网站根目录，将项目文件上传到 `www/wwwroot/你的域名/public/` 目录下：

```
www/wwwroot/你的域名/
├── database/
│   └── schema.sql          ← 数据库建表语句
├── public/                  ← 网站根目录（Nginx 指向这里）
│   ├── index.html           ← 前端入口
│   ├── login.html           ← 登录/注册页
│   ├── admin.html           ← 后台管理页
│   ├── api/
│   │   └── index.php        ← API 路由入口
│   ├── assets/
│   │   ├── css/app.css
│   │   ├── js/app.js
│   │   └── images/
│   │       ├── default-avatar.svg
│   │       └── default-cover.svg
│   └── uploads/
│       ├── music/           ← 上传的音频文件
│       └── covers/          ← 上传的封面图片
└── src/                     ← PHP 类（Web 根目录之外更安全）
    ├── Config/database.php
    ├── Controllers/
    │   ├── AuthController.php
    │   ├── SongController.php
    │   └── AdminController.php
    ├── Helpers/
    │   ├── Response.php
    │   └── FileUpload.php
    ├── Middleware/
    │   └── AuthMiddleware.php
    └── Models/
        ├── User.php
        ├── Song.php
        └── Category.php
```

### 2.3 导入数据库

在 phpMyAdmin 或命令行中导入 `database/schema.sql`：

```bash
mysql -u 数据库名 -p 数据库名 < database/schema.sql
```

默认管理员账号：`admin` / `password`

### 2.4 Nginx 配置

在宝塔面板 → 网站 → 设置 → 配置文件中添加：

```nginx
server {
    listen 80;
    server_name music.example.com;
    root /www/wwwroot/你的域名/public;
    index index.html index.php;

    # 静态文件缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|mp3|wav|flac|ogg|m4a|woff2?)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }

    # API 请求
    location /api {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    # PHP 文件
    location ~ \.php(.*)$ {
        fastcgi_pass unix:/tmp/php-cgi-74.sock;
        fastcgi_index index.php;
        fastcgi_split_path_info ^((?U).+\.php)(/?.+)$;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        include fastcgi_params;
    }

    # 前端路由（SPA）
    location / {
        try_files $uri $uri/ /index.html;
    }

    # 禁止访问 src 目录
    location /src {
        deny all;
        return 403;
    }
}
```

### 2.5 PHP 设置

在宝塔面板 → PHP 7.4 → 设置 中：

1. **扩展**：确保已启用 `pdo_mysql`、`mbstring`、`json`、`fileinfo`
2. **上传限制**：`upload_max_filesize` 设为 `60M`（支持上传音频文件）
3. **POST 限制**：`post_max_size` 设为 `70M`
4. **执行时间**：`max_execution_time` 设为 `60`

### 2.6 目录权限

```bash
chmod -R 755 /www/wwwroot/你的域名/public/uploads
chown -R www:www /www/wwwroot/你的域名/public/uploads
```

### 2.7 HTTPS（推荐）

在宝塔面板 → 网站 → 设置 → SSL 中申请 Let's Encrypt 证书，强制 HTTPS 访问。

---

## 三、安全注意事项

1. **生产环境请修改默认管理员密码**（登录后在"我的"→"修改密码"中修改）
2. **`src/` 目录不要放在 Web 根目录内**（防止 PHP 文件被直接访问）
3. **`database/` 目录不要放在 Web 根目录内**（防止 SQL 文件被下载）
4. 建议定期备份数据库和上传目录

---

## 四、目录结构说明

| 目录 | 说明 |
|------|------|
| `public/` | Web 根目录，Nginx 指向这里 |
| `public/api/` | API 入口，所有 `/api/*` 请求路由到这里 |
| `public/assets/` | 静态资源（CSS、JS、图片） |
| `public/uploads/` | 用户上传的文件（音频、封面） |
| `src/` | PHP 类文件（Controller、Model、Middleware 等） |
| `database/` | 数据库建表语句 |
