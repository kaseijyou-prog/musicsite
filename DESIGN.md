# MusicSite 音乐网站设计文档
> **最后更新**: 2026-06-09
> **技术栈**: PHP 7.4.3 + MySQL 8.0.12 + Nginx 1.15.11 (PHPStudy)
> **风格**: 仿汽水音乐，暗色主题，移动端优先

---

## 一、项目概述

### 1.1 目标
仿照「汽水音乐」风格的单页音乐网站，支持在线播放、歌词同步、收藏/历史、后台上传、用户管理。

### 1.2 技术选型
| 组件 | 选型 | 说明 |
|------|------|------|
| 后端语言 | PHP 7.4.3nts | PHPStudy 内置 |
| 数据库 | MySQL 8.0.12 | PHPStudy 内置 |
| Web 服务器 | Nginx 1.15.11 | PHPStudy 内置，端口 80 |
| PHP-FPM | 127.0.0.1:9001 | Nginx 通过 fastcgi 转发 |
| 前端 | 纯 HTML + CSS + JS | 无框架，原生实现 |
| 认证 | PHP Session | 服务端 Session，前端不存储凭证 |
| 音频存储 | 本地 uploads/ | 音频和封面图 |

### 1.3 环境路径
- **项目目录**: `C:\Users\chang\Documents\musicsite\`
- **网站根目录**: `D:\phpstudy_pro\WWW\musicsite\public\`（Nginx root）
- **PHP**: `D:\phpstudy_pro\Extensions\php\php7.4.3nts\php-cgi.exe`
- **MySQL**: `D:\phpstudy_pro\Extensions\MySQL8.0.12\`
- **Nginx**: `D:\phpstudy_pro\Extensions\Nginx1.15.11\`
- **访问地址**: `http://localhost/musicsite/public/`

---

## 二、目录结构

```
C:\Users\chang\Documents\musicsite\
├── DESIGN.md                      ← 本文档
├── database/
│   └── schema.sql                  # 数据库建表语句
├── public/                         # Web 根目录（Nginx 指向这里）
│   ├── index.php                   # 前端 SPA 入口（发现页 + 我的页）
│   ├── login.php                   # 独立登录/注册页
│   ├── admin.php                   # 后台管理页
│   ├── api/
│   │   └── index.php               # API 路由入口
│   ├── assets/
│   │   ├── css/app.css             # 全局样式
│   │   ├── js/app.js               # 前端主逻辑
│   │   ├── js/auth.js              # 登录/注册逻辑
│   │   ├── js/admin.js             # 后台管理逻辑
│   │   └── images/
│   │       ├── default-avatar.svg  # 默认头像
│   │       └── default-cover.svg   # 默认封面
│   └── uploads/
│       ├── music/                  # 音频文件
│       └── covers/                 # 封面图片
├── src/
│   ├── Config/
│   │   └── database.php            # PDO 单例
│   ├── Controllers/
│   │   ├── AuthController.php      # 认证
│   │   ├── SongController.php      # 歌曲/收藏/历史
│   │   └── AdminController.php     # 后台管理
│   ├── Helpers/
│   │   ├── Response.php            # JSON 响应工具
│   │   └── FileUpload.php          # 文件上传工具
│   ├── Middleware/
│   │   └── AuthMiddleware.php      # 认证中间件
│   └── Models/
│       ├── User.php                # 用户模型
│       ├── Song.php                # 歌曲模型
│       └── Category.php            # 分类模型
└── audio/                          # 测试用音频文件
```

---

## 三、Nginx 配置

修改 `D:\phpstudy_pro\Extensions\Nginx1.15.11\conf\vhosts\0localhost_80.conf`：

```nginx
server {
    listen        80;
    server_name  localhost;
    root          "D:/phpstudy_pro/WWW/musicsite/public";
    index         index.php index.html;

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|mp3|wav|flac|ogg|m4a)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
    }

    location /api {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    location ~ \.php(.*)$ {
        fastcgi_pass   127.0.0.1:9001;
        fastcgi_index  index.php;
        fastcgi_split_path_info  ^((?U).+\.php)(/?.+)$;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        fastcgi_param  PATH_INFO  $fastcgi_path_info;
        fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
        include        fastcgi_params;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

---

## 四、数据库设计（MySQL 8.0.12）

### 4.1 用户表 `users`
```sql
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    `password_hash` VARCHAR(255) NOT NULL COMMENT '密码哈希',
    `nickname` VARCHAR(50) DEFAULT NULL COMMENT '昵称',
    `avatar` VARCHAR(255) DEFAULT '/assets/images/default-avatar.svg' COMMENT '头像路径',
    `role` ENUM('user','admin') NOT NULL DEFAULT 'user' COMMENT '角色',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1正常 0禁用',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';
```

### 4.2 分类表 `categories`
```sql
CREATE TABLE `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE COMMENT '分类名称',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '排序',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='歌曲分类表';
```

