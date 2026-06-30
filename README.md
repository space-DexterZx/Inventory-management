# Nextgen Shield — Stationery Management System

A simple web-based inventory system for tracking office cupboard supplies and issuing items to different locations.

Built for **Nextgen Shield** to replace manual records — pens, scissors, rulers, books, and other stationery kept in the office cupboard and sent out to sites, workshops, and branches.

## What it does

- **Stock** — see what's in the cupboard and update quantities
- **Issue to locations** — record date, location, and multiple items in one go
- **Issue history** — full log of what went where and who issued it
- **Multi-user login** — Manager and Executive Administration roles
- **Members** — manager can add or remove staff accounts
- **Activity log** — manager sees every change and who made it

## Who uses it

| Role | Access |
|------|--------|
| **Manager** | Stock, issues, members, activity log |
| **Executive Administration** | Stock and issues |

## How to run (no admin install needed)

```bash
cd office-inventory
./start.sh
```

- **On the server Mac:** http://127.0.0.1:8888
- **Other devices (same office Wi-Fi):** use the IP shown in Terminal, e.g. `http://192.168.x.x:8888`

Default login: `manager` / `manager123` — change after first login and add staff under **Members**.

## Tech stack

- **Python** (Flask) — web app, login, database
- **Java** — inventory API (stock & issues)
- **HTML / CSS / JavaScript** — interface
- **PHP** — optional alternate web layer
- **SQLite** — one shared database on the server Mac

## Important notes

- The Mac running `./start.sh` is the **server** — it holds the one database everyone shares
- All staff connect via browser; no install needed on their devices
- Live data (`data/inventory.db`) stays on the server and is not pushed to Git

## Repository

https://github.com/space-DexterZx/Inventory-management