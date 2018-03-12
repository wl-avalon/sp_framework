<?php
/**
 * Created by PhpStorm.
 * User: wzj-dev
 * Date: 18/3/7
 * Time: 上午11:04
 */
$sqlFilePath        = $argv[1];
if($sqlFilePath === '-h'){
    echo "使用方法:php createSqlBean.php 参数1 参数2\n";
    echo "参数1:待生成的.sql文件或包含.sql文件的目录\n";
    echo "参数2(可选):Bean文件的存储目录,若目录不存在自动创建。如果不填,则在createSqlBean.php目录下生成output目录\n";
    exit;
}

$resultDirPath      = $argv[2] ?? __DIR__ . "/output";
if(!is_dir($resultDirPath)){
    mkdir($resultDirPath, 0744, true);
}

if(is_dir($sqlFilePath)){
    $dirPath        = $sqlFilePath;
    $dirResource    = opendir($dirPath);
    while (($filename = readdir($dirResource)) !== false) {
        if ($filename == "." || $filename == "..") {
            continue;
        }

        $sqlFileName = "{$dirPath}/{$filename}";
        if(!is_file($sqlFileName) || substr($sqlFileName, -4, 4) !== ".sql"){
            continue;
        }
        createBeanFile($sqlFileName, $resultDirPath);
    }
    closedir($dirResource);
}elseif(is_file($sqlFilePath)){
    createBeanFile($sqlFilePath, $resultDirPath);
}else{
    echo "待执行文件或目录不存在,已退出。\n";
}

function createBeanFile($sqlFileName, $resultDirPath){
    $tableInfoList = getCreateTableContent($sqlFileName);
    foreach($tableInfoList as $tableInfo){
        $formatTableInfo = formatCreateTableInfo($tableInfo);
        writeBeanFile($formatTableInfo, $resultDirPath);
    }
}

function getCreateTableContent($sqlFileName){
    $fp = @fopen($sqlFileName, 'r');
    if($fp === false){
        echo "打开文件失败,文件名为:{$sqlFileName},已自动跳过,请稍后重试。";
        return true;
    }

    $allLine = "";
    while(!feof($fp)){
        $line = trim(fgets($fp));
        $allLine .= $line;
    }
    preg_match_all('/(CREATE TABLE[^;]*?;)/i', $allLine, $matchArr);

    $createTableTextList    = $matchArr[1];
    $tableInfo              = [];
    foreach($createTableTextList as $textItem){
        if(strpos($textItem, 'AUTO_INCREMENT') === false){
            continue;
        }
        $matchArr = [];
        preg_match_all('/CREATE TABLE([^\(]*)\(([\s\S]*)\)([\s\S]*;)/i', $textItem, $matchArr);
        $tableInfo[] = [
            'tableName'     => $matchArr[1][0] ?? "",
            'tableContent'  => $matchArr[2][0] ?? "",
            'tableRemark'   => $matchArr[3][0] ?? "",
        ];
    }
    return $tableInfo;
}

function formatCreateTableInfo($tableInfo){
    //1 格式化表名
    $tableName  = trim($tableInfo['tableName']);
    $tableName  = trim($tableName, '`');

    //2 格式化表内字段
    $tableContentList       = [];
    $tableContentListTemp   = explode(',', $tableInfo['tableContent']);
    $count      = 0;
    $oneLine    = "";
    foreach($tableContentListTemp as $contentItem){
        $oneLine .= "{$contentItem},";
        $count += substr_count($contentItem, "'");
        if($count % 2 != 0){
            continue;
        }
        if(strpos($oneLine, 'COMMENT') === false || strpos($oneLine, 'comment')){
            continue;
        }
        $tableContentList[] = $oneLine;
        $oneLine    = "";
        $count      = 0;
    }
    $tableContentFormatList = [];
    foreach($tableContentList as $tableContent){
        if(strpos($tableContent, 'KEY') !== false){
            continue;
        }
        $matchArr = [];
        preg_match_all('/`([^`]*)`([\s\S]*),/', $tableContent, $matchArr);
        $tableContentFormatList[] = [
            'itemName'      => $matchArr[1][0] ?? "",
            'itemRemark'    => trim($matchArr[2][0]) ?? "",
        ];
    }

    //3 格式化表注释
    $tableRemarkTemp = $tableInfo['tableRemark'];
    $matchArr = [];
    preg_match_all('/COMMENT[^\']*\'([^\']*)\'/', $tableRemarkTemp, $matchArr);
    $tableComment = $matchArr[1][0] ?? "";
    $matchArr = [];
    preg_match_all('/ENGINE =[^\S]*([\S]*)\s/', $tableRemarkTemp, $matchArr);
    $tableEngine = $matchArr[1][0] ?? "";
    $matchArr = [];
    preg_match_all('/DEFAULT CHARSET =[^\S]*([\S]*)\s/', $tableRemarkTemp, $matchArr);
    $tableDefaultCharset = $matchArr[1][0] ?? "";

    $result = [
        'tableName'     => $tableName,
        'tableContent'  => $tableContentFormatList,
        'tableRemark'   => [
            'comment'           => $tableComment,
            'engine'            => $tableEngine,
            'defaultCharset'    => $tableDefaultCharset,
        ],
    ];
    return $result;
}

