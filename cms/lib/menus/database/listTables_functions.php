<?php

/**
 * Get the list of tables including menu details and record count.  Note this only returns tables that have a schema file.
 *
 * @return array List of tables with additional information.
 */
function getTableList(): array {
    $mysqlTables = getMysqlTablesWithPrefix();
    $tables = [];

    foreach (getSchemaTables() as $tableNameWithoutPrefix) {
        $tableName = getTableNameWithPrefix($tableNameWithoutPrefix);
        $tableSchema = loadSchema($tableName);
        $mysqlTableExists = in_array($tableName, $mysqlTables);
        $tables[]         = [
            'tableName'   => $tableName,
            'menuName'    => $tableSchema['menuName'] ?? '',
            'menuType'    => $tableSchema['menuType'] ?? '',
            'menuOrder'   => $tableSchema['menuOrder'] ?? '',
            'menuHidden'  => $tableSchema['menuHidden'] ?? '',
            'tableHidden' => $tableSchema['tableHidden'] ?? '',
            '_indent'     => $tableSchema['_indent'] ?? '',
            'recordCount' => $mysqlTableExists ? mysql_count($tableName) : 0,
        ];
    }

    // sort table list
    uasort($tables, '_sortMenusByOrder');


    //
    return $tables;
}
