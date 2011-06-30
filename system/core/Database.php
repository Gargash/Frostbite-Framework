<?php
/* 
| --------------------------------------------------------------
| 
| Frostbite Framework
|
| --------------------------------------------------------------
|
| Author: 		Steven Wilson
| Copyright:	Copyright (c) 2011, Steven Wilson
| License: 		GNU GPL v3
|
*/
namespace System\Core;

class Database
{
	// Queries statistics.
	public $_statistics = array(
		'time'  => 0,
		'count' => 0,
	);

	// Our connection
	private $mysql;
	
	// Mysql Hostname / Ip
	private $hostname;

	// Mysql Port
	private $port;

	// Mysql Username
	private $user;

	// Mysql Password
	private $pass;

	// Mysql Database Name
	private $database;

	// Our queryType
	public $queryType;

	// Our table
	public $table;

	// result of the last query
	public $result;

	// Our Sql Statement
	protected $sql = '';
	
	// Columns and Vaules of those columns for queries
	protected $columns = array(); 
	protected $values  = array();

/*
| ---------------------------------------------------------------
| Constructer
| ---------------------------------------------------------------
|
| Creates the connection to the mysql database, then selects the
| database.
|
*/
    public function __construct($host, $port, $user, $pass, $name)
    {
		// Fill Atributes
		$this->hostname = $host;
		$this->port = $port;
		$this->user = $user;
		$this->pass = $pass;
		$this->database = $name;
		
		// Connection time
		if(!$this->mysql = mysql_connect($host.":".$port, $user, $pass, true))
		{
			show_error('db_connect_error', array( $host, $port), E_ERROR);
			return FALSE;
		}

		// Select DB
		if(!mysql_select_db($name, $this->mysql))
		{
			show_error('db_select_error', array( $name ), E_ERROR);
			return FALSE;
		}
		return TRUE;
    }

/*
| ---------------------------------------------------------------
| Function: __destruct
| ---------------------------------------------------------------
|
| Closes the database connection
|
*/
    public function __destruct()
    {
        @mysql_close($this->mysql);
    }
 
/*
| ---------------------------------------------------------------
| Function: query()
| ---------------------------------------------------------------
|
| Query function is best used for INSERT and UPDATE functions
|
*/
    public function query($query = FALSE)
    {
		// Check for a manual query
		if($query != FALSE)
		{
			// Add the query to $this->sql, and then process the query
			$this->sql = $query;
			$this->result = mysql_query($query, $this->mysql);
			
			// Check for errors
			if(mysql_errno($this->mysql) !== 0)
			{
				$this->trigger_error();
			}
			
			// Up our statistic count and return the result
			$this->_statistics['count']++;
			return $this->result;
		}
		
		// No custom query, make sure we have a valid sql statment stored
		elseif(empty($this->sql) || empty($this->table))
		{
			show_error('db_emtpy_query', false, E_ERROR);
		}
		
		// Process our query depending on type
		switch($this->queryType)
		{
			case "SELECT":
				$this->fetch_array($this->sql);
				break;
				
			case "COUNT":
				$row = $this->query($this->sql);
				$this->result = mysql_result($row, 0);
				break;
				
			case "UPDATE":
			case "INSERT":
			case "DELETE":
				$this->result = mysql_query($this->sql, $this->mysql);
				
				// Check for errors
				if(mysql_errno($this->mysql) !== 0)
				{
					$this->trigger_error();
				}
				
				// Up our statistic count and return the result
				$this->_statistics['count']++;
				break;
		}
    }
 
/*
| ---------------------------------------------------------------
| Function: fetch_array(query)
| ---------------------------------------------------------------
|
| fetch function is great for getting huge arrays of multiple rows and tables
|
| @Param: $query - the query
|
*/
    public function fetch_array($query)
    {
		// Lets start off with the query
        $sql = mysql_query($query, $this->mysql);
	
		// Check for errors
		if(mysql_errno($this->mysql) !== 0)
		{
			$this->trigger_error();
		}
		
		// Up our statistic count
		$this->_statistics['count']++;
		
		// No rows mean a false to be returned!
		if(mysql_num_rows($sql) == 0)
		{
			$result = FALSE;
		}
		
		// More then 1 row, process as big array
		elseif(mysql_num_rows($sql) > 1)
		{
			$i = 0;
			while($row = mysql_fetch_assoc($sql))
			{
				foreach($row as $colname => $value)
				{
					$result[$i][$colname] = $value;
				}
				$i++;
			}
		}
		
		// Just 1 row to return
		else
		{
			$result = mysql_fetch_array($sql);
		}
		
		// Set result and return
		$this->result = $result;
    }
	
/*
| ---------------------------------------------------------------
| Function: clear_query()
| ---------------------------------------------------------------
|
| clears out the query. Not really needed to be honest as a new
| query will automatically call this method.
|
*/
    public function clear_query()
    {
		$this->sql = '';
		$this->columns = array();
		$this->values = array();
    }
	
/*
| ---------------------------------------------------------------
| Function: result()
| ---------------------------------------------------------------
|
| Retunrs the result of the last query
|
*/
    public function result()
    {
		return $this->result;
    }

/*
| ---------------------------------------------------------------
| Function: get_insert_id(query)
| ---------------------------------------------------------------
|
| The equivelant to mysql_insert_id(); This functions get the last
| primary key from a previous insert
|
| @Param: $query - the query
|
*/
	public function get_insert_id()
	{
		return mysql_insert_id();
	}
	
/*
| ---------------------------------------------------------------
| Function: affected_rows()
| ---------------------------------------------------------------
|
| The equivelant to mysql_affected_rows();
|
*/
	public function affected_rows()
	{
		return mysql_affected_rows();
	}
	
/*
| ---------------------------------------------------------------
| Function: num_rows()
| ---------------------------------------------------------------
|
| The equivelant to mysql_num_rows();
|
*/
	public function num_rows()
	{
		return mysql_num_rows();
	}
	
/*
| ---------------------------------------------------------------
| Function: trigger_error()
| ---------------------------------------------------------------
|
| Trigger a Core error using Mysql custom error message
|
*/

