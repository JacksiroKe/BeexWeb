<?php
/*
	AppSmata by AppSmata Sol.
	http://www.appsmata.org/

	Description: Common functions for connecting to and accessing database


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.appsmata.org/license.php
*/

if (!defined('AS_VERSION')) { // don't allow this page to be requested directly from browser
	header('Location: ../');
	exit;
}


/**
 * Indicates to the APS database layer that database connections are permitted fro this point forwards
 * (before this point, some plugins may not have had a chance to override some database access functions).
 */
function as_db_allow_connect()
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	global $as_db_allow_connect;

	$as_db_allow_connect = true;
}


/**
 * Connect to the APS database, select the right database, optionally install the $failhandler (and call it if necessary).
 * Uses mysqli as of APS 1.7.
 * @param null $failhandler
 * @return mixed|void
 */
function as_db_connect($failhandler = null)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	global $as_db_connection, $as_db_fail_handler, $as_db_allow_connect;

	if (!$as_db_allow_connect)
		as_fatal_error('It appears that a plugin is trying to access the database, but this is not allowed until APS initialization is complete.');

	if (isset($failhandler))
		$as_db_fail_handler = $failhandler; // set this even if connection already opened

	if ($as_db_connection instanceof mysqli)
		return;

	$host = AS_FINAL_MYSQL_HOSTNAME;
	$port = null;

	if (defined('AS_FINAL_WORDPRESS_INTEGRATE_PATH')) {
		// Wordpress allows setting port inside DB_HOST constant, like 127.0.0.1:3306
		$host_and_port = explode(':', $host);
		if (count($host_and_port) >= 2) {
			$host = $host_and_port[0];
			$port = $host_and_port[1];
		}
	} elseif (defined('AS_FINAL_MYSQL_PORT')) {
		$port = AS_FINAL_MYSQL_PORT;
	}

	if (AS_PERSISTENT_CONN_DB)
		$host = 'p:' . $host;

	// in mysqli we connect and select database in constructor
	if ($port !== null)
		$db = new mysqli($host, AS_FINAL_MYSQL_USERNAME, AS_FINAL_MYSQL_PASSWORD, AS_FINAL_MYSQL_DATABASE, $port);
	else
		$db = new mysqli($host, AS_FINAL_MYSQL_USERNAME, AS_FINAL_MYSQL_PASSWORD, AS_FINAL_MYSQL_DATABASE);

	// must use procedural `mysqli_connect_error` here prior to 5.2.9
	$conn_error = mysqli_connect_error();
	if ($conn_error)
		as_db_fail_error('connect', $db->connect_errno, $conn_error);

	// From APS 1.5, we explicitly set the character encoding of the MySQL connection, instead of using lots of "SELECT BINARY col"-style queries.
	// Testing showed that overhead is minimal, so this seems worth trading off against the benefit of more straightforward queries, especially
	// for plugin developers.
	if (!$db->set_charset('utf8'))
		as_db_fail_error('set_charset', $db->errno, $db->error);

	as_report_process_stage('db_connected');

	$as_db_connection = $db;
}


/**
 * If a DB error occurs, call the installed fail handler (if any) otherwise report error and exit immediately.
 * @param $type
 * @param int $errno
 * @param string $error
 * @param string $query
 * @return mixed
 */
function as_db_fail_error($type, $errno = null, $error = null, $query = null)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	global $as_db_fail_handler;

	@error_log('PHP AppSmata MySQL ' . $type . ' error ' . $errno . ': ' . $error . (isset($query) ? (' - Query: ' . $query) : ''));

	if (function_exists($as_db_fail_handler))
		$as_db_fail_handler($type, $errno, $error, $query);
	else {
		echo sprintf(
			'<hr><div style="color: red">Database %s<p>%s</p><code>%s</code></div>',
			htmlspecialchars($type . ' error ' . $errno), nl2br(htmlspecialchars($error)), nl2br(htmlspecialchars($query))
		);
		as_exit('error');
	}
}


/**
 * Return the current connection to the APS database, connecting if necessary and $connect is true.
 * @param bool $connect
 * @return mixed
 */
function as_db_connection($connect = true)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	global $as_db_connection;

	if ($connect && !($as_db_connection instanceof mysqli)) {
		as_db_connect();

		if (!($as_db_connection instanceof mysqli))
			as_fatal_error('Failed to connect to database');
	}

	return $as_db_connection;
}


