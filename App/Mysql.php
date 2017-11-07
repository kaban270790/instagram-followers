<?php

use Exceptions\TransactionDataBaseException;

/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 005 05.10
 * Time: 01:17:11
 */

class Mysql
{

    private static $link = null;

    private static $hasTransaction = false;

    /**
     * Простой запрос
     * @param $sql
     * @param array $params
     * @return bool|mysqli_result
     */
    public static function query($sql, array $params = array())
    {
        return self::stmtOpen($sql, $params)->close();
    }

    /**
     * Вытаскивание много строк
     * @param $sql
     * @return array
     */
    public static function select($sql, array $params = array())
    {
        $stmt = self::stmtOpen($sql, $params);
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Вытаскивание одной строки
     * @param $sql
     * @param $params
     * @return array
     */
    public static function selectRow($sql, array $params = array())
    {
        $sql .= " LIMIT 1";
        $stmt = self::stmtOpen($sql, $params);

        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    /**
     * Вытаскивание одной ячейки в одной строке
     * @param $sql
     * @return mixed - значение ячейки
     */
    public static function selectCell($sql)
    {
        $sql .= " LIMIT 1";
        $query = self::connect()->query($sql);
        $result = $query->fetch_row();
        if (!empty($result) && $result[0]) {
            return $result[0];
        }
        return null;
    }

    /**
     * Вытаскивание первой колонки в индексированном массиве
     * @param $sql
     * @return array
     */
    public static function selectColl($sql)
    {
        $query = self::connect()->query($sql);
        $rows = array();
        while ($cell = $query->fetch_row()) {
            if (!empty($cell) && $cell[0]) {
                $rows[] = $cell[0];
            }
        }
        return $rows;
    }

    /**
     * Возвращает последний вставленный ид
     * @return mixed
     */
    public static function insert_id()
    {
        return self::connect()->insert_id;
    }

    /**
     * Подключение к БД
     * @param bool $reconnect
     * @return mysqli
     */
    private static function connect($reconnect = false)
    {
        if (!self::$link || $reconnect === true) {
            $db_host = \Config::getConfig('database.db_host');
            $db_user = \Config::getConfig('database.db_user');
            $db_pass = \Config::getConfig('database.db_pass');
            $db_name = \Config::getConfig('database.db_name');
            $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if (mysqli_connect_errno()) {
                printf("Connect failed: %s\n", mysqli_connect_error());
                exit();
            }
            self::$link = $mysqli;
        }
        return self::$link;
    }

    /**
     * @param $sql
     * @param $params
     * @return mysqli_stmt
     */
    private static function stmtOpen($sql, $params)
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->bind_param(str_pad('', count($params), 's', STR_PAD_LEFT), ...$params);
        $stmt->execute();
        return $stmt;
    }

    /**
     * @param mysqli_stmt $stmt
     * @return bool
     */
    private static function stmtClose($stmt)
    {
        return $stmt->close();
    }

    /**
     * Начало транзакции
     * @param $flag
     * @return bool
     * @throws TransactionDataBaseException
     */
    public static function transaction($flag = null)
    {
        if (self::$hasTransaction === true) {
            throw new TransactionDataBaseException("Уже присутствует одна транзакция в данном соединении");
        }
        $result = self::connect()->begin_transaction($flag);
        if ($result === true) {
            self::$hasTransaction = true;
        } else {
            throw new TransactionDataBaseException("Ошибка при создании транзакции");
        }
        return $result;
    }

    /**
     * Применение транзации
     * @return bool
     * @throws TransactionDataBaseException
     */
    public static function commit()
    {
        if (self::$hasTransaction === false) {
            throw new TransactionDataBaseException("Транзакция не найдена");
        }
        $result = self::connect()->commit();
        if ($result === true) {
            self::$hasTransaction = false;
        } else {
            throw new TransactionDataBaseException("Ошибка применения транзакции");
        }
        return $result;
    }

    /**
     * Откат транзакции
     * @return bool
     * @throws TransactionDataBaseException
     */
    public static function rollback()
    {
        if (self::$hasTransaction === false) {
            throw new TransactionDataBaseException("Транзакция не найдена");
        }
        $result = self::connect()->rollback();
        if ($result === true) {
            self::$hasTransaction = false;
        } else {
            throw new TransactionDataBaseException("Ошибка отмены транзак");
        }
        return $result;
    }
}