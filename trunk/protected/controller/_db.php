<?php
// following file must contain 3 defines for DB access as follows
//
//define('DB_DSN', '<DSN>');  // eg mysql:unix_socket=/var/lib/mysql/mysql.sock;dbname=mydb
//define('DB_USER', '<USER>');
//define('DB_PWD', '<PASSWORD>');
require '../_dbconsts.php';

// static class  to manage a single database connection
class DBCxn {
    const dsn = DB_DSN;
    const user = DB_USER;
    const pwd = DB_PWD;

    const driverOpts = null;
    const errMode = PDO::ERRMODE_EXCEPTION;
    
    // Internal variable to hold the connection
    private static $pdb;
    // No cloning or instantiating allowed
    final private function __construct() { }
    final private function __clone() { }
    
    public static function get() {
        // Connect if not already connected
        if (is_null(self::$pdb)) {
		try 
		{
			self::$pdb = new PDO(self::dsn, self::user, self::pwd, self::driverOpts);
			if (!is_null(self::errMode))
			{
				self::$pdb->setAttribute( PDO::ATTR_ERRMODE, self::errMode );
                self::$pdb->query("SET NAMES 'utf8'");
			}
		}
		catch (PDOException $e) 
		{
//			echo 'Database connection failed: ' . $e->getMessage();
			exit();
			throw new Exception('Database connection failed');
		}
        }
        // Return the connection
        return self::$pdb;
    }
    
    public static function get_enum_values($table, $column)
	{
		try
		{
			// TODo find out home to bindtable name w/o quotes
			$sth=self::$pdb->prepare("SHOW COLUMNS FROM $table LIKE ?");
				    $sth->execute(array($column));
				    $sth->execute();
				    $row = $sth->fetch(PDO::FETCH_ASSOC);
				   preg_match_all("/'(.*?)'/", $row['Type'], $matches);
				   $arryEnum= $matches[1];
				   return $arryEnum;
		 } 
		catch (Exception $e)
		{
			print "Couldn't get enum: " . htmlenc($e->getMessage());
		}
	}
}

?>