/**
 * Disconnect from the APS database.
 */
function as_db_disconnect()
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	global $as_db_connection;

	if ($as_db_connection instanceof mysqli) {
		as_report_process_stage('db_disconnect');

		if (!AS_PERSISTENT_CONN_DB) {
			if (!$as_db_connection->close())
				as_fatal_error('Database disconnect failed');
		}

		$as_db_connection = null;
	}
}


/**
 * Run the raw $query, call the global failure handler if necessary, otherwise return the result resource.
 * If appropriate, also track the resources used by database queries, and the queries themselves, for performance debugging.
 * @param $query
 * @return mixed
 */
function as_db_query_raw($query)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	if (AS_DEBUG_PERFORMANCE) {
		global $as_usage;

		// time the query
		$oldtime = array_sum(explode(' ', microtime()));
		$result = as_db_query_execute($query);
		$usedtime = array_sum(explode(' ', microtime())) - $oldtime;

		// fetch counts
		$gotrows = $gotcolumns = null;
		if ($result instanceof mysqli_result) {
			$gotrows = $result->num_rows;
			$gotcolumns = $result->field_count;
		}

		$as_usage->logDatabaseQuery($query, $usedtime, $gotrows, $gotcolumns);
	} else
		$result = as_db_query_execute($query);

	// @error_log('AppSmata MySQL query: '.$query);

	if ($result === false) {
		$db = as_db_connection();
		as_db_fail_error('query', $db->errno, $db->error, $query);
	}

	return $result;
}


/**
 * Lower-level function to execute a query, which automatically retries if there is a MySQL deadlock error.
 * @param $query
 * @return mixed
 */
function as_db_query_execute($query)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	$db = as_db_connection();

	for ($attempt = 0; $attempt < 100; $attempt++) {
		$result = $db->query($query);

		if ($result === false && $db->errno == 1213)
			usleep(10000); // deal with InnoDB deadlock errors by waiting 0.01s then retrying
		else
			break;
	}

	return $result;
}


/**
 * Return $string escaped for use in queries to the APS database (to which a connection must have been made).
 * @param $string
 * @return mixed
 */
function as_db_escape_string($string)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	$db = as_db_connection();
	return $db->real_escape_string($string);
}


/**
 * Return $argument escaped for MySQL. Add quotes around it if $alwaysquote is true or it's not numeric.
 * If $argument is an array, return a comma-separated list of escaped elements, with or without $arraybrackets.
 * @param $argument
 * @param $alwaysquote
 * @param bool $arraybrackets
 * @return mixed|string
 */
function as_db_argument_to_mysql($argument, $alwaysquote, $arraybrackets = false)
{
	if (is_array($argument)) {
		$parts = array();

		foreach ($argument as $subargument)
			$parts[] = as_db_argument_to_mysql($subargument, $alwaysquote, true);

		if ($arraybrackets)
			$result = '(' . implode(',', $parts) . ')';
		else
			$result = implode(',', $parts);

	} elseif (isset($argument)) {
		if ($alwaysquote || !is_numeric($argument))
			$result = "'" . as_db_escape_string($argument) . "'";
		else
			$result = as_db_escape_string($argument);
	} else
		$result = 'NULL';

	return $result;
}


/**
 * Return the full name (with prefix) of database table $rawname, usually if it used after a ^ symbol.
 * @param $rawname
 * @return string
 */
function as_db_add_table_prefix($rawname)
{
	if (as_to_override(__FUNCTION__)) { $args=func_get_args(); return as_call_override(__FUNCTION__, $args); }

	$prefix = AS_MYSQL_TABLE_PREFIX;

	if (defined('AS_MYSQL_USERS_PREFIX')) {
		switch (strtolower($rawname)) {
			case 'users':
			case 'usersignins':
			case 'userprofile':
			case 'userfields':
			case 'messages':
			case 'cookies':
			case 'blobs':
			case 'cache':
			case 'usersignins_ibfk_1': // also special cases for constraint names
			case 'userprofile_ibfk_1':
				$prefix = AS_MYSQL_USERS_PREFIX;
				break;
		}
	}

	return $prefix . $rawname;
}


