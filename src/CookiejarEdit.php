<?php

/**
 * Description of CookiejarEdit
 *
 * @author VVB
 */
class CookiejarEdit {

    protected $sFname  = false; // имя файла cookies
    protected $aPrefix = array(// массив значений общих полей записей
        '', // #0: domain
        'FALSE', // #1: tailmatch (строгое совпадение доменного имени)
        '/', // #2: path
        'TRUE', // #3: secure (https-соединение)
    );
    protected $sPrefix = ''; // строка значений общих полей записей cookie

    function __construct($sFn, $sDomain = '', $aXtra = 0) {
        if (!$sFn) return;
        $this->sFname = $sFn;
        $this->setPrefix($sDomain, $aXtra);
    }

    function __clone() {
        $this->setPrefix();
    }

    /*     * **
     * * Инициализация/установка общих полей записей cookie:
     * * Аргументы:
     * *  1) $sDomain - значение поля 'domain'
     * *  2) $aXtra - массив значений дополн-ных общих полей:
     * *     $aXtra['tailmatch'] - значение поля 'tailmatch'
     * *     $aXtra['path'] - значение поля 'path'
     * *     $aXtra['secure'] - значение поля 'secure'
     */

    function setPrefix($sDomain, $aXtra = 0) {
        if ($sDomain) $this->aPrefix[0] = $sDomain;
        if (is_array($aXtra)) {
            if (isset($aXtra['tailmatch'])) $this->aPrefix[1] = $aXtra['tailmatch'] ? 'TRUE' : 'FALSE';
            if (isset($aXtra['path'])) $this->aPrefix[2] = $aXtra['path'];
            if (isset($aXtra['secure'])) $this->aPrefix[3] = $aXtra['secure'] ? 'TRUE' : 'FALSE';
        }
        if ($this->aPrefix[0]) $this->sPrefix = implode("\t", $this->aPrefix) . "\t";
    }

    /*     * **
     * * Экспорт содержимого файла cookies:
     */

    function export() {
        return ($this->sFname) ? file_get_contents($this->sFname) : false;
    }

    /*     * **
     * * Импорт содержимого файла cookies:
     */

    function import($sCont) {
        if (!$sCont || strlen($sCont) < 10) return false;
        file_put_contents($this->sFname, $sCont);
        return true;
    }

    /**
     * *
     * * Добавление/изменение/удаление записи в/из файла cookies:
     * * Аргументы:
     * *  1) $aFields - массив значений индивидуальных полей записи cookie
     * *     $aFields[0] - поле 'name' (имя параметра)
     * *     $aFields[1] - поле 'value' (значение параметра)
     * *     $aFields[2] - срок хранения записи в днях
     * * Возвращает значения:
     * *  1) false - в случае неправильного вызова
     * *  2) true - в случае успеха удаления
     * *  3) string - в случае успеха добавления/изменения, содержимое строки записи
     */
    function setCookie($aFields) {
        if (!$this->sFname || !$this->sPrefix) return false;
        if (!is_array($aFields) || !($n_arr  = count($aFields))) return false;
        $name   = $aFields[0];
        $cont   = file_exists($this->sFname) ? file_get_contents($this->sFname) : '';
        $cr     = (strpos($cont, "\r\n") !== false) ? "\r\n" : "\n";
        $a_rows = explode($cr, trim($cont, $cr));
        $i_row  = -1;
        foreach ($a_rows as $i => $row) {
            if (strpos($row, "\t" . $name . "\t") === false) continue;
            if (strpos($row, $this->sPrefix) !== 0) continue;
            $i_row = $i;
            break;
        }
        $ret = true;
        if ($n_arr > 1) {
            // add/modify:
            $val            = $aFields[1];
            $life           = ($n_arr > 2 && $aFields[1] >= 0) ? $aFields[1] : 1;
            if ($i_row < 0) $i_row          = count($a_rows);
            $n_exp          = ($life > 0) ? (time() + $life * 24 * 60 * 60) : 0;
            $a_rows[$i_row] = $ret            = $this->sPrefix . implode("\t", array($n_exp, $name, $val));
        }
        else if ($i_row >= 0) {
            // remove:
            unset($a_rows[$i_row]);
        }
        file_put_contents($this->sFname, implode($cr, $a_rows) . $cr);
        return $ret;
    }

    function getCookie($name) {
        $cont   = file_exists($this->sFname) ? file_get_contents($this->sFname) : '';
        $cr     = (strpos($cont, "\r\n") !== false) ? "\r\n" : "\n";
        $a_rows = explode($cr, trim($cont, $cr));
        foreach ($a_rows as $i => $row) {
            $n = explode("\t", $row);
            if (count($n) > 4 && $n[5] === $name) return $n;
        }
        return FALSE;
    }

    /*     * **
     * * Добавление/изменение записи в файл cookies:
     */

    function addCookie($sName, $sVal, $nLife = 0) {
        return $this->setCookie(array($sName, $sVal, $nLife));
    }

    /*     * **
     * * Удаление записи из файла cookies:
     */

    function removeCookie($sName) {
        return $this->setCookie(array($sName));
    }
    function clearAllCookie() {
        file_put_contents($this->sFname, '');
        return;
    }

}
