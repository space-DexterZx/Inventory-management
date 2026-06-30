import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import com.sun.net.httpserver.*;
import java.io.*;
import java.lang.reflect.Type;
import java.net.InetSocketAddress;
import java.nio.charset.StandardCharsets;
import java.util.*;

public class InventoryServer {
    private static final Gson GSON = new Gson();
    private static final Type MAP_TYPE = new TypeToken<Map<String, Object>>(){}.getType();

    public static void main(String[] args) throws Exception {
        String dbPath = args.length > 0 ? args[0] : "../data/inventory.db";
        int port = args.length > 1 ? Integer.parseInt(args[1]) : 8081;

        Database db = new Database(dbPath);
        InventoryService service = new InventoryService(db);

        HttpServer server = HttpServer.create(new InetSocketAddress("127.0.0.1", port), 0);
        server.createContext("/api/items", exchange -> {
            try {
                if ("GET".equals(exchange.getRequestMethod())) {
                    writeJson(exchange, 200, service.getItems());
                } else {
                    writeJson(exchange, 405, Map.of("ok", false, "error", "Method not allowed"));
                }
            } catch (Exception e) {
                writeJson(exchange, 500, Map.of("ok", false, "error", e.getMessage()));
            }
        });
        server.createContext("/api/issues", exchange -> {
            try {
                if ("GET".equals(exchange.getRequestMethod())) {
                    writeJson(exchange, 200, service.getIssues());
                } else {
                    writeJson(exchange, 405, Map.of("ok", false, "error", "Method not allowed"));
                }
            } catch (Exception e) {
                writeJson(exchange, 500, Map.of("ok", false, "error", e.getMessage()));
            }
        });
        server.createContext("/api/add-item", exchange -> handlePost(exchange, service, "add-item"));
        server.createContext("/api/update-stock", exchange -> handlePost(exchange, service, "update-stock"));
        server.createContext("/api/issue", exchange -> handlePost(exchange, service, "issue"));

        server.setExecutor(null);
        server.start();
        System.out.println("Java inventory API on http://127.0.0.1:" + port);
    }

    private static void handlePost(HttpExchange exchange, InventoryService service, String action) throws IOException {
        if (!"POST".equals(exchange.getRequestMethod())) {
            writeJson(exchange, 405, Map.of("ok", false, "error", "Method not allowed"));
            return;
        }
        String body = new String(exchange.getRequestBody().readAllBytes(), StandardCharsets.UTF_8);
        Map<String, Object> data = GSON.fromJson(body, MAP_TYPE);
        if (data == null) data = Map.of();
        try {
            Map<String, Object> result = switch (action) {
                case "add-item" -> service.addItem(
                    str(data, "name"), num(data, "quantity"), num(data, "user_id"));
                case "update-stock" -> service.updateStock(
                    num(data, "item_id"), num(data, "quantity"), num(data, "user_id"));
                case "issue" -> service.issueItems(
                    str(data, "location"), str(data, "issue_date"), num(data, "user_id"),
                    intList(data, "item_ids"), intList(data, "quantities"));
                default -> Map.of("ok", false, "error", "Unknown action");
            };
            writeJson(exchange, 200, result);
        } catch (Exception e) {
            writeJson(exchange, 500, Map.of("ok", false, "error", e.getMessage()));
        }
    }

    private static List<Integer> intList(Map<String, Object> data, String key) {
        Object val = data.get(key);
        if (val instanceof List<?> list) {
            List<Integer> out = new ArrayList<>();
            for (Object o : list) {
                if (o instanceof Number n) out.add(n.intValue());
                else if (o != null) out.add((int) Double.parseDouble(o.toString()));
            }
            return out;
        }
        return List.of();
    }

    private static int num(Map<String, Object> data, String key) {
        Object v = data.get(key);
        if (v instanceof Number n) return n.intValue();
        if (v instanceof String s) return Integer.parseInt(s);
        return 0;
    }

    private static String str(Map<String, Object> data, String key) {
        Object v = data.get(key);
        return v == null ? "" : v.toString();
    }

    private static void writeJson(HttpExchange exchange, int code, Map<String, Object> data) throws IOException {
        String json = GSON.toJson(data);
        exchange.getResponseHeaders().set("Content-Type", "application/json");
        byte[] bytes = json.getBytes(StandardCharsets.UTF_8);
        exchange.sendResponseHeaders(code, bytes.length);
        exchange.getResponseBody().write(bytes);
        exchange.close();
    }

}