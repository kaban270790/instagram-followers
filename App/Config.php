<?php

use Exceptions\ConfigFileNotFoundException;
use Exceptions\ConfigNotFountException;

/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 005 05.10
 * Time: 02:18:14
 */
class Config
{
    /**
     * @var string путь до файла с настроками
     */
    private static $config_file = APP_DIR . '/config.php';

    /**
     * Возвращает значение настройки, можно запрашивать
     * database.login - вернет из массива database значение с ключом login
     * @param $setting_name
     * @return mixed|null
     * @throws ConfigFileNotFoundException
     * @throws ConfigNotFountException
     */
    public static function getConfig($setting_name)
    {
        if (file_exists(self::$config_file) === false) {
            throw new ConfigFileNotFoundException("Файл с конфигурацие не найден");
        }
        $ar_setting = explode('.', $setting_name);
        $value = null;
        if (!empty($ar_setting)) {
            $value = include self::$config_file;
            foreach ($ar_setting as $_setting) {
                if ($value[$_setting] !== null) {
                    $value = $value[$_setting];
                }
            }
        }
        if (is_null($value)) {
            throw new ConfigNotFountException("Настройка {$setting_name} не найдена");
        }
        return $value;
    }
}
