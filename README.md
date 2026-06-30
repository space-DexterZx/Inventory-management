# Nextgen Shield — Stationery Management System

**Repository:** https://github.com/space-DexterZx/Inventory-management

A web-based inventory system built for **Nextgen Shield** to manage office cupboard stationery — pens, scissors, rulers, books, and similar supplies — and keep accurate records when items are issued to different company locations (sites, workshops, branches, etc.).

---

## Table of contents

1. [Why this system exists](#why-this-system-exists)
2. [What it does](#what-it-does)
3. [User roles & permissions](#user-roles--permissions)
4. [Screens & features](#screens--features)
5. [Daily workflow](#daily-workflow)
6. [How multi-device access works](#how-multi-device-access-works)
7. [Tech stack](#tech-stack)
8. [Project structure](#project-structure)
9. [Database schema](#database-schema)
10. [Java API endpoints](#java-api-endpoints)
11. [Requirements](#requirements)
12. [How to run](#how-to-run)
13. [Default login](#default-login)
14. [Adding staff accounts](#adding-staff-accounts)
15. [Activity log — what gets recorded](#activity-log--what-gets-recorded)
16. [Branding & logo](#branding--logo)
17. [Git repository](#git-repository)
18. [Troubleshooting](#troubleshooting)
19. [Limitations](#limitations)

---

## Why this system exists

Nextgen Shield keeps stationery in an **office cupboard**. Staff take items and send them to various locations. Before this system, records were hard to track. This app provides:

- One place to see **current stock**
- A clear **issue record** (date, location, items, who issued)
- **Multiple people** using the system at the same time from different devices
- **Manager oversight** via member management and a full activity log

---

## What it does

| Feature | Description |
|---------|-------------|
| **Current stock** | List everything in the cupboard with live quantities |
| **Add items** | Register new stationery types (e.g. Pens, Scissors, Rulers) |
| **Update stock** | Correct quantities when restocking the cupboard |
| **Issue to location** | Record items sent out — supports **multiple items in one issue** |
| **Issue record** | Full history: date, location, item, quantity, issued by |
| **Quick stats** | Item types count, total in cupboard, total issues logged |
| **Login system** | Separate accounts per person |
| **Member management** | Manager adds/removes staff logins |
| **Activity log** | Manager sees every action, who did it, and when |

---

## User roles & permissions

| Role | Stock & issues | Members | Activity log |
|------|----------------|---------|--------------|
| **Manager** | Yes | Yes — add/remove users | Yes — full audit trail |
| **Executive Administration** | Yes | No | No |

- Each person has their own **username** and **password**
- Several people can be logged in **at the same time** from different devices
- The system records **who** made each stock change and issue

---

## Screens & features

### Login
- Nextgen Shield logo (transparent, no white background)
- Username + password
- Works from any browser on the office network

### Stock & Issues (main page)
Three sections on one page:

1. **Current stock** — table of items, quantities, inline update
2. **Issue to location** — date, location, one or more item lines (`+ another item`)
3. **Issue record** — complete history with "Issued By" column

### Members (Manager only)
- View all active team members (name, username, role)
- Add new member: full name, username, password, role
- Remove member (cannot remove your own account)

### Activity log (Manager only)
- Every logged action with timestamp, user, role, action type, and details

---

## Daily workflow

### Morning — start the server (one Mac in the office)
```bash
cd office-inventory
./start.sh
```
Keep this Mac **on** and Terminal **open** (or running in background).

### Share the link with staff
Terminal shows:
```
On this Mac:     http://127.0.0.1:8888
Other devices: http://192.168.x.x:8888
```
Staff on phones, laptops, or office PCs open the **Other devices** link in their browser.

### During the day
1. Staff log in with their own account
2. Check stock before issuing
3. Record issues: date + location + items (multiple allowed)
4. Stock updates automatically
5. Manager can review activity log anytime

### Adding new cupboard items
- Bottom of **Current stock** → type item name + quantity → **Add item**

### Restocking the cupboard
- Change the number in the stock table → **Save**

---

## How multi-device access works

```
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│  Phone      │  │  Laptop     │  │  Office PC  │
│  (browser)  │  │  (browser)  │  │  (browser)  │
└──────┬──────┘  └──────┬──────┘  └──────┬──────┘
       │                │                │
       └────────────────┼────────────────┘
                        │  office Wi-Fi
                        ▼
              ┌─────────────────────┐
              │  Server Mac         │
              │  ./start.sh         │
              │  Python Flask app   │
              │  port 8888          │
              └──────────┬──────────┘
                         │
                         ▼
              ┌─────────────────────┐
              │  ONE database       │
              │  data/inventory.db  │
              └─────────────────────┘
```

- **One Mac** runs the app and holds **one database**
- Other devices only use a **browser** — nothing to install
- When anyone updates stock or issues items, **everyone sees the same data**
- Database uses **WAL mode** for safer concurrent access from multiple users

---

## Tech stack

| Technology | Role in this project |
|------------|---------------------|
| **Python 3** | Main web application (Flask) — login, pages, database, network server |
| **HTML** | Page structure (Jinja2 templates + PHP views) |
| **CSS** | Dark theme UI matching Nextgen Shield black & gold branding |
| **JavaScript** | Multi-item issue form (`public/assets/js/app.js`) |
| **Java** | Optional inventory REST API on port 8081 (stock/issue operations) |
| **PHP** | Optional alternate web layer (`public/index.php`) — requires PHP installed |
| **SQLite** | Single-file database — no separate database server needed |
| **Flask** | Python web framework (`requirements.txt`) |
| **Git** | Version control — hosted on GitHub |

### Primary runtime (no admin install)
- **Python** — already on Mac, no install permission needed
- **Java** — uses JDK in user home folder (`~/oracleJdk-25.jdk`) if present
- **No PHP required** for normal use

---

## Project structure

```
office-inventory/
├── app.py                 # Main Flask application (primary)
├── start.sh               # Start server — use this daily
├── run.sh                 # Alternative launcher (PHP + Java if available)
├── requirements.txt       # Python dependency: Flask
├── README.md              # This file
├── .gitignore             # Excludes live data from Git
│
├── data/
│   ├── inventory.db       # LIVE database (not in Git)
│   └── .secret            # Session key (not in Git)
│
├── templates/             # HTML pages (Flask/Jinja2)
│   ├── base.html          # Layout, sidebar, navigation
│   ├── login.html         # Sign-in page
│   ├── index.html         # Stock, issue form, issue record
│   ├── members.html       # Team management (manager)
│   └── logs.html          # Activity log (manager)
│
├── static/
│   ├── style.css          # Dark theme styles
│   └── logo.png           # Transparent Nextgen Shield logo
│
├── public/                # PHP web root (optional stack)
│   ├── index.php          # PHP router
│   └── assets/
│       ├── css/style.css
│       ├── js/app.js      # Multi-item issue rows
│       └── img/logo.png
│
├── php/                   # PHP backend (optional)
│   ├── includes/          # config, auth, database, Java client
│   └── views/             # PHP page templates
│
└── java/                  # Java inventory API (optional)
    ├── src/
    │   ├── InventoryServer.java   # HTTP server
    │   ├── InventoryService.java  # Business logic
    │   └── Database.java        # SQLite connection
    └── lib/               # JDBC, Gson, SLF4J jars
```

---

## Database schema

Single SQLite file: `data/inventory.db`

### `users`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| username | TEXT | Unique login name |
| password_hash | TEXT | Hashed password (pbkdf2:sha256) |
| full_name | TEXT | Display name |
| role | TEXT | `manager` or `executive` |
| active | INTEGER | 1 = active, 0 = removed |
| created_at | TEXT | ISO timestamp |

### `items`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| name | TEXT | Unique item name (e.g. "Pens") |
| quantity | INTEGER | Current stock in cupboard |

### `issues`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| item_id | INTEGER | FK → items |
| location | TEXT | Where items were sent |
| quantity | INTEGER | How many issued |
| issue_date | TEXT | Date of issue (YYYY-MM-DD) |
| user_id | INTEGER | FK → users (who issued) |

### `audit_logs`
| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER | Primary key |
| user_id | INTEGER | FK → users |
| action | TEXT | Action type (see below) |
| details | TEXT | Human-readable description |
| created_at | TEXT | ISO timestamp |

---

## Java API endpoints

When Java service is running (`start.sh` auto-starts it on port **8081**):

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/items` | List all items and quantities |
| GET | `/api/issues` | List all issue records |
| POST | `/api/add-item` | Add new item `{name, quantity, user_id}` |
| POST | `/api/update-stock` | Update quantity `{item_id, quantity, user_id}` |
| POST | `/api/issue` | Issue items `{location, issue_date, user_id, item_ids[], quantities[]}` |

The Python Flask app (`app.py`) handles everything directly for normal use. Java API is used by the optional PHP layer.

---

## Requirements

### Server Mac (the one running `./start.sh`)
- macOS
- **Python 3** (pre-installed on Mac)
- **Flask:** `pip3 install -r requirements.txt`
- **No admin access needed** for normal operation
- Optional: Java JDK in `~/oracleJdk-25.jdk`

### Staff devices (phones, laptops, PCs)
- Any device with a web browser
- Connected to the **same office Wi-Fi** as the server Mac
- No software installation

---

## How to run

### First time setup
```bash
git clone git@github.com:space-DexterZx/Inventory-management.git
cd Inventory-management
pip3 install -r requirements.txt
./start.sh
```

### Every day
```bash
cd office-inventory
./start.sh
```

### Stop the server
Press `Ctrl + C` in Terminal.

### Access URLs
| Who | URL |
|-----|-----|
| Server Mac itself | http://127.0.0.1:8888 |
| Other devices on office network | http://YOUR_MAC_IP:8888 |

The IP is printed in Terminal when you run `./start.sh`.

---

## Default login

| Field | Value |
|-------|-------|
| Username | `manager` |
| Password | `manager123` |

**Change this after first use** by adding a new manager account and removing the default, or by updating the password via Members.

---

## Adding staff accounts

1. Log in as **Manager**
2. Go to **Members**
3. Fill in **Add someone:**
   - Full name (e.g. W.M. Salinda)
   - Username (login name)
   - Password
   - Role: **Executive Administration** or **Manager**
4. Click **Add member**
5. Share with the person:
   - The office network URL (e.g. `http://192.168.1.50:8888`)
   - Their username and password

---

## Activity log — what gets recorded

| Action | When |
|--------|------|
| `login` | User signs in |
| `logout` | User signs out |
| `add_item` | New item added to cupboard |
| `update_stock` | Stock quantity changed |
| `issue` | Items issued to a location |
| `add_member` | Manager adds a staff account |
| `remove_member` | Manager removes a staff account |

Each entry includes: **who**, **when**, and **full details** (e.g. "To Site A on 2026-06-30: 5 Pens, 3 Scissors").

---

## Branding & logo

- **System name:** Nextgen Shield — Stationery Management System
- **Logo:** Transparent PNG extracted from company assets (no white background box)
- **Theme:** Dark background with gold accents matching the Nextgen Shield shield logo
- **Logo file:** `public/assets/img/logo.png` and `static/logo.png`

---

## Git repository

```bash
# Clone
git clone git@github.com:space-DexterZx/Inventory-management.git

# After making changes
git add -A
git commit -m "Describe your change"
git push origin main
```

### Included in Git
- All source code (Python, Java, PHP, HTML, CSS, JS)
- Logo and styles
- Scripts (`start.sh`, `run.sh`)

### Excluded from Git (`.gitignore`)
- `data/inventory.db` — live stock and issue data
- `data/.secret` — session security key
- `java/build/` — compiled Java classes

**Back up `data/inventory.db` separately** if you need to preserve live records.

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Other devices can't connect | Same Wi-Fi? Server Mac running `./start.sh`? Check IP in Terminal |
| "Invalid username or password" | Check caps. Default: manager / manager123 |
| IP address changed | Restart `./start.sh` — new IP shown in Terminal |
| Port 8888 in use | Stop other instances: close Terminal or restart Mac |
| Stock not saving | Ensure server Mac is still running — don't close Terminal |
| Java API error on start | Safe to ignore — Python app works without Java |
| Can't install PHP | Not needed — use `./start.sh` (Python only) |

---

## Limitations

- **Office network only** — devices must be on the same Wi-Fi/LAN as the server Mac
- **Server Mac must stay on** — it holds the database and runs the app
- **No remote access from home** — would need cloud hosting or IT setup (admin access)
- **SQLite** — suitable for small teams; not designed for hundreds of concurrent users
- **No automatic backups** — back up `data/inventory.db` manually if needed
- **Single server** — one Mac is the central hub for all devices

---

## Author

Created for **Nextgen Shield** internal stationery and cupboard inventory management.

**GitHub:** https://github.com/space-DexterZx/Inventory-management