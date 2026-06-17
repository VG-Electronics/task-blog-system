# Task Blog System

A RESTful blog API built with **Laravel 13**, featuring role-based access control, automated risk assessment for posts, content archiving, background job processing, and admin reporting with export capabilities.

---

## Table of Contents

1. [About](#1-about)
2. [Docker Setup](#2-docker-setup)
3. [Postman Collection](#3-postman-collection)
4. [Custom Configuration](#4-custom-configuration)
5. [Background Processing Architecture](#5-background-processing-architecture)
6. [Project Architecture](#6-project-architecture)
7. [API Reference](#7-api-reference)
8. [Roles & Permissions](#8-roles--permissions)
9. [Admin Reporting](#9-admin-reporting)

---

## 1. About

Task Blog System is an API-only Laravel application (no frontend, no Blade views). It provides a full blogging platform backend with:

- **Token-based authentication** via Laravel Sanctum
- **Role-based access control** — three roles: `User`, `Moderator`, `Admin`
- **Automated post risk scoring** — background job analyses every new/updated post and flags high-risk content
- **Admin email notifications** — admins are notified when a high-risk post is detected
- **Scheduled post archiving** — posts older than a configurable number of days are automatically archived
- **Comment flagging** — moderators and admins can flag/unflag comments for review
- **Admin reporting** — filterable, sortable reports on posts and comments, exportable to JSON, CSV, or Excel
- **Soft deletes** on users, posts, and comments

**Tech stack:** PHP 8.3+, Laravel 13, Laravel Sanctum, MySQL 8.4, Nginx, PHPUnit 12, phpoffice/phpspreadsheet

---

## 2. Docker Setup

### Prerequisites

- **Docker Desktop** must be installed and **running** before executing any Docker commands.
  On Windows, open Docker Desktop from the Start Menu and wait until the whale icon in the system tray shows "Docker Desktop is running".
- Docker Compose v2 (bundled with Docker Desktop)

### Starting the application

```bash
docker-compose up -d
```

That single command builds all images and starts four containers:

| Container  | Role                                      | Exposed port  |
|------------|-------------------------------------------|---------------|
| `app`      | PHP 8.5-FPM — Laravel application         | internal :9000 |
| `nginx`    | Nginx reverse proxy                       | **localhost:8080** |
| `mysql`    | MySQL 8.4 database                        | internal      |
| `scheduler`| Runs `php artisan schedule:work`          | —             |
| `worker`   | Runs `php artisan queue:work`             | —             |

The API is available at: **`http://localhost:8080/api`**

### What happens on first boot

The `app` container entrypoint automatically performs first-time setup:

1. Copies `.env.example` → `.env` (if `.env` does not exist)
2. Runs `composer install`
3. Generates the application key (`php artisan key:generate`)
4. Runs all database migrations (`php artisan migrate --force`)
5. Seeds the database with roles (`php artisan db:seed --class=DatabaseSeeder`)

The `scheduler` and `worker` containers wait for `app` to finish setup (health check) before starting. They skip setup automatically via `SKIP_SETUP=true`.

### Seeding test users

The default seeder only creates roles. To seed admin, moderator, and regular user accounts:

```bash
docker-compose exec app php artisan db:seed --class=UserSeeder
```

This creates the following accounts (all passwords: `password`):

| Role      | Count | Email                   |
|-----------|-------|-------------------------|
| Admin     | 1     | `admin@blog.test`       |
| Moderator | 2     | random (printed to console) |
| User      | 3     | random (printed to console) |

Credentials are printed to the console output after the seeder finishes.

To also seed sample posts, comments, and tags for development:

```bash
docker-compose exec app php artisan db:seed --class=DevelopmentSeeder
```

### Stopping the application

```bash
docker-compose down
```

To also remove volumes (database data):

```bash
docker-compose down -v
```

### Running tests

```bash
docker-compose exec app php artisan test
```

Tests run against a dedicated **`laravel_test`** MySQL database, separate from the development database (`laravel`).

The `laravel_test` database is created automatically by `docker/mysql/init.sql` when the MySQL volume is first initialised.

---

## 3. Postman Collection

A ready-to-use Postman collection is included in the repository: **`Blog System API.postman_collection.json`**

### Importing

1. Open Postman
2. Click **Import** (top-left)
3. Drag and drop `Blog System API.postman_collection.json` onto the import dialog, or click **Choose Files** and select it
4. Click **Import**

The collection is immediately ready to use — no manual configuration required.

### What's included

The collection covers all API endpoints, organized into folders:

| Folder | Requests |
|---|---|
| Health | Ping |
| Auth | Register, Login, Logout |
| Posts | List, Get, Create, Update, Delete |
| Tags | Attach Tag, Detach Tag |
| Comments | List, Get, Create, Update, Delete, Flag, Unflag |
| Admin – Reporting | Get Analytics, List Admin Posts, List Admin Comments |

### Collection variables

| Variable | Default value | Description |
|---|---|---|
| `base_url` | `http://localhost:8080/api` | API base URL — change if you run on a different port |
| `token` | _(empty)_ | Bearer token — set automatically after login/register |
| `post_id` | _(empty)_ | Set automatically after creating a post |
| `comment_id` | _(empty)_ | Set manually to target a specific comment |

### Automatic token handling

The **Register** and **Login** requests include a Postman test script that extracts the token from the response and saves it to the `token` collection variable automatically. All protected requests use `{{token}}` as their Bearer token — so after a successful login or register, all subsequent requests are authenticated without any manual copy-pasting.

Similarly, **Create Post** automatically saves the returned post ID to the `post_id` variable, which is then used by Update, Delete, and comment/tag requests.

---

## 4. Custom Configuration

The application ships with two custom config files beyond the standard Laravel defaults.

### `config/post_risk.php` — Post Risk Assessment

Controls how the `CalculatePostRisk` background job scores posts.

| Key                          | Default                                          | Description                                              |
|------------------------------|--------------------------------------------------|----------------------------------------------------------|
| `default_risk_score`         | `20`                                             | Base score applied to every post                         |
| `short_content_score`        | `10`                                             | Added if content length ≤ `short_content_length`         |
| `short_content_length`       | `49`                                             | Character threshold to consider content "short"          |
| `keywords_score`             | `5`                                              | Added per matched keyword in title/description/content   |
| `risk_score_threshold`       | `71`                                             | Score ≥ this → `high` risk                               |
| `medium_risk_score_threshold`| `30`                                             | Score ≥ this → `medium` risk; below → `low`              |
| `keywords`                   | `fire, accident, theft, damage, burglary`        | Keywords that increase the risk score                    |

**Risk levels:** `low` · `medium` · `high`

When a post reaches `high` risk, the system automatically dispatches `NotifyAboutHighRiskPost`, which notifies all admin users via the notification channel.

Override any value in `.env` or directly in the config file.

### `config/post_archiving.php` — Post Archiving

Controls the daily archiving scheduler job.

| Key                  | Default | Description                                                     |
|----------------------|---------|-----------------------------------------------------------------|
| `archive_after_days` | `30`    | Posts not updated for this many days have `archived_at` set     |

The archiving job runs daily at **02:00** via the scheduler container.

---

## 5. Background Processing Architecture

The system runs three types of background processes, each in its own container.

### Queue Worker

Container: `worker` — command: `php artisan queue:work --sleep=3 --tries=3`

Queue driver: **database** (jobs stored in the `jobs` table).

| Job                       | Queue    | Trigger                          | What it does                                                                                  |
|---------------------------|----------|----------------------------------|-----------------------------------------------------------------------------------------------|
| `CalculatePostRisk`       | default  | Post created or updated          | Scores the post; sets `risk_level` and `risk_score`; if HIGH → dispatches notification job    |
| `NotifyAboutHighRiskPost` | emails   | `CalculatePostRisk` (HIGH result)| Sends `HighRiskPostNotification` to every admin user via the mailing channel                  |

Failed jobs are stored in the `failed_jobs` table and can be retried with `php artisan queue:retry all`.

### Scheduler

Container: `scheduler` — command: `php artisan schedule:work`

| Job              | Schedule      | What it does                                                           |
|------------------|---------------|------------------------------------------------------------------------|
| `ArchiveOldPosts`| Daily at 02:00| Sets `archived_at` on posts older than `post_archiving.archive_after_days` |

### Notification Channel

Notifications use a custom `MailingChannel` backed by `MailingServiceInterface`. The default implementation is `LogMailingService`, which writes notifications to the Laravel log file (`storage/logs/laravel.log`) instead of sending real emails. To send real emails, implement `MailingServiceInterface` and bind it in the service container.

---

## 6. Project Architecture

```
app/
├── Console/                  # Scheduled commands via routes/console.php
├── Http/
│   ├── Controllers/
│   │   ├── AuthController        # register, login, logout
│   │   ├── PostController        # CRUD for posts
│   │   ├── CommentController     # CRUD + flag/unflag for comments
│   │   ├── TagController         # assign/unassign tags to posts
│   │   └── Admin/
│   │       └── ReportingController  # post/comment reports + analytics
│   ├── Middleware/
│   │   └── EnsureUserIsAdmin     # 403 if not admin role
│   └── Requests/             # Form request validation classes
├── Jobs/
│   ├── CalculatePostRisk         # Scores posts for risky content
│   ├── NotifyAboutHighRiskPost   # Notifies admins of high-risk posts
│   └── ArchiveOldPosts           # Daily archiving job
├── Models/
│   ├── User                      # Soft delete, has roles (many-to-many)
│   ├── Post                      # Soft delete, risk_level enum, archived_at
│   ├── Comment                   # Soft delete, flag boolean
│   ├── Tag
│   └── Role                      # Constants: USER, MODERATOR, ADMIN
├── Notifications/
│   └── HighRiskPostNotification
├── Channels/
│   └── MailingChannel            # Custom notification channel
├── Services/
│   ├── AuthService               # Token creation/revocation
│   ├── RiskAssessmentService     # Scoring logic for CalculatePostRisk
│   ├── ConfigService             # Base typed config accessor
│   ├── RiskAssessmentConfig      # Typed wrapper for post_risk config
│   └── PostArchivingConfig       # Typed wrapper for post_archiving config
└── Enums/
    └── PostRiskLevel             # low | medium | high
```

### Database Schema

```
users           id, nickname, email, password, deleted_at, timestamps
posts           id, user_id, title, description, content,
                risk_level (nullable), risk_score (nullable int),
                archived_at (nullable), deleted_at, timestamps
comments        id, user_id, post_id, content, flag (bool), deleted_at, timestamps
tags            id, tag, timestamps
posts_tags      id, post_id, tag_id
roles           id, name, timestamps
role_user       role_id, user_id  (composite PK)
jobs            Laravel queue jobs table
failed_jobs     Laravel failed jobs table
personal_access_tokens  Laravel Sanctum tokens
```

---

## 7. API Reference

> **OpenAPI / Swagger spec** — the full machine-readable specification is available in [`openapi.yaml`](openapi.yaml) at the root of the repository.
> Paste its contents into [editor.swagger.io](https://editor.swagger.io) or import it into Postman / Insomnia for an interactive UI.

Base URL: `http://localhost:8080/api`

All requests that send a body must use `Content-Type: application/json`.  
Protected routes require the header: `Authorization: Bearer <token>`

### Health Check

#### Ping

```
GET /api/ping
```

Public. Use this to verify the API is reachable and the server is running.

**Response `200`:**

```json
{ "status": "OK" }
```

---

### Authentication

#### Register

```
POST /api/register
```

**Body:**

```json
{
  "nickname": "string (unique, max 255)",
  "email": "valid email (unique)",
  "password": "min 8 characters",
  "password_confirmation": "same as password"
}
```

**Response `201`:**

```json
{ "token": "1|abc123..." }
```

---

#### Login

```
POST /api/login
```

**Body:**

```json
{ "email": "user@example.com", "password": "secret" }
```

**Response `200`:**

```json
{ "token": "2|xyz789..." }
```

**Response `401`:** Invalid credentials.

---

#### Logout

```
POST /api/logout
```

Requires authentication. Invalidates the current Bearer token.

**Response `204`:** No content.

---

### Posts

#### List posts

```
GET /api/posts
```

Public. Returns all non-archived, non-deleted posts.

---

#### Get post

```
GET /api/posts/{post}
```

Public.

---

#### Create post

```
POST /api/posts
```

Requires authentication. Dispatches `CalculatePostRisk` job asynchronously after creation.

**Body:**

```json
{
  "title": "string (max 255)",
  "description": "string",
  "content": "string"
}
```

---

#### Update post

```
PUT /api/posts/{post}
```

Requires authentication. Owner or Admin only. Re-dispatches `CalculatePostRisk`.

**Body:** same as create (all fields optional on update).

---

#### Delete post

```
DELETE /api/posts/{post}
```

Requires authentication. Owner or Admin only. Soft deletes the post.

---

### Tags

#### Assign tag to post

```
POST /api/posts/{post}/tags/{tag}
```

Requires authentication. The `{tag}` segment is the tag name (string). If the tag does not exist, it is created automatically.

Allowed: post owner, any Moderator, any Admin.

---

#### Remove tag from post

```
DELETE /api/posts/{post}/tags/{tag}
```

Requires authentication. Same permission rules as assign.

---

### Comments

#### List comments for post

```
GET /api/posts/{post}/comments
```

Public.

---

#### Get comment

```
GET /api/posts/{post}/comments/{comment}
```

Public.

---

#### Add comment

```
POST /api/posts/{post}/comments
```

Requires authentication.

**Body:**

```json
{ "content": "string" }
```

---

#### Update comment

```
PUT /api/posts/{post}/comments/{comment}
```

Requires authentication. Owner or Admin only.

**Body:**

```json
{ "content": "string" }
```

---

#### Delete comment

```
DELETE /api/posts/{post}/comments/{comment}
```

Requires authentication. Owner, Moderator, or Admin.

---

#### Flag comment

```
POST /api/posts/{post}/comments/{comment}/flag
```

Requires authentication. Moderator or Admin only. Sets `flag = true` on the comment.

---

#### Unflag comment

```
DELETE /api/posts/{post}/comments/{comment}/flag
```

Requires authentication. Moderator or Admin only. Sets `flag = false`.

---

### Admin Reporting

All reporting endpoints require authentication and the `Admin` role.

See [Section 8 — Admin Reporting](#8-admin-reporting) for full filter/export documentation.

```
GET /api/admin/reporting/posts
GET /api/admin/reporting/comments
GET /api/admin/reporting/analytics
```

---

## 8. Roles & Permissions

Users can hold multiple roles simultaneously via a many-to-many `role_user` pivot table.

| Action                        | User | Moderator | Admin |
|-------------------------------|:----:|:---------:|:-----:|
| Register / Login              | ✓    | ✓         | ✓     |
| View posts & comments         | ✓    | ✓         | ✓     |
| Create post                   | ✓    | ✓         | ✓     |
| Update **own** post           | ✓    | ✓         | ✓     |
| Update **any** post           | ✗    | ✗         | ✓     |
| Delete **own** post           | ✓    | ✓         | ✓     |
| Delete **any** post           | ✗    | ✗         | ✓     |
| Add comment                   | ✓    | ✓         | ✓     |
| Edit **own** comment          | ✓    | ✓         | ✓     |
| Edit **any** comment          | ✗    | ✗         | ✓     |
| Delete **own** comment        | ✓    | ✓         | ✓     |
| Delete **any** comment        | ✗    | ✓         | ✓     |
| Flag / unflag comments        | ✗    | ✓         | ✓     |
| Assign tag to **own** post    | ✓    | ✓         | ✓     |
| Assign tag to **any** post    | ✗    | ✓         | ✓     |
| Access admin reporting        | ✗    | ✗         | ✓     |

---

## 9. Admin Reporting

All three reporting endpoints accept query parameters for filtering and output format.

### Common parameters (posts & comments)

| Parameter          | Values                          | Default       | Description                         |
|--------------------|---------------------------------|---------------|-------------------------------------|
| `format`           | `json`, `csv`, `xls`            | `json`        | Response format                     |
| `sort_by`          | `created_at`, `updated_at`      | `created_at`  | Sort field                          |
| `sort_dir`         | `asc`, `desc`                   | `desc`        | Sort direction                      |
| `from`             | ISO 8601 date                   | —             | Filter records created/updated from |
| `to`               | ISO 8601 date                   | —             | Filter records created/updated to   |
| `filtering_logic`  | `and`, `or`                     | `and`         | How multiple filters are combined   |

### Post-specific parameters

| Parameter    | Values                    | Description                              |
|--------------|---------------------------|------------------------------------------|
| `risk_level` | `low`, `medium`, `high`   | Filter by post risk level                |
| `min_comments`| integer                  | Minimum number of comments on the post   |
| `user_role`  | `User`, `Moderator`, `Admin` | Filter by the role of the post author |

### Comment-specific parameters

| Parameter   | Values                       | Description                                |
|-------------|------------------------------|--------------------------------------------|
| `flag`      | `true`, `false`              | Filter by flagged status                   |
| `user_role` | `User`, `Moderator`, `Admin` | Filter by the role of the comment author   |

### Analytics endpoint

`GET /api/admin/reporting/analytics`

**Response:**

```json
{
  "total_posts": 42,
  "total_comments": 120,
  "avg_comments_per_post": 2.86,
  "top_users": [
    { "id": 1, "nickname": "alice", "post_count": 10 }
  ],
  "most_common_tags": [
    { "tag": "news", "count": 15 }
  ]
}
```

### Export formats

- **`format=json`** — standard JSON response (default)
- **`format=csv`** — file download (`Content-Disposition: attachment`)
- **`format=xls`** — Excel `.xlsx` file download via phpoffice/phpspreadsheet