function writeBeanFile($tableInfo, $resultDirPath){
    //1 获取Bean文件路径
    $tableName      = $tableInfo['tableName'];
    $beanClassName  = str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName))) . 'Bean';
    $beanFilePath   = rtrim($resultDirPath, '/') . "/{$beanClassName}.php";
    $namespace      = "namespace app\modules\models\beans";

    //2 打开文件准备写入,初始化变量
    $fp = fopen($beanFilePath, 'w');
    $tableContent   = $tableInfo['tableContent'];
    $tableRemark    = $tableInfo['tableRemark'];
    //2.1 写入文件头
    writeOneLine($fp, "<?php", 0);
    writeOneLine($fp, "/**", 0);
    writeOneLine($fp, " * Create by script:createSqlBean.php", 0);
    writeOneLine($fp, " * Date is:" . date('Y-m-d'), 0);
    writeOneLine($fp, " * Time is:" . date('H:i:s'), 0);
    writeOneLine($fp, " */\n", 0);
    writeOneLine($fp, "{$namespace};\n", 0);

    //2.2 写如Bean类注释
    writeOneLine($fp, "/**", 0);
    if(!empty($tableRemark['comment'])){
        writeOneLine($fp, " * Comment: {$tableRemark['comment']}", 0);
    }
    if(!empty($tableRemark['engine'])){
        writeOneLine($fp, " * Engine: {$tableRemark['engine']}", 0);
    }
    if(!empty($tableRemark['defaultCharset'])){
        writeOneLine($fp, " * Default Charset: {$tableRemark['defaultCharset']}", 0);
    }
    writeOneLine($fp, " * Class {$beanClassName}", 0);
    writeOneLine($fp, " * Package {$namespace}", 0);
    writeOneLine($fp, " */", 0);
    writeOneLine($fp, "class {$beanClassName}", 0);
    writeOneLine($fp, "{", 0);
    writeTableContent($fp, $tableContent);
    writeOneLine($fp, "}", 0);
    fclose($fp);
}

