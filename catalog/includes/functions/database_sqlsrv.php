<?php
    /*
    $Id:

    osCommerce, Open Source E-Commerce Solutions
    http://www.oscommerce.com

    Copyright (c) 2007 osCommerce

    Released under the GNU General Public License
    */

    function tep_db_connect($server = DB_SERVER, $username = DB_SERVER_USERNAME, $password = DB_SERVER_PASSWORD,
    $database = DB_DATABASE, $link = 'db_link')
    {
        global $$link;

        sqlsrv_configure(SQLSRV_LOG_SYSTEM_ALL, 1);

        $conn_array = array
        (

        'Database' => $database,
        'UID' => $username,
        'PWD' => $password,
        "ConnectionPooling" => (USE_PCONNECT == 'true'),
        "ReturnDatesAsStrings" => '1',

        );

        $$link = sqlsrv_connect($server, $conn_array);

        if (!is_resource($$link))
        {
            tep_db_error('db_query', $query);
        }

        return $$link;
    }

    function tep_db_close($link = 'db_link')
    {
        global $$link;

        return sqlsrv_close($$link);
    }

    function tep_db_query($query, $link = 'db_link')
    {
        global $$link, $logger;

        if (!is_resource($$link))
        {
            tep_db_error('Database not connected'); // die
        }

        if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true'))
        {
            if (!is_object($logger))
                $logger = new logger;
            $logger->write($query, 'QUERY');
        }

        /* not used
        $time_start = explode(' ', microtime());
        */

        // Dynamic SQL Translations

        $q = $query;
        $qry = $query;
        $qry = str_ireplace('"', "'", $qry);
        $qry = str_ireplace("\\'", "''", $qry);
        $qry = str_ireplace("'0001-01-01'", "'1753-01-01'", $qry);
        $qry = str_ireplace('now()', 'getdate()', $qry);
        $qry = str_ireplace('SQL_CALC_FOUND_ROWS', '', $qry);
        $qry = str_ireplace('ifnull(', 'isnull(', $qry);

        $qry = preg_replace('/rlike\s*([\w|.]*)/i', "LIKE $1", $qry);
        $qry = preg_replace('/if\s*\((.*),\s*([\w|.]*)\s*,\s*([\w|.]*)\s*\)/i', "(CASE WHEN ($1) THEN $2 ELSE $3 END)", $qry);
        $qry = preg_replace('/show\s*tables/i', "SELECT TABLE_NAME AS Name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'", $qry);
        $qry = preg_replace('/show\s*tables\s*status\s*from\s*([\w|.]*)/i', "USE $1; SELECT TABLE_NAME AS Name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'", $qry);
        $qry = preg_replace('/show\s*table\s*like\s([\w|.]*)/i', "SELECT TABLE_NAME AS Name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE $1", $qry);
        $qry = preg_replace('/show\s*index\s*from\s*([\w|.]*)/i', "SELECT NAME AS KeyName FROM SYS.INDEXES WHERE NAME = '$1'", $qry);
        $qry = preg_replace('/show\s*fields\s*from\s*([\w|.]*)/i', "SELECT COLUMN_NAME AS Field, (DATA_TYPE + CASE WHEN (CHARACTER_MAXIMUM_LENGTH IS NOT NULL ) THEN '(' + CAST ( CHARACTER_MAXIMUM_LENGTH  AS VARCHAR) + ')' ELSE '' END ) AS Type FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$1'", $qry);
        $qry = preg_replace('/optimize table([\w|.]*.*)/i', "ALTER INDEX ALL ON $1 REORGANIZE; ALTER INDEX ALL ON $1 REBUILD", $qry);
        $qry = preg_replace('/date_add\s*\((.*),[\w|\s]*INTERVAL\s*(\d{1,2})\s*(\w*)\s*\)/i', 'DATEADD($3, $2, $1)', $qry);
        $qry = preg_replace('/date_sub\s*\((.*),[\w|\s]*INTERVAL\s*(\d{1,2})\s*(\w*)\s*\)/i', 'DATEADD($3, -$2, $1)', $qry);
        $qry = preg_replace('/date_format\s*\(([\w|\s|\(|\)]*),[\w|\s]*[\'|"]%Y-?%m-?%d[\'|"]\s*\)/i', 'CONVERT(varchar(10),$1,120)', $qry);
        $qry = preg_replace('/date_format\(([\w|.]*)\\Q, "%Y")\E/i', 'CONVERT(varchar(4),$1,112)', $qry);
        $qry = preg_replace('/date_format\(([\w|.]*)\\Q, "%m")\E/i', 'CONVERT(varchar(2),$1,101)', $qry);
        $qry = preg_replace('/date_format\(([\w|.]*)\\Q, "%d")\E/i', 'CONVERT(varchar(2),$1,103)', $qry);
        $qry = preg_replace('/unix_timestamp\((.*?)\)/i', "DATEDIFF(s, '19700101', $1)", $qry);
        $qry = preg_replace('/dayofmonth\((.*?)\)/i', "DATEPART(d, $1)", $qry);
        $qry = preg_replace('/dayofmonth\(([\w|.]*?)\)/i', 'DATEPART(d,$1)', $qry);
        $qry = preg_replace('/month\((.*?)\)/i', "DATEPART(m, $1)", $qry);
        $qry = preg_replace('/year\((.*?)\)/i', "DATEPART(yyyy, $1)", $qry);
        $qry = preg_replace('/length\((.*?)\)/i', "DATALENGTH($1)", $qry);
        $qry = preg_replace('/to_days\((.*?)\)/i', "DATEDIFF(s, '19700101', $1)", $qry);
        $qry = preg_replace('/lpad\(([\w|.]*),([\w|.]*),([\w|.]*)\)/i', 'REPLICATE($3, $2 - DATALENGTH($1)) + $1', $qry);

        $qry = preg_replace_callback('/concat\((.*)\)/i',
            create_function('$matches', 'return "(".str_replace(",","+",$matches[1]).")";'), $qry);

        // catch LIMIT clauses and convert to SQL Server compatible TOP queries
        if (stripos($qry, 'limit') > 0)
        {
            $qry = _LimitToTopN($qry);
        }


        // End Dynamic SQL Translations

        $query = '';
        //$query = 'SET QUOTED_IDENTIFIER OFF; ';
        $query .= $qry;

        // vvvvvvvvvvv DEBUG --- remove when ready

        $myFile = "C:\inetpub\wwwroot\oscommerce\debug\queryLog.txt";
        $fh = fopen($myFile, 'a+') or die("can't open file");
        $crlf = chr(13).chr(10);
        fwrite($fh, $crlf.'$$link=> '. $$link . ' Query=> '.$query);
        fclose($fh);

        // ^^^^^^^^^^ DEBUG --- remove when ready

        $db_resource = sqlsrv_query($$link, $query, array (), array ("Scrollable" => SQLSRV_CURSOR_KEYSET));

        if ($db_resource === false || !is_resource($db_resource))
        {

            // vvvvv DEBUG --- remove when ready

            $myFile = "C:\inetpub\wwwroot\oscommerce\debug\queryLog.txt";
            $fh = fopen($myFile, 'a+') or die("can't open file");
            fwrite($fh, $crlf.'**Error on last query**'.$crlf);
            fwrite($fh, $crlf.tep_db_format_error().$crlf);

            var_dump(debug_backtrace());
            $backtrace = (debug_backtrace());
            fwrite($fh, $crlf.'Backtrace: '.$backtrace.$crlf);
            fclose($fh);

            // msk
            DebugBreak();

            // ^^^^^ DEBUG --- remove when ready

            if (defined('STORE_DB_TRANSACTIONS') && (STORE_DB_TRANSACTIONS == 'true'))
            {
                $logger->write(tep_db_format_error(), 'ERROR');
            }

            tep_db_error('db_query_sqlsrv', $query);
        }

        return $db_resource;
    }

    function tep_db_perform($table, $data, $action = 'insert', $parameters = '', $link = 'db_link')
    {
        reset($data);

        if ($action == 'insert')
        {
            $query = 'insert into '.$table.' (';

            while (list($columns, ) = each($data))
            {
                $query .= $columns.', ';
            }
            $query = substr($query, 0, -2).') values (';
            reset($data);

            while (list(, $value) = each($data))
            {
                switch ((string)$value)
                {
                    case 'now()':
                        $query .= 'now(), ';
                        break;

                    case 'null':
                        $query .= 'null, ';
                        break;

                    default:
                        $query .= '\'' . tep_db_input($value) . '\', ';
                        break;
                }
            }
            $query = substr($query, 0, -2).')';
        }
        elseif ($action == 'update')
        {
            $query = 'update '.$table.' set ';

            while (list($columns, $value) = each($data))
            {
                switch ((string)$value)
                {
                    case 'now()':
                        $query .= $columns.' = now(), ';
                        break;

                    case 'null':
                        $query .= $columns .= ' = null, ';
                        break;

                    default:
                        $query .= $columns . ' = \'' . tep_db_input($value) . '\', ';
                        break;
                }
            }
            $query = substr($query, 0, -2).' where '.$parameters;
        }

        return tep_db_query($query, $link);
    }

    function tep_db_fetch_array($queryresult)
    {
        return sqlsrv_fetch_array($queryresult, SQLSRV_FETCH_ASSOC);
    }

    function tep_db_fetch_all($queryresult)
    {

        $rows = array ();

        if ($queryresult !== null)
        {
            $numFields = sqlsrv_num_fields($queryresult);
            $fieldMeta = sqlsrv_field_metadata($queryresult);

            while ($row = sqlsrv_fetch_array($queryresult, SQLSRV_FETCH_ASSOC))
            {
                if ($row !== null)
                {
                    foreach ($row as $k => $v)
                    {
                        if (is_object($v) && method_exists($v, 'format'))
                        {                                             // DateTime Object
                            $v->setTimezone(new DateTimeZone('GMT')); //TS_ISO_8601 with a trailing 'Z' is GMT
                            $row[$k] = $v->format("Y-m-d\TH:i:s\Z");
                        }
                    }
                    $rows[] = $row; //read results into memory, cursors are not supported
                }
            }

            sqlsrv_free_stmt($queryresult);
        }
        else
        {
            $numFields = 0;
            $fieldMeta = array ();
        }

        return $rows;
    }

    function tep_db_result($result, $row, $field = '')
    {
        return mysql_result($result, $row, $field);
    }

    function tep_db_num_rows($stmt)
    {
        global $$link;

        /*
        $L = tep_db_connect();
        $q = 'select TOP 1 id from administrators';
        $S = sqlsrv_query($L, $q, array (), array ("Scrollable" => SQLSRV_CURSOR_KEYSET));
        $num_rows = sqlsrv_num_rows($S);
        */

        //        $num_rows_affected = sqlsrv_rows_affected($stmt);
        $num_rows = sqlsrv_num_rows($stmt);
        return $num_rows;
    }

    function tep_db_data_seek($db_query, $row_number)
    {
        return mysql_data_seek($db_query, $row_number);
    }

    function tep_db_insert_id($link = 'db_link')
    {
        global $$link;

        return mysql_insert_id($$link);
    }

    function tep_db_free_result($db_query)
    {
        return mysql_free_result($db_query);
    }

    function tep_db_fetch_fields($db_query)
    {
        return mysql_fetch_field($db_query);
    }

    function tep_db_output($string)
    {
        return htmlspecialchars($string);
    }

    function tep_db_input($string, $link = 'db_link')
    {

        /*
        global $$link;


        if (function_exists('mysql_real_escape_string')) {
        return mysql_real_escape_string($string, $$link);
        } elseif (function_exists('mysql_escape_string')) {
        return mysql_escape_string($string);
        }

        return addslashes($string);
        */

        $result_string = str_replace("'", "''", $string);
        return ($result_string);
    }

    function tep_db_prepare_input($string)
    {
        $string = str_replace("'", "''", $string);

        if (is_string($string))
        {
            return trim(stripslashes($string));
        }
        elseif (is_array($string))
        {
            reset($string);

            while (list($key, $value) = each($string))
            {
                $string[$key] = tep_db_prepare_input($value);
            }
            return $string;
        }
        else
        {
            return $string;
        }
    }

    //
    // private (local) functions
    //

    function _LimitToTopN($sql)
    {
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
        for ($part_no = 0; $part_no < count($parts); $part_no++)
        {
            $part = $parts[$part_no];
            $part = trim($part);
            $partLo = strtolower($part);

            // last clause was the LIMIT statement, this clause is the values
            if ($inLimit)
            {
                // split on ,
                // $limitParts = explode(',', $part);
                // if there is 2 parts, it is a Offset, nResults format
                // if (count($limitParts) > 1)
                $count = count($parts);

                if (count($parts) - 1 > $part_no)
                {
                    // handle LIMIT OFFSET,COUNT syntax...
                    // $offset = $limitParts[0];
                    // $topN = $limitParts[1];
                    // $dropN = $limitParts[1];
                    $offset = (int)$parts[$part_no];
                    $parts[$part_no] = "";     // kill the offset
                    $topN = $parts[$part_no + 1];
                    $parts[$part_no + 1] = ""; // kill the limit
                    $dropN = $parts[$part_no + 1];
                }
                else
                {
                    // the limit value
                    $topN = $part;
                }

                $dropN = $part;
                $limit = $topN;
                // done processing this part
                $inLimit = false;
            }
            else
            {
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

        foreach ($parts as $part)
        {
            $part = trim($part);
            $partLo = strtolower($part);

            if ($partLo == "")
            {
                continue;
            }

            // pull out any [ ALL | DISTINCT ] and put back just after SELECT, DELETE, UPDATE, INSERT
            if ($partLo == 'distinct' || $partLo == 'all')
            {
                continue; // discard it as we will put it back in its proper place later
            }

            // TOP N is only supported in SELECT, DELETE, UPDATE, INSERT statements
            if (($topN != null) && ($partLo == 'select') || ($partLo == 'delete') || ($partLo == 'update')
            || ($partLo == 'insert'))
            {
                if ($disctinct_found)
                {
                    $part .= ' DISTINCT ';
                    $disctinct_found = false;
                }
                else if ($all_found)
                    {
                        $part .= ' ALL ';
                        $all_found = false;
                    }

                    $sql2 .= " $part TOP $topN";
                $topN = null; // we already used it, clear it out
            }
            else
            {
                if ($inLimit)
                {
                    // we just eat the values clause for a LIMIT statement
                    $inLimit = false;
                }
                else
                {
                    if ($partLo == 'limit')
                    {
                        // we just eat the LIMIT portion of a LIMIT statement
                        $inLimit = true;
                    }
                    else
                    {
                        $sql2 .= " $part";
                    }
                }
            }
        }

        $sql2 = str_ireplace("limit $dropN", "", $sql2);

        if ($limit > 0 && $offset > 0)
        {
            if (preg_match('/\w*from\s*([\w|.]*)/i', $sql2, $match))
            {
                $from_table = $match[1];
                $sql2 =
                // Emulate < LIMIT offset, limit >

                'SELECT * FROM (
                SELECT sub2.*, ROW_NUMBER() OVER(ORDER BY sub2.line2) AS line3 FROM (
                SELECT 1 AS line2, sub1.* FROM '
                .$from_table.' AS sub1
                ) AS sub2
                ) AS sub3
                WHERE line3 BETWEEN '.($offset + 1).' AND '
                .($offset + $limit);
            }
            else
            {
                $sql2 = "SELECT 'Invalid table'";
            }
        }

        return $sql2;
    }

    function tep_db_format_error()
    {

        $retErrors = sqlsrv_errors(SQLSRV_ERR_ALL);
        $strError = 'No errors found';

        if ($retErrors != null)
        {
            $strError = '';

            foreach ($retErrors as $arrError)
            {
                $strError .= "SQLState: ".$arrError['SQLSTATE']."<br>\n";
                $strError .= "Error Code: ".$arrError['code']."<br>\n";
                $strError .= "Message: ".$arrError['message']."<br>\n";
            }

            return $strError;
        }
    }

//    function tep_db_error($query, $errno, $error, $err_text = '', $fatal = true)
    function tep_db_error($err_text = '', $query, $fatal = true)
    {
        global $db_error;

        $err_text .= ' '.tep_db_format_error();
        $db_error = $err_text;

        echo '<div class="systemError">';
        echo ('<hr><h3>SQL Error:</h3> <b>'.$err_text.'</b>');
        echo ('<br><br><h4>Query:</h4> <pre>'.$query.'</pre>');

        if (defined('IS_ADMIN_FLAG') && IS_ADMIN_FLAG == true)
            echo
            'If you were entering information, press the BACK button in your browser and re-check the information you had entered to be sure you left no blank fields.<br />';

        echo '</div>';

        print('<pre>');
        debug_print_backtrace();
        print('</pre>');

        @ob_flush();
        @flush();

        if ($fatal)
        {
            // msk
            DebugBreak();

            //die('<font color="#000000"><b>' . $errno . ' - ' . $error . '<br><br>' . $query . '<br><br><small><font color="#ff0000">[TEP STOP]</font></small><br><br></b></font>');
            die();
        }

        // msk
        DebugBreak();

        return $db_error;
    }

?>