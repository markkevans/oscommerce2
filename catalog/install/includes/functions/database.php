<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2007 osCommerce

  Released under the GNU General Public License
*/

  function osc_db_connect($database_type, $server, $username, $password, $database, $link = 'db_link') {
    global $$link, $db_error;
    global $$db_type;

    $$link = null;
    $db_error = false;

    if (!$server || !$database_type) {
        $db_error = 'No Server selected.';
        return false;
    }

    $$db_type = $database_type;

    switch ($$db_type)
     {
      case 'mysql':

       $$link = @mysql_connect($server, $username, $password) or $db_error = mysql_error();
       osc_db_select_db($database);
       break;

      case 'sqlsrv':

       $conn_array = array
        (
         'Database' => $database,
         'UID' => $username,
         'PWD' => $password,
         "ConnectionPooling" => true
        );

       $$link = sqlsrv_connect($server, $conn_array);

       if (!is_resource($$link)) {
           osc_db_error_sqlsrv('osc_db_query', $query);
       }

       break;
     }
    return $$link;
}

function osc_db_select_db($database) {
    global $$db_type;

    switch ($$db_type)
     {
      case 'mysql':
       return mysql_select_db($database);
       break;

      case 'sqlsrv':
      // Done at connect time;
      break;
     }
}

function osc_db_query($query, $link = 'db_link') {
    global $$link;
    global $$db_type;

    switch ($$db_type)
     {
      case 'mysql':

       return mysql_query($query, $$link);

       break;

      case 'sqlsrv':

       return osc_db_query_sqlsrv($query, $$link);

       break;
     }
}

function osc_db_error_sqlsrv($err_text, $query = '', $fatal = true) {
    global $db_error;

    $retErrors = @sqlsrv_errors(SQLSRV_ERR_ALL);
    $strError = 'No errors found';

    if ($retErrors != null) {
        $strError = '';

        foreach ($retErrors as $arrError) {
            $strError .= "SQLState: ".$arrError['SQLSTATE']."<br>\n";
            $strError .= "Error Code: ".$arrError['code']."<br>\n";
            $strError .= "Message: ".$arrError['message']."<br>\n";
        }

        $err_text .= ' '.$strError;
        $db_error = $err_text;

        echo '<div class="systemError">';
        echo ('<hr><h3>SQL Error:</h3> <b>'.$err_text.'</b>');
        echo ('<br><br><h4>Query:</h4> <pre>'.$query.'</pre>');
        echo '</div>';
        print('<pre>');
        debug_print_backtrace();
        print('</pre>');

        @ob_flush();
        @flush();

        if ($fatal) {
            die();
        }
    }
    return $db_error;
}

function prepare_input($zp_string) {
    return str_replace("'", "''", $zp_string);
}

