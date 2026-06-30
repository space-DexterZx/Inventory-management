import secrets
import socket
import sqlite3
from datetime import date, datetime
from functools import wraps
from pathlib import Path

from flask import (
    Flask,
    flash,
    g,
    redirect,
    render_template,
    request,
    session,
    url_for,
)
from werkzeug.security import check_password_hash, generate_password_hash

app = Flask(__name__)
DATABASE = Path(__file__).parent / "data" / "inventory.db"
SECRET_FILE = DATABASE.parent / ".secret"

if SECRET_FILE.exists():
    app.secret_key = SECRET_FILE.read_text().strip()
else:
    DATABASE.parent.mkdir(parents=True, exist_ok=True)
    app.secret_key = secrets.token_hex(32)
    SECRET_FILE.write_text(app.secret_key)

ROLES = {
    "manager": "Manager",
    "executive": "Executive Administration",
}
HASH_METHOD = "pbkdf2:sha256"


def get_db():
    if "db" not in g:
        g.db = sqlite3.connect(DATABASE, timeout=15)
        g.db.row_factory = sqlite3.Row
        g.db.execute("PRAGMA journal_mode=WAL")
    return g.db


def local_ips() -> list[str]:
    ips = set()
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ips.add(s.getsockname()[0])
        s.close()
    except OSError:
        pass
    try:
        for info in socket.getaddrinfo(socket.gethostname(), None, socket.AF_INET):
            ips.add(info[4][0])
    except OSError:
        pass
    ips.discard("127.0.0.1")
    return sorted(ips)


@app.teardown_appcontext
def close_db(_error):
    db = g.pop("db", None)
    if db is not None:
        db.close()


def log_action(action, details=""):
    user_id = session.get("user_id")
    if not user_id:
        return
    get_db().execute(
        "INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?, ?, ?, ?)",
        (user_id, action, details, datetime.now().isoformat(timespec="seconds")),
    )


def current_user():
    if "user_id" not in session:
        return None
    return get_db().execute(
        "SELECT * FROM users WHERE id = ? AND active = 1", (session["user_id"],)
    ).fetchone()


def login_required(view):
    @wraps(view)
    def wrapped(*args, **kwargs):
        if not current_user():
            flash("Please log in to continue.", "error")
            return redirect(url_for("login"))
        return view(*args, **kwargs)

    return wrapped


def manager_required(view):
    @wraps(view)
    def wrapped(*args, **kwargs):
        user = current_user()
        if not user:
            flash("Please log in to continue.", "error")
            return redirect(url_for("login"))
        if user["role"] != "manager":
            flash("Only managers can access this page.", "error")
            return redirect(url_for("index"))
        return view(*args, **kwargs)

    return wrapped


def init_db():
    db = sqlite3.connect(DATABASE)
    db.executescript("""
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('manager', 'executive')),
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            quantity INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS issues (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER NOT NULL,
            location TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            issue_date TEXT NOT NULL,
            user_id INTEGER,
            FOREIGN KEY (item_id) REFERENCES items(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            details TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    """)

    issue_cols = {row[1] for row in db.execute("PRAGMA table_info(issues)").fetchall()}
    if "user_id" not in issue_cols:
        db.execute("ALTER TABLE issues ADD COLUMN user_id INTEGER REFERENCES users(id)")

    if db.execute("SELECT COUNT(*) FROM users").fetchone()[0] == 0:
        now = datetime.now().isoformat(timespec="seconds")
        db.execute(
            """INSERT INTO users (username, password_hash, full_name, role, created_at)
               VALUES (?, ?, ?, ?, ?)""",
            (
                "manager",
                generate_password_hash("manager123", method=HASH_METHOD),
                "System Manager",
                "manager",
                now,
            ),
        )

    db.commit()
    db.close()


@app.context_processor
def inject_globals():
    user = current_user() if session.get("user_id") else None
    return {"current_user": user, "roles": ROLES}


@app.route("/login", methods=["GET", "POST"])
def login():
    if current_user():
        return redirect(url_for("index"))

    if request.method == "POST":
        username = request.form["username"].strip().lower()
        password = request.form["password"]
        user = get_db().execute(
            "SELECT * FROM users WHERE username = ? AND active = 1", (username,)
        ).fetchone()

        if user and check_password_hash(user["password_hash"], password):
            session.clear()
            session["user_id"] = user["id"]
            log_action("login", f"{user['full_name']} logged in")
            get_db().commit()
            flash(f"Welcome, {user['full_name']}.", "success")
            return redirect(url_for("index"))

        flash("Invalid username or password.", "error")

    return render_template("login.html")


@app.route("/logout")
@login_required
def logout():
    user = current_user()
    log_action("logout", f"{user['full_name']} logged out")
    get_db().commit()
    session.clear()
    flash("You have been logged out.", "success")
    return redirect(url_for("login"))


