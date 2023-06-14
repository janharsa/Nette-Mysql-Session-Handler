<?php declare(strict_types=1);

namespace JanHarsa\Session;

use Nextras\Dbal\Connection;

final class MysqlSessionHandler implements \SessionHandlerInterface
{

    const LOCK_TIMEOUT = 10;
    private $tableName;
    private $connection;
    private $lockName = '';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

    }

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        $this->connection->query("
            CREATE TABLE IF NOT EXISTS `$this->tableName` (
                `id` VARBINARY(128) NOT NULL PRIMARY KEY,
                `time` int UNSIGNED NOT NULL,
                `data` LONGTEXT NOT NULL
            ) COLLATE 'utf8_general_ci';
        ");
    }

    /**
     * Close the session
     * @link https://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function close() : bool
    {
        $this->connection->query("SELECT RELEASE_LOCK('$this->lockName')");
        return true;
    }

    /**
     * Destroy a session
     * @link https://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $session_id The session ID being destroyed.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function destroy($session_id) : bool
    {
        $this->connection->query("DELETE FROM $this->tableName WHERE id = %s", $session_id);
        return true;
    }

    /**
     * Cleanup old sessions
     * @link https://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxlifetime <p>
     * Sessions that have not updated for
     * the last maxlifetime seconds will be removed.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function gc($maxlifetime) : int|false
    {
        $this->connection->query("DELETE FROM $this->tableName WHERE time < %i", (time() - $maxlifetime));
        return true;
    }

    /**
     * Initialize session
     * @link https://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($save_path, $name) : bool
    {
        $this->lockName = 'session_' . session_id();
        $this->connection->query("SELECT GET_LOCK('$this->lockName', %i)", self::LOCK_TIMEOUT);
        return true;
    }

    /**
     * Read session data
     * @link https://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $session_id The session id to read data for.
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function read($session_id) : string|false
    {
        $result = $this->connection->query("SELECT data FROM $this->tableName s WHERE s.id = %s", $session_id);
        if ($row = $result->fetch())
            return $row->data;
        return "";
    }

    /**
     * Write session data
     * @link https://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $session_id The session id.
     * @param string $session_data <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function write($session_id, $session_data) : bool
    {
        $this->connection->query("REPLACE INTO $this->tableName (id, time, data) VALUES (%s, %i, %s)", $session_id, time(), $session_data);
        return true;
    }
}