function osc_db_query_sqlsrv($query, $link = 'db_link') {
    global $db_error;
    global $$db_type;

    if (!$link) {
        osc_db_error_sqlsrv('Database not connected');
    }

    // Dynamic SQL Translations
    //
    $qry = $query;
    $qry = str_ireplace('"', "'", $qry);
    $qry = str_ireplace("\\'", "''", $qry);
    $qry = str_ireplace("'0001-01-01'", "'1753-01-01'", $qry);
    $qry = str_ireplace('now()', 'getdate()', $qry);
    $qry = str_ireplace('SQL_CALC_FOUND_ROWS', '', $qry);
    $qry = str_ireplace('ifnull(', 'isnull(', $qry);

    $qry = preg_replace('/\w*rlike\s*([\w|.]*)/i', "LIKE $1", $qry); // punt
    $qry = preg_replace('/show\s*table\s*status\s*from\s*([\w|.]*)/i', "USE $1; SELECT TABLE_NAME AS Name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'", $qry);
    $qry = preg_replace('/show\s*tables\s*like\s([\w|.]*)/i', "SELECT TABLE_NAME AS Name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE $1", $qry);
    $qry = preg_replace('/show\s*index\s*from\s*([\w|.]*)/i', "SELECT NAME AS KeyName FROM SYS.INDEXES WHERE NAME = '$1'", $qry);
    $qry = preg_replace('/show\s*fields\s*from\s*([\w|.]*)/i', "SELECT COLUMN_NAME AS Field, (DATA_TYPE + CASE WHEN (CHARACTER_MAXIMUM_LENGTH IS NOT NULL ) THEN '(' + CAST ( CHARACTER_MAXIMUM_LENGTH  AS VARCHAR) + ')' ELSE '' END ) AS Type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$1'", $qry);
    $qry = preg_replace('/optimize table([\w|.]*.*)/i', "ALTER INDEX ALL ON $1 REORGANIZE; ALTER INDEX ALL ON $1 REBUILD", $qry);
    $qry = preg_replace('/date_format\s*\(\s*([\w|.]*)\s*,\s*[\'|"]%Y-%m-%d[\'|"]\s*\)/i', 'CONVERT(varchar(10),$1,120)', $qry);
    $qry = preg_replace('/date_format\(([\w|.]*)\\Q, "%Y")\E/i', 'CONVERT(varchar(4),$1,112)', $qry);
    $qry = preg_replace('/date_format\(([\w|.]*)\\Q, "%m")\E/i', 'CONVERT(varchar(2),$1,101)', $qry);
    $qry = preg_replace('/date_format\(([\w|.]*)\\Q, "%d")\E/i', 'CONVERT(varchar(2),$1,103)', $qry);
    $qry = preg_replace('/unix_timestamp\((.*?)\)/i', "DATEDIFF(s, '19700101', $1)", $qry);
    $qry = preg_replace('/dayofmonth\((.*?)\)/i', "DATEPART(d, $1)", $qry);
    $qry = preg_replace('/dayofmonth\(([\w|.]*?)\)/i', 'datepart(d,$1)', $qry);
    $qry = preg_replace('/month\((.*?)\)/i', "DATEPART(m, $1)", $qry);
    $qry = preg_replace('/year\((.*?)\)/i', "DATEPART(yyyy, $1)", $qry);
    $qry = preg_replace('/length\((.*?)\)/i', "DATALENGTH($1)", $qry);
    $qry = preg_replace('/to_days\((.*?)\)/i', "DATEDIFF(s, '19700101', $1)", $qry);
    $qry = preg_replace('/lpad\(([\w|.]*),([\w|.]*),([\w|.]*)\)/i', 'REPLICATE($3, $2 - DATALENGTH($1)) + $1', $qry);

    $qry = preg_replace_callback('/concat\((.*)\)/i',
        create_function('$matches', 'return "(".str_replace(",","+",$matches[1]).")";'), $qry);

    // catch LIMIT clauses and convert to SQL Server compatible TOP queries
    if (stripos($qry, 'limit') > 0) {
        $qry = $this->_LimitToTopN($qry);
    }
    //
    // End Dynamic SQL Translations

    $query = 'SET QUOTED_IDENTIFIER OFF; '.$qry;
    $db_resource = sqlsrv_query($link, $query, array(), array( "Scrollable" => SQLSRV_CURSOR_KEYSET));

    if ($zp_db_resource === false || !is_resource($db_resource)) {
        osc_db_error_sqlsrv('osc_db_query_sqlsrv', $query);
    }
    return $db_resource;
}

