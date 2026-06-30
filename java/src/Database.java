import java.sql.*;
import java.nio.file.Path;

public class Database {
    private final String url;

    public Database(String dbPath) {
        try {
            Class.forName("org.sqlite.JDBC");
        } catch (ClassNotFoundException e) {
            throw new RuntimeException("SQLite JDBC driver missing", e);
        }
        this.url = "jdbc:sqlite:" + Path.of(dbPath).toAbsolutePath();
    }

    public Connection connect() throws SQLException {
        return DriverManager.getConnection(url);
    }
}