/**
 * Callback function to add table prefixes, as used in as_db_apply_sub().
 * @param $matches
 * @return string
 */
function as_db_prefix_callback($matches)
{
	return as_db_add_table_prefix($matches[1]);
}


/**
 * Substitute ^, $ and # symbols in $query. ^ symbols are replaced with the table prefix set in as-config.php.
 * $ and # symbols are replaced in order by the corresponding element in $arguments (if the element is an array,
 * it is converted recursively into comma-separated list). Each element in $arguments is escaped.
 * $ is replaced by the argument in quotes (even if it's a number), # only adds quotes if the argument is non-numeric.
 * It's important to use $ when matching a textual column since MySQL won't use indexes to compare text against numbers.
 * @param $query
 * @param $arguments
 * @return mixed
 */
function as_db_apply_sub($query, $arguments)
{
	$query = preg_replace_callback('/\^([A-Za-z_0-9]+)/', 'as_db_prefix_callback', $query);

	if (!is_array($arguments))
		return $query;

	$countargs = count($arguments);
	$offset = 0;

	for ($argument = 0; $argument < $countargs; $argument++) {
		$stringpos = strpos($query, '$', $offset);
		$numberpos = strpos($query, '#', $offset);

		if ($stringpos === false || ($numberpos !== false && $numberpos < $stringpos)) {
			$alwaysquote = false;
			$position = $numberpos;
		} else {
			$alwaysquote = true;
			$position = $stringpos;
		}

		if (!is_numeric($position))
			as_fatal_error('Insufficient parameters in query: ' . $query);

		$value = as_db_argument_to_mysql($arguments[$argument], $alwaysquote);
		$query = substr_replace($query, $value, $position, 1);
		$offset = $position + strlen($value); // allows inserting strings which contain #/$ character
	}

	return $query;
}


/**
 * Run $query after substituting ^, # and $ symbols, and return the result resource (or call fail handler).
 * @param string $query
 * @return mixed
 */
function as_db_query_sub($query) // arguments for substitution retrieved using func_get_args()
{
	$funcargs = func_get_args();

	return as_db_query_sub_params($query, array_slice($funcargs, 1));
}

/**
 * Run $query after substituting ^, # and $ symbols, and return the result resource (or call fail handler).
 * Query parameters are passed as an array.
 * @param string $query
 * @param array $params
 * @return mixed
 */
function as_db_query_sub_params($query, $params)
{
	return as_db_query_raw(as_db_apply_sub($query, $params));
}


/**
 * Return the number of rows in $result. (Simple wrapper for mysqli_result::num_rows.)
 * @param $result
 * @return int
 */
function as_db_num_rows($result)
{
	if ($result instanceof mysqli_result)
		return $result->num_rows;

	return 0;
}


/**
 * Return the value of the auto-increment column for the last inserted row.
 */
function as_db_last_insert_id()
{
	$db = as_db_connection();
	return $db->insert_id;
}


/**
 * Return the number of rows affected by the last query.
 */
function as_db_affected_rows()
{
	$db = as_db_connection();
	return $db->affected_rows;
}


/**
 * For the previous INSERT ... ON DUPLICATE KEY UPDATE query, return whether an insert operation took place.
 */
function as_db_insert_on_duplicate_inserted()
{
	return (as_db_affected_rows() == 1);
}


/**
 * Return a random integer (as a string) for use in a BIGINT column.
 * Actual limit is 18,446,744,073,709,551,615 - we aim for 18,446,743,999,999,999,999.
 */
function as_db_random_bigint()
{
	return sprintf('%d%06d%06d', mt_rand(1, 18446743), mt_rand(0, 999999), mt_rand(0, 999999));
}


/**
 * Return an array of the names of all tables in the APS database, converted to lower case.
 * No longer used by APS and shouldn't be needed.
 */
function as_db_list_tables_lc()
{
	return array_map('strtolower', as_db_list_tables());
}


/**
 * Return an array of the names of all tables in the APS database.
 *
 * @param bool $onlyTablesWithPrefix Determine if the result should only include tables with the
 * AS_MYSQL_TABLE_PREFIX or if it should include all tables in the database.
 * @return array
 */