function _LimitToTopN($sql) {
    $offset = 0;
    $limit = 0;
    // break into tokens
    $parts = explode(' ', $sql);

    $inLimit = false;
    $topN = null;

    $disctinct_found = false;
    $all_found = false;

// process each token looking for the LIMIT clause, either in LIMIT nResults, or LIMIT Offset,nResults format
// foreach ($parts as $part)
    for ($part_no = 0; $part_no < count($parts); $part_no++) {
        $part = $parts[$part_no];
        $part = trim($part);
        $partLo = strtolower($part);

        // last clause was the LIMIT statement, this clause is the values
        if ($inLimit) {
            // split on ,
            // $limitParts = explode(',', $part);
            // if there is 2 parts, it is a Offset, nResults format
            // if (count($limitParts) > 1)
            $count = count($parts);

            if (count($parts) - 1 > $part_no) {
                // handle LIMIT OFFSET,COUNT syntax...
                // $offset = $limitParts[0];
                // $topN = $limitParts[1];
                // $dropN = $limitParts[1];
                $offset = (int)$parts[$part_no];
                $parts[$part_no] = "";     // kill the offset
                $topN = $parts[$part_no + 1];
                $parts[$part_no + 1] = ""; // kill the limit
                $dropN = $parts[$part_no + 1];
            } else {
                // the limit value
                $topN = $part;
            }

            $dropN = $part;
            $limit = $topN;
            // done processing this part
            $inLimit = false;
        } else {
            switch ($partLo)
             {
              case 'limit':
               $inLimit = true;
               break;

              case 'distinct':
               $disctinct_found = true;
               break;

              case 'all':
               $all_found = true;
               break;
             }
        }
    }

    // now build up sql again with top N in it
    $sql2 = '';

    foreach ($parts as $part) {
        $part = trim($part);
        $partLo = strtolower($part);

        if ($partLo == "") {
            continue;
        }

        // pull out any [ ALL | DISTINCT ] and put back just after SELECT, DELETE, UPDATE, INSERT
        if ($partLo == 'distinct' || $partLo == 'all') {
            continue; // discard it as we will put it back in its proper place later
        }

        // TOP N is only supported in SELECT, DELETE, UPDATE, INSERT statements
        if (($topN != null) && ($partLo == 'select') || ($partLo == 'delete')
            || ($partLo == 'update') || ($partLo == 'insert')) {
            if ($disctinct_found) {
                $part .= ' DISTINCT ';
                $disctinct_found = false;
            } else if ($all_found) {
                $part .= ' ALL ';
                $all_found = false;
            }

            $sql2 .= " $part TOP $topN";
            $topN = null; // we already used it, clear it out
        } else {
            if ($inLimit) {
                // we just eat the values clause for a LIMIT statement
                $inLimit = false;
            } else {
                if ($partLo == 'limit') {
                    // we just eat the LIMIT portion of a LIMIT statement
                    $inLimit = true;
                } else {
                    $sql2 .= " $part";
                }
            }
        }
    }

    $sql2 = str_ireplace("limit $dropN", "", $sql2);

    if ($limit > 0 && $offset > 0) {
        if (preg_match('/\w*from\s*([\w|.]*)/i', $sql2, $match)) {
            $from_table = $match[1];
            $sql2 =
            // Emulate < LIMIT offset, limit >

            'SELECT * FROM (
                            SELECT sub2.*, ROW_NUMBER() OVER(ORDER BY sub2.line2) AS line3 FROM (
                                SELECT 1 AS line2, sub1.* FROM '
                .$from_table.' AS sub1
                                ) AS sub2
                            ) AS sub3
                WHERE line3 BETWEEN '.($offset + 1).' AND '.($offset + $limit);
        } else {
            $sql2 = "SELECT 'Invalid table'";
        }
    }

    return $sql2;
}

function osc_db_num_rows($db_query) {
    global $$db_type;
    $rows = 0;

    switch ($$db_type)
     {
      case 'mysql':

       $rows = mysql_num_rows($db_query);
       break;

      case 'sqlsrv':

       $rows = sqlsrv_num_rows($db_query);

       if ($rows === false) {
           osc_db_error_sqlsrv('osc_db_query_sqlsrv', $query);
       }

       break;
     }
    return $rows;
}

function osc_db_install($database, $sql_file) {
    global $db_error;
    global $$db_type;

    $db_error = false;

    switch ($$db_type)
    {
        case 'mysql':

            return osc_db_install_mysql($sql_file);

        case 'sqlsrv':
            return osc_db_install_sqlsrv($sql_file);
    }

  }

