<?php
/**
 * 检查数据库迁移文件中创建的表
 */

// 提取所有迁移文件中创建的表名
$migrations_dir = __DIR__ . '/database/migrations';
$files = glob($migrations_dir . '/*.sql');
$created_tables = [];
$table_details = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);
    
    // 匹配 CREATE TABLE 语句
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?([\w]+)[`"]?/i', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $table) {
            // 移除前缀
            $clean_table = preg_replace('/^__PREFIX__/', '', $table);
            $clean_table = preg_replace('/^sn_/', '', $clean_table);
            
            if (!isset($table_details[$clean_table])) {
                $table_details[$clean_table] = [];
            }
            $table_details[$clean_table][] = $filename;
            
            if (!in_array($clean_table, $created_tables)) {
                $created_tables[] = $clean_table;
            }
        }
    }
}

sort($created_tables);

echo "=== 迁移文件中创建的表 (" . count($created_tables) . " 个) ===\n";
foreach ($created_tables as $table) {
    $files_list = implode(', ', $table_details[$table]);
    echo "  - $table (来源: $files_list)\n";
}

// 检查 016_database_index_optimization.sql 中引用的表
echo "\n=== 016_database_index_optimization.sql 中引用的表 ===\n";
$index_file = $migrations_dir . '/016_database_index_optimization.sql';
if (file_exists($index_file)) {
    $content = file_get_contents($index_file);
    preg_match_all('/ALTER\s+TABLE\s+[`"]?__PREFIX__([\w]+)[`"]?/i', $content, $matches);
    
    if (!empty($matches[1])) {
        $indexed_tables = array_unique($matches[1]);
        sort($indexed_tables);
        
        foreach ($indexed_tables as $table) {
            $exists = in_array($table, $created_tables) ? '✓' : '✗';
            echo "  $exists - $table\n";
        }
    }
}