function as_db_list_tables($onlyTablesWithPrefix = false)
{
	$query = 'SHOW TABLES';

	if ($onlyTablesWithPrefix) {
		$col = 'Tables_in_' . AS_FINAL_MYSQL_DATABASE;
		$query .= ' WHERE `' . $col . '` LIKE "' . str_replace('_', '\\_', AS_MYSQL_TABLE_PREFIX) . '%"';
		if (defined('AS_MYSQL_USERS_PREFIX')) {
			$query .= ' OR `' . $col . '` LIKE "' . str_replace('_', '\\_', AS_MYSQL_USERS_PREFIX) . '%"';
		}
	}

	return as_db_read_all_values(as_db_query_raw($query));
}


/*
	The selectspec array can contain the elements below. See db/selects.php for lots of examples.

	By default, as_db_single_select() and as_db_multi_select() return the data for each selectspec as a numbered
	array of arrays, one per row. The array for each row has column names in the keys, and data in the values.
	But this can be changed using the 'arraykey', 'arrayvalue' and 'single' in the selectspec.

	Note that even if you specify ORDER BY in 'source', the final results may not be ordered. This is because
	the SELECT could be done within a UNION that (annoyingly) doesn't maintain order. Use 'sortasc' or 'sortdesc'
	to fix this. You can however rely on the combination of ORDER BY and LIMIT retrieving the appropriate records.


	'columns' => Array of names of columns to be retrieved (required)

		If a value in the columns array has an integer key, it is retrieved AS itself (in a SQL sense).
		If a value in the columns array has a non-integer key, it is retrieved AS that key.
		Values in the columns array can include table specifiers before the period.

	'source' => Any SQL after FROM, including table names, JOINs, GROUP BY, ORDER BY, WHERE, etc... (required)

	'arguments' => Substitutions in order for $s and #s in the query, applied in as_db_apply_sub() above (required)

	'arraykey' => Name of column to use for keys of the outer-level returned array, instead of numbers by default

	'arrayvalue' => Name of column to use for values of outer-level returned array, instead of arrays by default

	'single' => If true, return the array for a single row and don't embed it within an outer-level array

	'sortasc' => Sort the output ascending by this column

	'sortdesc' => Sort the output descending by this column


	Why does as_db_multi_select() combine usually unrelated SELECT statements into a single query?

	Because if the database and web servers are on different computers, there will be latency.
	This way we ensure that every read pageview on the site requires as few DB queries as possible, so
	that we pay for this latency only one time.

	For writes we worry less, since the user is more likely to be expecting a delay.

	If AS_OPTIMIZE_DISTANT_DB is set to false in as-config.php, we assume zero latency and go back to
	simple queries, since this will allow both MySQL and PHP to provide quicker results.
*/


/**
 * Return the data specified by a single $selectspec - see long comment above.
 * @param $selectspec
 * @return array|mixed
 */
function as_db_single_select($selectspec)
{
	// check for cached results
	if (isset($selectspec['caching'])) {
		$cacheDriver = APS_Storage_CacheFactory::getCacheDriver();
		$cacheKey = 'query:' . $selectspec['caching']['key'];

		if ($cacheDriver->isEnabled()) {
			$queryData = $cacheDriver->get($cacheKey);
			if ($queryData !== null)
				return $queryData;
		}
	}

	$query = 'SELECT ';

	foreach ($selectspec['columns'] as $columnas => $columnfrom) {
		$query .= is_int($columnas) ? "$columnfrom, " : "$columnfrom AS `$columnas`, ";
	}

	$results = as_db_read_all_assoc(as_db_query_raw(as_db_apply_sub(
			substr($query, 0, -2) . (strlen(@$selectspec['source']) ? (' FROM ' . $selectspec['source']) : ''),
			@$selectspec['arguments'])
	), @$selectspec['arraykey']); // arrayvalue is applied in as_db_post_select()

	as_db_post_select($results, $selectspec); // post-processing

	// save cached results
	if (isset($selectspec['caching'])) {
		if ($cacheDriver->isEnabled()) {
			$cacheDriver->set($cacheKey, $results, $selectspec['caching']['ttl']);
		}
	}

	return $results;
}


/**
 * Return the data specified by each element of $selectspecs, where the keys of the
 * returned array match the keys of the supplied $selectspecs array. See long comment above.
 * @param array $selectspecs
 * @return array
 */