function osc_db_install_sqlsrv($sql_file) {
    global $db_link;

    if (!is_resource($db_link)) {
        $db_error = 'SQL server not cvonnected';
        return false;
    }

    if (file_exists($sql_file)) {

        $lines = file($sql_file, FILE_TEXT);
        $sql = '';

        $linenum = 0;

        foreach ($lines as $line) {
            $linenum++;

            if (trim($line) == '') {
                continue;
            }

            if (trim($line) == 'GO') {
                if (trim($sql) == '') {
                    continue;
                }

                $stmt_resource = sqlsrv_query($db_link, $sql.';');

                if (!is_resource($stmt_resource)) {

                    $retErrors = @sqlsrv_errors(SQLSRV_ERR_ALL);
                    $strError = 'No errors found';

                    if ($retErrors != null) {
                        $strError = '';

                        foreach ($retErrors as $arrError) {
                            $strError .= "SQLState: ".$arrError['SQLSTATE']."<br>\n";
                            $strError .= "Error Code: ".$arrError['code']."<br>\n";
                            $strError .= "Message: ".$arrError['message']."<br>\n";
                        }

                        $db_error = $strError;
                    }
                    return false;
                }

                $sql = '';
                @sqlsrv_free_stmt($stmt);
            } else {
                $sql .= $line.chr(13).chr(10);
            }
        }
    }
    return true;
}

function osc_db_install_mysql($sql_file) {
    if (!@osc_db_select_db($database)) {
        if (@osc_db_query('create database '.$database)) {
            osc_db_select_db($database);
        } else {
            $db_error = mysql_error();
        }
    }

    if (!$db_error) {
        if (file_exists($sql_file)) {
            $fd = fopen($sql_file, 'rb');
            $restore_query = fread($fd, filesize($sql_file));
            fclose($fd);
        } else {
            $db_error = 'SQL file does not exist: '.$sql_file;
            return false;
        }

        $sql_array = array ();
        $sql_length = strlen($restore_query);
        $pos = strpos($restore_query, ';');

        for ($i = $pos; $i < $sql_length; $i++) {
            if ($restore_query[0] == '#') {
                $restore_query = ltrim(substr($restore_query, strpos($restore_query, "\n")));
                $sql_length = strlen($restore_query);
                $i = strpos($restore_query, ';') - 1;
                continue;
            }

            if ($restore_query[($i + 1)] == "\n") {
                for ($j = ($i + 2); $j < $sql_length; $j++) {
                    if (trim($restore_query[$j]) != '') {
                        $next = substr($restore_query, $j, 6);

                        if ($next[0] == '#') {
// find out where the break position is so we can remove this line (#comment line)
                            for ($k = $j; $k < $sql_length; $k++) {
                                if ($restore_query[$k] == "\n") break;
                            }

                            $query = substr($restore_query, 0, $i + 1);
                            $restore_query = substr($restore_query, $k);
                            // join the query before the comment appeared, with the rest of the dump
                            $restore_query = $query.$restore_query;
                            $sql_length = strlen($restore_query);
                            $i = strpos($restore_query, ';') - 1;
                            continue 2;
                        }
                        break;
                    }
                }

                if ($next == '') { // get the last insert query
                    $next = 'insert';
                }

                if ((eregi('create', $next)) || (eregi('insert', $next)) || (eregi('drop t', $next))) {
                    $next = '';
                    $sql_array[] = substr($restore_query, 0, $i);
                    $restore_query = ltrim(substr($restore_query, $i + 1));
                    $sql_length = strlen($restore_query);
                    $i = strpos($restore_query, ';') - 1;
                }
            }
        }

        osc_db_query("drop table if exists address_book, address_format, administrators, banners, banners_history, categories, categories_description, configuration, configuration_group, counter, counter_history, countries, currencies, customers, customers_basket, customers_basket_attributes, customers_info, languages, manufacturers, manufacturers_info, orders, orders_products, orders_status, orders_status_history, orders_products_attributes, orders_products_download, products, products_attributes, products_attributes_download, prodcts_description, products_options, products_options_values, products_options_values_to_products_options, products_to_categories, reviews, reviews_description, sessions, specials, tax_class, tax_rates, geo_zones, whos_online, zones, zones_to_geo_zones");

        for ($i = 0; $i < sizeof($sql_array); $i++) {
            osc_db_query($sql_array[$i]);
        }
    } else {
        return false;
    }
}

?>