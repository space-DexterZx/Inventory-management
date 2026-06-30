import java.sql.*;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

public class InventoryService {
    private final Database db;
    private static final DateTimeFormatter FMT = DateTimeFormatter.ISO_LOCAL_DATE_TIME;

    public InventoryService(Database db) {
        this.db = db;
    }

    public Map<String, Object> getItems() throws SQLException {
        List<Map<String, Object>> items = new ArrayList<>();
        try (Connection c = db.connect();
             Statement s = c.createStatement();
             ResultSet rs = s.executeQuery("SELECT * FROM items ORDER BY name")) {
            while (rs.next()) {
                items.add(Map.of(
                    "id", rs.getInt("id"),
                    "name", rs.getString("name"),
                    "quantity", rs.getInt("quantity")
                ));
            }
        }
        return Map.of("ok", true, "items", items);
    }

    public Map<String, Object> getIssues() throws SQLException {
        List<Map<String, Object>> issues = new ArrayList<>();
        String sql = """
            SELECT i.issue_date, i.location, i.quantity, it.name AS item_name,
                   COALESCE(u.full_name, 'Unknown') AS issued_by
            FROM issues i
            JOIN items it ON i.item_id = it.id
            LEFT JOIN users u ON i.user_id = u.id
            ORDER BY i.issue_date DESC, i.id DESC
            """;
        try (Connection c = db.connect();
             Statement s = c.createStatement();
             ResultSet rs = s.executeQuery(sql)) {
            while (rs.next()) {
                issues.add(Map.of(
                    "issue_date", rs.getString("issue_date"),
                    "location", rs.getString("location"),
                    "quantity", rs.getInt("quantity"),
                    "item_name", rs.getString("item_name"),
                    "issued_by", rs.getString("issued_by")
                ));
            }
        }
        return Map.of("ok", true, "issues", issues);
    }

    public Map<String, Object> addItem(String name, int qty, int userId) throws SQLException {
        try (Connection c = db.connect()) {
            PreparedStatement ps = c.prepareStatement("INSERT INTO items (name, quantity) VALUES (?, ?)");
            ps.setString(1, name.trim());
            ps.setInt(2, qty);
            ps.executeUpdate();
            audit(c, userId, "add_item", "Added " + name.trim() + " with stock " + qty);
        } catch (SQLException e) {
            if (e.getMessage() != null && e.getMessage().contains("UNIQUE")) {
                return Map.of("ok", false, "error", "That item already exists.");
            }
            throw e;
        }
        return Map.of("ok", true, "message", "Added " + name.trim());
    }

    public Map<String, Object> updateStock(int itemId, int newQty, int userId) throws SQLException {
        try (Connection c = db.connect()) {
            PreparedStatement get = c.prepareStatement("SELECT * FROM items WHERE id = ?");
            get.setInt(1, itemId);
            ResultSet rs = get.executeQuery();
            if (!rs.next()) return Map.of("ok", false, "error", "Item not found.");
            String name = rs.getString("name");
            int oldQty = rs.getInt("quantity");

            PreparedStatement upd = c.prepareStatement("UPDATE items SET quantity = ? WHERE id = ?");
            upd.setInt(1, newQty);
            upd.setInt(2, itemId);
            upd.executeUpdate();
            audit(c, userId, "update_stock", "Updated " + name + ": " + oldQty + " → " + newQty);
        }
        return Map.of("ok", true, "message", "Stock updated.");
    }

    public Map<String, Object> issueItems(String location, String issueDate, int userId,
                                          List<Integer> itemIds, List<Integer> quantities) throws SQLException {
        if (location == null || location.trim().isEmpty()) {
            return Map.of("ok", false, "error", "Enter a location.");
        }

        Map<Integer, Integer> lines = new LinkedHashMap<>();
        for (int i = 0; i < itemIds.size(); i++) {
            int id = itemIds.get(i);
            int qty = quantities.get(i);
            if (id <= 0 || qty <= 0) continue;
            lines.merge(id, qty, Integer::sum);
        }
        if (lines.isEmpty()) return Map.of("ok", false, "error", "Add at least one item.");

        try (Connection c = db.connect()) {
            c.setAutoCommit(false);
            List<String> issued = new ArrayList<>();
            try {
                for (var entry : lines.entrySet()) {
                    int itemId = entry.getKey();
                    int qty = entry.getValue();
                    PreparedStatement get = c.prepareStatement("SELECT * FROM items WHERE id = ?");
                    get.setInt(1, itemId);
                    ResultSet rs = get.executeQuery();
                    if (!rs.next()) throw new SQLException("Item not found.");
                    String name = rs.getString("name");
                    int stock = rs.getInt("quantity");
                    if (stock < qty) {
                        throw new SQLException("Only " + stock + " " + name + " left in stock.");
                    }
                    PreparedStatement upd = c.prepareStatement("UPDATE items SET quantity = quantity - ? WHERE id = ?");
                    upd.setInt(1, qty);
                    upd.setInt(2, itemId);
                    upd.executeUpdate();

                    PreparedStatement ins = c.prepareStatement(
                        "INSERT INTO issues (item_id, location, quantity, issue_date, user_id) VALUES (?, ?, ?, ?, ?)");
                    ins.setInt(1, itemId);
                    ins.setString(2, location.trim());
                    ins.setInt(3, qty);
                    ins.setString(4, issueDate);
                    ins.setInt(5, userId);
                    ins.executeUpdate();
                    issued.add(qty + " " + name);
                }
                String detail = "To " + location.trim() + " on " + issueDate + ": " + String.join(", ", issued);
                audit(c, userId, "issue", detail);
                c.commit();
                return Map.of("ok", true, "message", "Issued to " + location.trim() + ": " + String.join(", ", issued));
            } catch (SQLException ex) {
                c.rollback();
                return Map.of("ok", false, "error", ex.getMessage());
            }
        }
    }

    private void audit(Connection c, int userId, String action, String details) throws SQLException {
        PreparedStatement ps = c.prepareStatement(
            "INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?, ?, ?, ?)");
        ps.setInt(1, userId);
        ps.setString(2, action);
        ps.setString(3, details);
        ps.setString(4, LocalDateTime.now().format(FMT));
        ps.executeUpdate();
    }
}