function as_db_multi_select($selectspecs)
{
	if (!count($selectspecs))
		return array();

	// Perform simple queries if the database is local or there are only 0 or 1 selectspecs

	if (!AS_OPTIMIZE_DISTANT_DB || (count($selectspecs) <= 1)) {
		$outresults = array();

		foreach ($selectspecs as $selectkey => $selectspec)
			$outresults[$selectkey] = as_db_single_select($selectspec);

		return $outresults;
	}

	// Otherwise, parse columns for each spec to deal with columns without an 'AS' specification

	foreach ($selectspecs as $selectkey => $selectspec) {
		$selectspecs[$selectkey]['outcolumns'] = array();
		$selectspecs[$selectkey]['autocolumn'] = array();

		foreach ($selectspec['columns'] as $columnas => $columnfrom) {
			if (is_int($columnas)) {
				$periodpos = strpos($columnfrom, '.');
				$columnas = is_numeric($periodpos) ? substr($columnfrom, $periodpos + 1) : $columnfrom;
				$selectspecs[$selectkey]['autocolumn'][$columnas] = true;
			}

			if (isset($selectspecs[$selectkey]['outcolumns'][$columnas]))
				as_fatal_error('Duplicate column name in as_db_multi_select()');

			$selectspecs[$selectkey]['outcolumns'][$columnas] = $columnfrom;
		}

		if (isset($selectspec['arraykey']))
			if (!isset($selectspecs[$selectkey]['outcolumns'][$selectspec['arraykey']]))
				as_fatal_error('Used arraykey not in columns in as_db_multi_select()');

		if (isset($selectspec['arrayvalue']))
			if (!isset($selectspecs[$selectkey]['outcolumns'][$selectspec['arrayvalue']]))
				as_fatal_error('Used arrayvalue not in columns in as_db_multi_select()');
	}

	// Work out the full list of columns used

	$outcolumns = array();
	foreach ($selectspecs as $selectspec)
		$outcolumns = array_unique(array_merge($outcolumns, array_keys($selectspec['outcolumns'])));

	// Build the query based on this full list

	$query = '';
	foreach ($selectspecs as $selectkey => $selectspec) {
		$subquery = "(SELECT '" . as_db_escape_string($selectkey) . "'" . (empty($query) ? ' AS selectkey' : '');

		foreach ($outcolumns as $columnas) {
			$subquery .= ', ' . (isset($selectspec['outcolumns'][$columnas]) ? $selectspec['outcolumns'][$columnas] : 'NULL');

			if (empty($query) && !isset($selectspec['autocolumn'][$columnas]))
				$subquery .= ' AS ' . $columnas;
		}

		if (strlen(@$selectspec['source']))
			$subquery .= ' FROM ' . $selectspec['source'];

		$subquery .= ')';

		if (strlen($query))
			$query .= ' UNION ALL ';

		$query .= as_db_apply_sub($subquery, @$selectspec['arguments']);
	}

	// Perform query and extract results

	$rawresults = as_db_read_all_assoc(as_db_query_raw($query));

	$outresults = array();
	foreach ($selectspecs as $selectkey => $selectspec)
		$outresults[$selectkey] = array();

	foreach ($rawresults as $rawresult) {
		$selectkey = $rawresult['selectkey'];
		$selectspec = $selectspecs[$selectkey];

		$keepresult = array();
		foreach ($selectspec['outcolumns'] as $columnas => $columnfrom)
			$keepresult[$columnas] = $rawresult[$columnas];

		if (isset($selectspec['arraykey']))
			$outresults[$selectkey][$keepresult[$selectspec['arraykey']]] = $keepresult;
		else
			$outresults[$selectkey][] = $keepresult;
	}

	// Post-processing to apply various stuff include sorting request, since we can't rely on ORDER BY due to UNION

	foreach ($selectspecs as $selectkey => $selectspec)
		as_db_post_select($outresults[$selectkey], $selectspec);

	// Return results

	return $outresults;
}


/**
 * Post-process $outresult according to $selectspec, applying 'sortasc', 'sortdesc', 'arrayvalue' and 'single'.
 * @param array $outresult
 * @param array $selectspec
 */