@app.route("/members", methods=["GET", "POST"])
@manager_required
def members():
    db = get_db()
    user = current_user()

    if request.method == "POST":
        action = request.form.get("action")

        if action == "add":
            username = request.form["username"].strip().lower()
            full_name = request.form["full_name"].strip()
            password = request.form["password"]
            role = request.form["role"]

            if not username or not full_name or not password:
                flash("Fill in all fields.", "error")
            elif role not in ROLES:
                flash("Invalid role.", "error")
            else:
                try:
                    db.execute(
                        """INSERT INTO users
                           (username, password_hash, full_name, role, created_at)
                           VALUES (?, ?, ?, ?, ?)""",
                        (
                            username,
                            generate_password_hash(password, method=HASH_METHOD),
                            full_name,
                            role,
                            datetime.now().isoformat(timespec="seconds"),
                        ),
                    )
                    log_action(
                        "add_member",
                        f"Added {full_name} ({ROLES[role]}) — username: {username}",
                    )
                    flash(f"Member {full_name} added.", "success")
                except sqlite3.IntegrityError:
                    flash("That username already exists.", "error")

        elif action == "remove":
            member_id = int(request.form["id"])
            if member_id == user["id"]:
                flash("You cannot remove your own account.", "error")
            else:
                member = db.execute(
                    "SELECT * FROM users WHERE id = ?", (member_id,)
                ).fetchone()
                if member:
                    db.execute("UPDATE users SET active = 0 WHERE id = ?", (member_id,))
                    log_action(
                        "remove_member",
                        f"Removed {member['full_name']} ({ROLES[member['role']]})",
                    )
                    flash(f"Removed {member['full_name']}.", "success")

        db.commit()
        return redirect(url_for("members"))

    members_list = db.execute(
        "SELECT * FROM users WHERE active = 1 ORDER BY role, full_name"
    ).fetchall()
    return render_template("members.html", members=members_list)


@app.route("/logs")
@manager_required
def logs():
    entries = get_db().execute("""
        SELECT a.created_at, a.action, a.details, u.full_name, u.role
        FROM audit_logs a
        JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC, a.id DESC
    """).fetchall()
    return render_template("logs.html", entries=entries)


@app.route("/", methods=["GET", "POST"])
@login_required
def index():
    db = get_db()
    user = current_user()

    if request.method == "POST":
        action = request.form.get("action")

        if action == "add_item":
            name = request.form["name"].strip()
            qty = int(request.form.get("quantity", 0))
            if not name:
                flash("Enter an item name.", "error")
            else:
                try:
                    db.execute(
                        "INSERT INTO items (name, quantity) VALUES (?, ?)",
                        (name, qty),
                    )
                    log_action("add_item", f"Added {name} with stock {qty}")
                    flash(f"Added {name}.", "success")
                except sqlite3.IntegrityError:
                    flash("That item already exists.", "error")

        elif action == "update_stock":
            item = db.execute(
                "SELECT * FROM items WHERE id = ?", (int(request.form["id"]),)
            ).fetchone()
            new_qty = int(request.form["quantity"])
            if item:
                db.execute(
                    "UPDATE items SET quantity = ? WHERE id = ?",
                    (new_qty, item["id"]),
                )
                log_action(
                    "update_stock",
                    f"Updated {item['name']}: {item['quantity']} → {new_qty}",
                )
                flash("Stock updated.", "success")

        elif action == "issue":
            location = request.form["location"].strip()
            issue_date = request.form["issue_date"]
            item_ids = request.form.getlist("item_id")
            quantities = request.form.getlist("quantity")

            lines = {}
            for item_id, qty in zip(item_ids, quantities):
                if not item_id:
                    continue
                item_id = int(item_id)
                qty = int(qty)
                if qty <= 0:
                    flash("Quantity must be more than zero.", "error")
                    break
                lines[item_id] = lines.get(item_id, 0) + qty
            else:
                if not location:
                    flash("Enter a location.", "error")
                elif not lines:
                    flash("Add at least one item.", "error")
                else:
                    issued = []
                    try:
                        db.execute("BEGIN")
                        for item_id, qty in lines.items():
                            item = db.execute(
                                "SELECT * FROM items WHERE id = ?", (item_id,)
                            ).fetchone()
                            if not item:
                                raise ValueError("Item not found.")
                            if item["quantity"] < qty:
                                raise ValueError(
                                    f"Only {item['quantity']} {item['name']} left in stock."
                                )
                            db.execute(
                                "UPDATE items SET quantity = quantity - ? WHERE id = ?",
                                (qty, item_id),
                            )
                            db.execute(
                                """INSERT INTO issues
                                   (item_id, location, quantity, issue_date, user_id)
                                   VALUES (?, ?, ?, ?, ?)""",
                                (item_id, location, qty, issue_date, user["id"]),
                            )
                            issued.append(f"{qty} {item['name']}")
                        db.execute("COMMIT")
                        detail = f"To {location} on {issue_date}: {', '.join(issued)}"
                        log_action("issue", detail)
                        flash(f"Issued to {location}: {', '.join(issued)}.", "success")
                    except ValueError as exc:
                        db.execute("ROLLBACK")
                        flash(str(exc), "error")

        db.commit()
        return redirect(url_for("index"))

    items = db.execute("SELECT * FROM items ORDER BY name").fetchall()
    issues = db.execute("""
        SELECT i.issue_date, i.location, i.quantity, it.name as item_name,
               COALESCE(u.full_name, 'Unknown') as issued_by
        FROM issues i
        JOIN items it ON i.item_id = it.id
        LEFT JOIN users u ON i.user_id = u.id
        ORDER BY i.issue_date DESC, i.id DESC
    """).fetchall()

    stats = {
        "item_count": len(items),
        "total_stock": sum(i["quantity"] for i in items),
        "issue_count": len(issues),
    }

    return render_template(
        "index.html",
        items=items,
        issues=issues,
        stats=stats,
        today=date.today().isoformat(),
    )


if __name__ == "__main__":
    DATABASE.parent.mkdir(parents=True, exist_ok=True)
    init_db()
    port = 8888
    print("\n  Nextgen Shield — Stationery Management")
    print(f"  On this Mac:     http://127.0.0.1:{port}")
    for ip in local_ips():
        print(f"  Other devices:   http://{ip}:{port}")
    print("\n  Each person logs in with their own username & password.")
    print("  Login: manager / manager123\n")
    app.run(host="0.0.0.0", port=port, debug=False, threaded=True, use_reloader=False)