	function trigger_error() 
	{
		$msg  = mysql_error($this->mysql) . "<br /><br />";
		$msg .= "<b>MySql Error No:</b> ". mysql_errno($this->mysql) ."<br />";
		$msg .= '<b>Query String:</b> ' . $this->sql;
		show_error($msg, false, E_ERROR);
	}
	
	
/*
|----------------------------------------------------------------
| 				START OF QUERY BUILDING METHODS
|----------------------------------------------------------------
*/


/*
| ---------------------------------------------------------------
| Function: select()
| ---------------------------------------------------------------
|
| select is used to initiate a SELECT query
|
| @Param: $data - the columns being selected
|
*/
	public function select($data) 
	{	
		// Empty out the old junk
		$this->clear_query();
		
		// Define our query type
		$this->queryType = "SELECT";
		if(is_array($data))
		{
			if(count($data) > 1)
			{
				$this->sql = "SELECT ". mysql_real_escape_string( implode(',', $data) );
			}
			else
			{
				$this->sql = "SELECT ". mysql_real_escape_string($data[0]);
			}
		}
		else
		{
			$this->sql = "SELECT ". mysql_real_escape_string($data);
		}
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: select_max()
| ---------------------------------------------------------------
|
| select_max is used to initiate a SELECT MAX($col) query
|
| @Param: $col - the columns being selected
|
*/
	public function select_max($col) 
	{
		$col = mysql_real_escape_string($col);
		
		// Empty out the old junk
		$this->clear_query();
		
		// Define our query type
		$this->queryType = "COUNT";
		$this->sql = "SELECT MAX(". $col .")";
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: select_min()
| ---------------------------------------------------------------
|
| select_min is used to initiate a SELECT MIN($col) query
|
| @Param: $col - the columns being selected
|
*/
	public function select_min($col) 
	{
		$col = mysql_real_escape_string($col);
		
		// Empty out the old junk
		$this->clear_query();
		
		// Define our query type
		$this->queryType = "COUNT";
		$this->sql = "SELECT MIN(". $col .")";
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: select_avg()
| ---------------------------------------------------------------
|
| select_avg is used to initiate a SELECT AVG($col) query
|
| @Param: $col - the columns being selected
|
*/
	public function select_avg($col) 
	{
		$col = mysql_real_escape_string($col);
		
		// Empty out the old junk
		$this->clear_query();
		
		// Define our query type
		$this->queryType = "COUNT";
		$this->sql = "SELECT AVG(". $col .")";
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: select_sum()
| ---------------------------------------------------------------
|
| select_sum is used to initiate a SELECT SUM($col) query
|
| @Param: $col - the columns being selected
|
*/
	public function select_sum($col) 
	{
		$col = mysql_real_escape_string($col);
		
		// Empty out the old junk
		$this->clear_query();
		
		// Define our query type
		$this->queryType = "COUNT";
		$this->sql = "SELECT SUM(". $col .")";
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: select_count()
| ---------------------------------------------------------------
|
| select_sum is used to initiate a SELECT COUNT($col) query
|
| @Param: $col - the columns being selected
|
*/
	public function select_count($col) 
	{
		$col = mysql_real_escape_string($col);
		
		// Empty out the old junk
		$this->clear_query();
		
		// Define our query type
		$this->queryType = "COUNT";
		$this->sql = "SELECT COUNT(". $col .")";
		return $this;
	}

/*
| ---------------------------------------------------------------
| Function: insert()
| ---------------------------------------------------------------
|
| insert is used to initiate an INSERT query
|
| @Param: $table - the table we are inserting into
| @Param: $data - an array of ( column => value )
|
*/	
	public function insert($table, $data) 
	{
		// Empty out the old junk
		$this->clear_query();
		
		// Define our query type
		$this->queryType = "INSERT";
		$this->table = mysql_real_escape_string($table);
		
		// Make sure our data is in an array format
		if(!is_array($data))
		{
			show_error(2, 'non_array', array('data', 'Database::insert'));
			$data = array();
		}
		
		// Loop through if we need to
		if(count($data) > 1)
		{
			foreach($data as $key => $value)
			{
				// Check to see if the key is numeric, if not, then escape it
				if(!is_numeric($key))
				{
					$this->columns[] = mysql_real_escape_string($key);
				}
				
				// Also Check to see if the value is numeric, if not, add quotes around the value
				if(!is_numeric($value))
				{
					$this->values[] = "'". mysql_real_escape_string($value) ."'";
				}
				else
				{
					$this->values[] = mysql_real_escape_string($value);
				}
			}
			
			// If we entered columns, then we use them, otherwise we do a plain insert
			if(count($this->columns) >= 1)
			{
				$this->sql = "INSERT INTO ". $table ." (". implode(',', $this->columns) .") VALUES (". implode(',', $this->values) .")";
			}
			else
			{
				$this->sql = "INSERT INTO ". $table ." VALUES (". implode(',', $this->values) .")";
			}
		}
		
		// No Loop needed, a simple insert of 1 key / value
		else
		{
			$key = mysql_real_escape_string( key($data) );
			$value = mysql_real_escape_string($data[$key]);
			$this->sql = "INSERT INTO ". $this->table ." (". $key .") VALUES (". $value.")";
		}
		return $this;
	}

/*
| ---------------------------------------------------------------
| Function: update()
| ---------------------------------------------------------------
|
| Update is used to initiate an UPDATE query
|
| @Param: $table - the table we are updating
| @Param: $data - an array of ( column => value )
|
*/	
	public function update($table, $data) 
	{
		// Empty out the old junk
		$this->clear_query();
		
		// Define our query type
		$this->queryType = "UPDATE";
		$this->table = mysql_real_escape_string($table);
		
		// Make sure our data is in an array format
		if(!is_array($data))
		{
			show_error(2, 'non_array', array('data', 'Database::update'));
			$data = array();
		}
		
		// Add the column and values to 2 seperate arrays
		if(count($data) > 1)
		{
			foreach($data as $key => $value)
			{
				$this->columns[] = mysql_real_escape_string($key);
				$this->values[] = mysql_real_escape_string($value);
			}
		}
		else
		{
			$key = key($data);
			$this->columns[] = mysql_real_escape_string( $key );
			$this->values[] = mysql_real_escape_string( $data[$key] );
		}
		
		// Init the SQL statement
		$this->sql = "UPDATE ". $this->table ." SET ";
	
		// Start the loop of $keys = $values
		$count = count($this->columns);
		for($i = 0; $i < $count; $i++) 
		{
			// If the number is numeric, we do not add single quotes to the value
			if(is_numeric($this->values[$i]))
			{
				$this->sql .= "`".$this->columns[$i] ."` = ". $this->values[$i];
			}
			else
			{
				$this->sql .= "`".$this->columns[$i] ."` = '". $this->values[$i] ."'";
			}
			
			// If we have more to go, add a ","
			if($i < ($count - 1)) 
			{
				$this->sql.= ", ";
			}
		}
		return $this;
	}

/*
| ---------------------------------------------------------------
| Function: delete_from()
| ---------------------------------------------------------------
|
| delete is used to delete from a table
|
| @Param: $table - the table we are deleting data from
|
*/	
	public function delete_from($table) 
	{
		// Empty out the old junk
		$this->clear_query();
		
		// Define our query type
		$this->queryType = "DELETE";
		$this->table = mysql_real_escape_string($table);
		$this->sql = "DELETE FROM ". $this->table;
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: where()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "WHERE $col = $val" to the query being built
|
| @Param: $col - the column
| @Param: $val - value of the column
|
*/
	public function where($col, $val) 
	{
		$col = mysql_real_escape_string($col);
		$val = mysql_real_escape_string($val);
		
		if(!is_numeric($val))
		{
			$val = "'". $val ."'";
		}
		$this->sql .= " WHERE ". $col ." = ". $val;	
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: and_where()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "AND $col = $val" to the query being built
|
| @Param: $col - the column
| @Param: $val - value of the column
|
*/
	public function and_where($col, $val) 
	{
		$col = mysql_real_escape_string($col);
		$val = mysql_real_escape_string($val);
		
		if(!is_numeric($val))
		{
			$val = "'". $val ."'";
		}
		$this->sql .= " AND ". $col ." = ". $val;	
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: or_where()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "OR $col = $val" to the query being built
|
| @Param: $col - the column
| @Param: $val - value of the column
|
*/
	public function or_where($col, $val) 
	{
		$col = mysql_real_escape_string($col);
		$val = mysql_real_escape_string($val);
		
		if(!is_numeric($val))
		{
			$val = "'". $val ."'";
		}
		$this->sql .= " OR ". $col ." = ". $val;	
		return $this;
	}

/*
| ---------------------------------------------------------------
| Function: from()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "FROM $table" to the query being built
|
| @Param: $table - the table name
|
*/	
	public function from($table) 
	{
		$this->table = mysql_real_escape_string($table);
		$this->sql .= " FROM ". $this->table;
		return $this;		
	}
	
/*
| ---------------------------------------------------------------
| Function: where_like()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "WHERE $col LIKE $like" to the query being built
|
| @Param: $col- the column we are selecting
| @Param: $like - what we are comparing to
|
*/	
	public function where_like($col, $like) 
	{
		$like = mysql_real_escape_string($like);
		$col = mysql_real_escape_string($col);
		
		$this->sql .=  " WHERE". $col ." LIKE ". $like;
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: not_like()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "WHERE $col NOT LIKE $like" to the query being built
|
| @Param: $col- the column we are selecting
| @Param: $like - what we are comparing to
|
*/	
	public function where_not_like($col, $like) 
	{
		$like = mysql_real_escape_string($like);
		$col = mysql_real_escape_string($col);
		
		$this->sql .= "WHERE ". $col ." NOT LIKE ". $like;
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: and_like()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "AND $sub LIKE $like" to the query being built
|
| @Param: $like - what we are comparing to
|
*/	
	public function and_like($sub, $like) 
	{
		$sub = mysql_real_escape_string($sub);
		$like = mysql_real_escape_string($like);
		
		$this->sql .= " AND ". $sub ." LIKE ". $like;
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: and_not_like()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "AND $sub NOT LIKE $like" to the query being built
|
| @Param: $like - what we are comparing to
|
*/	
	public function and_not_like($sub, $like) 
	{
		$sub = mysql_real_escape_string($sub);
		$like = mysql_real_escape_string($like);
		
		$this->sql .= " AND ". $sub ." NOT LIKE ". $like;
		return $this;
	}
	
/*
| ---------------------------------------------------------------
| Function: groupBy()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "GROUP BY $groupby" to the query being built
|
| @Param: $groupBy - What we are grouping by
|
*/	
	public function groupBy($groupBy, $type) 
	{
		$this->sql .= " GROUP BY ". mysql_real_escape_string($groupBy);
		return $this;
	}

/*
| ---------------------------------------------------------------
| Function: having()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "HAVING $having" to the query being built
|
| @Param: $having - what the table needs to have
|
*/	
	public function having($having) 
	{
		$this->sql .= " HAVING ". mysql_real_escape_string($having);
		return $this;
	}

/*
| ---------------------------------------------------------------
| Function: orderBy()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "ORDER BY $orderBy" to the query being built
|
| @Param: $orderBy - How we are ording the result
| @Param: $type - How we order, for example: ASC, or DESC
|
*/	
	public function orderBy($orderBy, $type = 'ASC') 
	{
		$order = mysql_real_escape_string($orderBy);
		$type = mysql_real_escape_string($type);
		
		$this->sql .= " ORDER BY ". $order ." ". $type ;
		return $this;
	}

/*
| ---------------------------------------------------------------
| Function: limit()
| ---------------------------------------------------------------
|
| Querybuilder: Adds "LIMIT $limit" to the query being built
|
| @Param: $x - the Limit
| @Param: $y - the result number to start on
|
*/
	public function limit($x, $y = 0) 
	{
		$x = mysql_real_escape_string($x);
		$y = mysql_real_escape_string($y);
			
		$this->sql .= " LIMIT ". $y .",". $x;
		return $this;
	}
}
// EOF