function as_db_post_select(&$outresult, $selectspec)
{
	// PHP's sorting algorithm is not 'stable', so we use '_order_' element to keep stability.
	// By contrast, MySQL's ORDER BY does seem to give the results in a reliable order.

	if (isset($selectspec['sortasc'])) {
		require_once AS_INCLUDE_DIR . 'util/sort.php';

		$index = 0;
		foreach ($outresult as $key => $value)
			$outresult[$key]['_order_'] = $index++;

		as_sort_by($outresult, $selectspec['sortasc'], '_order_');

	} elseif (isset($selectspec['sortdesc'])) {
		require_once AS_INCLUDE_DIR . 'util/sort.php';

		if (isset($selectspec['sortdesc_2']))
			as_sort_by($outresult, $selectspec['sortdesc'], $selectspec['sortdesc_2']);

		else {
			$index = count($outresult);
			foreach ($outresult as $key => $value)
				$outresult[$key]['_order_'] = $index--;

			as_sort_by($outresult, $selectspec['sortdesc'], '_order_');
		}

		$outresult = array_reverse($outresult, true);
	}

	if (isset($selectspec['arrayvalue']))
		foreach ($outresult as $key => $value)
			$outresult[$key] = $value[$selectspec['arrayvalue']];

	if (@$selectspec['single'])
		$outresult = count($outresult) ? reset($outresult) : null;
}


/**
 * Return the full results from the $result resource as an array. The key of each element in the returned array
 * is from column $key if specified, otherwise it's integer. The value of each element in the returned array
 * is from column $value if specified, otherwise it's a named array of all columns, given an array of arrays.
 * @param $result
 * @param string $key
 * @param mixed $value
 * @return array
 */
function as_db_read_all_assoc($result, $key = null, $value = null)
{
	if (!($result instanceof mysqli_result))
		as_fatal_error('Reading all assoc from invalid result');

	$assocs = array();

	while ($assoc = $result->fetch_assoc()) {
		if (isset($key))
			$assocs[$assoc[$key]] = isset($value) ? $assoc[$value] : $assoc;
		else
			$assocs[] = isset($value) ? $assoc[$value] : $assoc;
	}

	return $assocs;
}


/**
 * Return the first row from the $result resource as an array of [column name] => [column value].
 * If there's no first row, throw a fatal error unless $allowempty is true.
 * @param $result
 * @param bool $allowempty
 * @return array|null
 */
function as_db_read_one_assoc($result, $allowempty = false)
{
	if (!($result instanceof mysqli_result))
		as_fatal_error('Reading one assoc from invalid result');

	$assoc = $result->fetch_assoc();

	if (is_array($assoc))
		return $assoc;

	if ($allowempty)
		return null;
	else
		as_fatal_error('Reading one assoc from empty results');
}


/**
 * Return a numbered array containing the first (and presumably only) column from the $result resource.
 * @param $result
 * @return array
 */
function as_db_read_all_values($result)
{
	if (!($result instanceof mysqli_result))
		as_fatal_error('Reading column from invalid result');

	$output = array();

	while ($row = $result->fetch_row())
		$output[] = $row[0];

	return $output;
}


/**
 * Return the first column of the first row (and presumably only cell) from the $result resource.
 * If there's no first row, throw a fatal error unless $allowempty is true.
 * @param $result
 * @param bool $allowempty
 * @return mixed|null
 */
function as_db_read_one_value($result, $allowempty = false)
{
	if (!($result instanceof mysqli_result))
		as_fatal_error('Reading one value from invalid result');

	$row = $result->fetch_row();

	if (is_array($row))
		return $row[0];

	if ($allowempty)
		return null;
	else
		as_fatal_error('Reading one value from empty results');
}


/**
 * Suspend the updating of counts (of many different types) in the database, to save time when making a lot of changes
 * if $suspend is true, otherwise reinstate it. A counter is kept to allow multiple calls.
 * @param bool $suspend
 */
function as_suspend_update_counts($suspend = true)
{
	global $as_update_counts_suspended;

	$as_update_counts_suspended += ($suspend ? 1 : -1);
}


/**
 * Returns whether counts should currently be updated (i.e. if count updating has not been suspended).
 * @return bool
 */
function as_should_update_counts()
{
	global $as_update_counts_suspended;

	return ($as_update_counts_suspended <= 0);
}