function writeTableContent($fp, $tableContent){
    //1 获取变量最大长度
    $maxLength = 0;
    foreach($tableContent as $tableItem){
        $itemName   = $tableItem['itemName'];
//        $itemName   = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $itemName))));
        if(mb_strlen($itemName) > $maxLength){
            $maxLength = mb_strlen($itemName);
        }
    }

    //2 写入私有成员变量
    $privateMaxLength = $maxLength + 9;
    $privateMaxLength = $privateMaxLength % 4 == 0 ? $privateMaxLength + 4 : $privateMaxLength + (4 - $privateMaxLength % 4);
    foreach($tableContent as $tableItem){
        $itemName   = $tableItem['itemName'];
        $itemRemark = $tableItem['itemRemark'];
//        $itemName   = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $itemName))));
        $line = "private \${$itemName}";
        if(mb_strlen($line) < $privateMaxLength){
            $line .= str_repeat(' ', $privateMaxLength - mb_strlen($line));
        }
        $line .= "= null; //{$itemRemark}";
        writeOneLine($fp, $line, 1);
    }

    //2 定义构造函数
    writeOneLine($fp, "", 1);
    writeOneLine($fp, "public function __construct(\$input){", 1);
    $initMaxLength      = $maxLength + 8;
    $initMaxLength      = $initMaxLength % 4 == 0 ? $initMaxLength + 4 : $initMaxLength + (4 - $initMaxLength % 4);
    $defaultMaxLength   = $maxLength + $initMaxLength + 12;
    $defaultMaxLength   = $defaultMaxLength % 4 == 0 ? $defaultMaxLength + 4 : $defaultMaxLength + (4 - $defaultMaxLength % 4);
    foreach($tableContent as $tableItem){
        $itemName   = $tableItem['itemName'];
//        $itemName   = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $itemName))));
        $line = "\$this->{$itemName}";
        if(mb_strlen($line) < $initMaxLength){
            $line .= str_repeat(' ', $initMaxLength - mb_strlen($line));
        }
        $line .= "= \$input['{$itemName}']";
        if(mb_strlen($line) < $defaultMaxLength){
            $line .= str_repeat(' ', $defaultMaxLength - mb_strlen($line));
        }
        $line .= "?? null;";
        writeOneLine($fp, $line, 2);
    }
    writeOneLine($fp, "}", 1);

    //3 定义toArray()
    writeOneLine($fp, "", 1);
    writeOneLine($fp, "public function toArray(){", 1);
    writeOneLine($fp, "return [", 2);
    $equalMaxLength = $maxLength + 2;
    $equalMaxLength = $equalMaxLength % 4 == 0 ? $equalMaxLength + 4 : $equalMaxLength + (4 - $equalMaxLength % 4);
    foreach($tableContent as $tableItem){
        $itemName   = $tableItem['itemName'];
//        $itemName   = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $itemName))));
        $line = "'{$itemName}'";
        if(mb_strlen($line) < $equalMaxLength){
            $line .= str_repeat(' ', $equalMaxLength - mb_strlen($line));
        }
        $line .= "=> \$this->{$itemName},";
        writeOneLine($fp, $line, 3);
    }
    writeOneLine($fp, "];", 2);
    writeOneLine($fp, "}", 1);

    //4 定义get方法
    writeOneLine($fp, "", 1);
    $funcMaxLength = $maxLength + 21;
    $funcMaxLength = $funcMaxLength % 4 == 0 ? $funcMaxLength + 4 : $funcMaxLength + (4 - $funcMaxLength % 4);
    foreach($tableContent as $tableItem){
        $itemName       = $tableItem['itemName'];
        $itemNameList   = explode('_', $itemName);
        $itemNameList   = array_map(function($item){if($item === 'id'){$item = 'ID';} return $item;}, $itemNameList);
        $funcItemName   = implode('_', $itemNameList);
        $funcItemName   = str_replace(' ', '', ucwords(str_replace('_', ' ', $funcItemName)));
//        $itemName       = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $itemName))));
        $line           = "public function get{$funcItemName}()";
        if(mb_strlen($line) < $funcMaxLength){
            $line .= str_repeat(' ', $funcMaxLength - mb_strlen($line));
        }
        $line           .= "{return \$this->{$itemName};}";
        writeOneLine($fp, $line, 1);
    }

    //5 定义set方法
    writeOneLine($fp, "", 1);
    $funcMaxLength  = $maxLength * 2 + 22;
    $funcMaxLength  = $funcMaxLength % 4 == 0 ? $funcMaxLength + 4 : $funcMaxLength + (4 - $funcMaxLength % 4);
    $equalMaxLength = $funcMaxLength + $maxLength + 8;
    $equalMaxLength = $equalMaxLength % 4 == 0 ? $equalMaxLength + 4 : $equalMaxLength + (4 - $equalMaxLength % 4);
    foreach($tableContent as $tableItem){
        $itemName       = $tableItem['itemName'];
        $itemNameList   = explode('_', $itemName);
        $itemNameList   = array_map(function($item){if($item === 'id'){$item = 'ID';} return $item;}, $itemNameList);
        $funcItemName   = implode('_', $itemNameList);
        $funcItemName   = str_replace(' ', '', ucwords(str_replace('_', ' ', $funcItemName)));
//        $itemName       = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $itemName))));
        $line           = "public function set{$funcItemName}(\${$itemName})";
        if(mb_strlen($line) < $funcMaxLength){
            $line .= str_repeat(' ', $funcMaxLength - mb_strlen($line));
        }
        $line           .= "{\$this->{$itemName}";
        if(mb_strlen($line) < $equalMaxLength){
            $line .= str_repeat(' ', $equalMaxLength - mb_strlen($line));
        }
        $line           .= "= \${$itemName};}";
        writeOneLine($fp, $line, 1);
    }
}

function writeOneLine($fp, $str, $level){
    $writeStr = str_repeat(' ', $level * 4);
    fwrite($fp, "{$writeStr}{$str}\n");
}