### 4.3 歌曲表 `songs`
```sql
CREATE TABLE `songs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL COMMENT '歌曲名',
    `artist` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '艺术家',
    `album` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '专辑',
    `cover_path` VARCHAR(255) DEFAULT '/assets/images/default-cover.svg' COMMENT '封面图',
    `file_path` VARCHAR(255) NOT NULL COMMENT '音频文件路径',
    `duration` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '时长(秒)',
    `lyrics` TEXT DEFAULT NULL COMMENT 'LRC歌词',
    `play_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '播放次数',
    `category_id` INT UNSIGNED DEFAULT NULL COMMENT '分类ID',
    `uploaded_by` INT UNSIGNED NOT NULL COMMENT '上传者',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1正常 0隐藏',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_category` (`category_id`),
    INDEX `idx_play_count` (`play_count` DESC),
    INDEX `idx_created` (`created_at` DESC),
    FULLTEXT KEY `ft_search` (`title`, `artist`, `album`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='歌曲表';
```

### 4.4 收藏表 `favorites`
```sql
CREATE TABLE `favorites` (
    `user_id` INT UNSIGNED NOT NULL,
    `song_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `song_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户收藏表';
```

### 4.5 播放历史表 `play_history`
```sql
CREATE TABLE `play_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `song_id` INT UNSIGNED NOT NULL,
    `played_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`song_id`) REFERENCES `songs`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_time` (`user_id`, `played_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='播放历史表';
```

### 4.6 默认数据
```sql
INSERT INTO `users` (`username`, `password_hash`, `nickname`, `role`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理员', 'admin');

INSERT INTO `categories` (`name`, `sort_order`) VALUES
('流行音乐', 1), ('摇滚', 2), ('民谣', 3), ('电子', 4),
('说唱', 5), ('轻音乐', 6), ('经典', 7), ('其他', 8);
```
---

## 五、API 接口设计

### 5.1 认证相关
| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| POST | api/auth/register | 注册（用户名2-50字符，密码>=6位） | 无 |
| POST | api/auth/login | 登录 | 无 |
| POST | api/auth/logout | 退出登录 | 需登录 |
| GET | api/auth/me | 获取当前用户信息 | 需登录 |
| PUT | api/auth/profile | 更新昵称 | 需登录 |
| PUT | api/auth/password | 修改密码 | 需登录 |

### 5.2 歌曲相关
| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | api/songs | 歌曲列表（分页/搜索/分类筛选） | 可选 |
| GET | api/songs/hot | 热门歌曲 | 无 |
| GET | api/songs/latest | 最新上架歌曲 | 无 |
| GET | api/songs/{id} | 歌曲详情 | 无 |
| GET | api/songs/{id}/play | 播放（计数+1，记录历史） | 可选 |
| GET | api/categories | 分类列表 | 无 |

### 5.3 收藏/历史
| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| POST | api/favorite/{id} | 收藏/取消收藏 | 需登录 |
| GET | api/favorites | 我的收藏列表 | 需登录 |
| GET | api/history | 播放历史列表 | 需登录 |

### 5.4 后台管理
| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | api/admin/stats | 统计（歌曲/用户/播放） | 需管理员 |
| GET | api/admin/songs | 歌曲管理列表 | 需管理员 |
| POST | api/admin/songs/upload | 上传歌曲 | 需管理员 |
| PUT | api/admin/songs/{id} | 更新歌曲 | 需管理员 |
| DELETE | api/admin/songs/{id} | 删除歌曲 | 需管理员 |
| GET | api/admin/users | 用户列表 | 需管理员 |
| PUT | api/admin/users/{id}/toggle | 启用/禁用用户 | 需管理员 |
| POST | api/admin/categories | 创建分类 | 需管理员 |
| PUT | api/admin/categories/{id} | 更新分类 | 需管理员 |
| DELETE | api/admin/categories/{id} | 删除分类 | 需管理员 |

### 5.5 统一响应格式
```json
{
    "code": 0,
    "message": "ok",
    "data": { ... }
}
```
- `code: 0` = 成功，非 0 = 错误（值同 HTTP 状态码）

---

## 六、前端页面设计

### 6.1 设计风格
- **仿汽水音乐**: 暗色主题（深蓝黑底 #0f0f1a）、紫色强调色（#6c5ce7）
- **移动端优先**: 最大宽度 480px 居中；桌面端（>480px）内容区限制 480px
- **黑胶唱片动画**: 全屏播放器唱片旋转（播放 8s/圈，暂停减速）
- **圆角卡片**: 8px 圆角，半透明背景，毛玻璃效果
- **图标**: 使用内联 SVG 图标（不依赖外部 CDN）

### 6.2 页面总览
| 页面 | 文件 | 说明 |
|------|------|------|
| 登录/注册页 | login.php | 独立页面 |
| 发现页 | index.php | SPA 首页（搜索+分类+热门+最新） |
| 我的页 | index.php | SPA 内页（用户信息+收藏/历史/退出/后台入口） |
| 全屏播放器 | index.php | 从迷你播放器展开 |
| 后台管理 | admin.php | 独立页面（仪表盘+歌曲/用户/分类管理） |

### 6.3 登录/注册页（login.php）
- 全屏渐变背景（#0f0f1a -> #1a1a2e）
- 居中卡片：Logo + 登录/注册 Tab 切换
- 表单：用户名 + 密码 + 确认密码（注册时显示）
- 提交方式：fetch POST /api/auth/login（不刷新页面）
- 成功 -> location.href = '/index.php'
- 失败 -> 红色提示（不跳页）

### 6.4 发现页（index.php - 首页）
- **顶部导航**: Logo + 搜索框 + 用户头像
- **搜索**: 实时搜索，500ms 防抖
- **分类标签**: 横向滚动，点击筛选
- **热门推荐**: 按播放量排序
- **最新上架**: 按上传时间排序
- **迷你播放器**: 底部固定栏（封面+歌名+播放/暂停+下一首+进度条）

### 6.5 我的页（index.php - 内页）
- **用户信息区**: 头像 + 昵称 + 角色标签
- **菜单**: 我的收藏 / 播放历史 / 后台管理（仅管理员） / 退出登录

### 6.6 全屏播放器
- 全屏覆盖，背景渐变+封面模糊
- 顶部：返回 + 歌曲名 + 艺术家
- 中部：黑胶唱片（CSS 旋转动画）
- 歌词区：LRC 滚动高亮
- 进度条：可点击/拖动
- 底部：播放模式 + 上一首 + 播放/暂停 + 下一首 + 收藏

### 6.7 后台管理页（admin.php）
- **左侧导航**: 仪表盘 / 歌曲管理 / 用户管理 / 分类管理
- **仪表盘**: 4 个统计卡片 + 最近上传
- **歌曲管理**: 搜索 + 列表 + 上传弹窗
- **用户管理**: 搜索 + 列表 + 启用/禁用
- **分类管理**: 列表 + 新建弹窗

### 6.8 认证流程
1. 访问 index.php -> JS 检查 /api/auth/me -> 失败跳转 login.php
2. 登录 -> POST /api/auth/login -> 成功跳转 index.php
3. 退出 -> POST /api/auth/logout -> 跳转 login.php
4. admin.php 未登录或未管理员跳转登录页

### 6.9 播放器逻辑
- 点击歌曲 -> new Audio(url).play()
- 播放模式：顺序 / 随机 / 单曲循环
- 歌词同步：LRC 解析，currentTime 匹配高亮
- 播放历史：POST /api/songs/{id}/play（已登录）
- 收藏：POST /api/favorite/{id}（已登录）

### 6.10 响应式适配
- 移动端（<=480px）: 全屏布局
- 桌面端（>480px）: max-width 480px，margin auto 居中
- font-size 使用 rem，不随视口缩放

---

## 七、安全设计

- PHP Session 认证
- password_hash() + password_verify()（bcrypt）
- PDO 预处理语句防 SQL 注入
- 文件上传验证 MIME + 扩展名 + 大小
- **绝对不在 URL 中传递密码**
- API 不返回 password_hash

---

## 八、已知问题与待修复项

### 8.1 PHP 兼容性
- `FileUpload.php` 使用了 `match`（PHP 8.0+），需改为 `switch`

### 8.2 前端编码
- 之前 PowerShell Out-File 写入的中文全部乱码
- 所有 .html 和 .js 需以 UTF-8 重新写入

### 8.3 测试音频
- `C:\Users\chang\Documents\musicsite\audio\数字行者1.mp3`（约 5.8MB）

---

## 九、实施步骤

| # | 内容 | 状态 |
|---|------|------|
| 1 | 配置 Nginx 虚拟主机 | 待执行 |
| 2 | 创建数据库，导入 schema.sql | 待执行 |
| 3 | 修复 FileUpload.php | 待执行 |
| 4 | 重写 index.php | 待执行 |
| 5 | 重写 login.php | 待执行 |
| 6 | 重写 admin.php | 待执行 |
| 7 | 重写 app.js | 待执行 |
| 8 | 重写 auth.js | 待执行 |
| 9 | 重写 admin.js | 待执行 |
| 10 | 重写 api/index.php | 待执行 |
| 11 | 上传测试音频 | 待执行 |
| 12 | 端到端测试 | 待执行 |

---

## 十、用户参与度

| 步骤 | 用户需要做什么 |
|------|-------------|
| 安装 PHPStudy | 已完成 |
| 启动服务 | 一键启动 Nginx + PHP + MySQL |
| 创建数据库 | 首次访问自动导入 |
| 日常使用 | 浏览器访问 |
| 上传音乐 | 后台管理页面上传 |
| 注册用户 | 登录页注册 |
