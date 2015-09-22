<?php
/**
 * Created by Ostashev Dmitriy <ostashevdv@gmail.com>
 * -------------------------------------------------------------
 */

namespace ostashevdv\image;


use Intervention\Image\Exception\NotReadableException;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class ImageManager extends Component
{
    /** @var string драйвер обрабатывающий изображения*/
    public $driver = 'imagick';

    /** @var int ширина по умолчанию */
    public $defaultWidth = 640;

    /** @var int высота по умолчанию */
    public $defaultHeight = 320;

    /** @var null|string изображение заглушка  */
    public $imageCap = null;

    /** @var array пользовательские настройки миниатюр */
    public $thumbs = [];

    /** @var array настройки миниатюр по умолчанию */
    protected $_thumbs = [
        'sm' => ['width' => 320,  'height' => 180],
        'md' => ['width' => 640,  'height' => 360],
        'lg' => ['width' => 1280, 'height' => 720]
    ];

    /** @var string папка на сервере для кеша */
    public $cacheDir = '@webroot/assets/thumbs';

    /** @var string  */
    public $cacheUrl = '@web/assets/thumbs';

    public function init()
    {
        $this->cacheDir = rtrim($this->cacheDir, '/') . '/';
        $this->cacheUrl = rtrim($this->cacheUrl, '/') . '/';
        $this->thumbs = ArrayHelper::merge($this->_thumbs, $this->thumbs);
    }



    /**
     * Initiates an Image instance from different input types
     *
     * @param  mixed $data
     *
     * @return \Intervention\Image\Image
     */
    public function make($data)
    {
        return $this->createDriver()->init($data);
    }

    /**
     * Creates an empty image canvas
     *
     * @param  integer $width
     * @param  integer $height
     * @param  mixed $background
     *
     * @return \Intervention\Image\Image
     */
    public function canvas($width=null, $height=null, $background = null)
    {
        $width = $width===null ? $this->defaultWidth : $width;
        $height = $height===null ? $this->defaultWidth : $height;
        return $this->createDriver()->newImage($width, $height, $background);
    }

    /**
     * Creates a driver instance according to config settings
     *
     * @return \Intervention\Image\AbstractDriver
     */
    private function createDriver()
    {
        $drivername = ucfirst($this->driver);
        $driverclass = sprintf('Intervention\\Image\\%s\\Driver', $drivername);

        if (class_exists($driverclass)) {
            return new $driverclass;
        }

        throw new \Intervention\Image\Exception\NotSupportedException(
            "Driver ({$drivername}) could not be instantiated."
        );
    }

    public function thumb($url, $mode = 'md')
    {
        // Устанавливаем ширину и высоту миниатюры
        is_array($mode) ? @extract($mode) : @extract($this->thumbs[$mode]);
        if (empty($width) || empty($height)) {
            throw new InvalidConfigException('Вы должны указать корректную настройку ширины и высоты миниатюры.');
        }

        //Нормализация Url
        $url = $this->normalizeUrl($url);

        // Формируем параметры миниатюры
        $dest = [];
        $dest['name'] = md5($url)."-{$mode}.".pathinfo($url, PATHINFO_EXTENSION);
        $dest['dir'] = Yii::getAlias($this->cacheDir);
        $dest['path'] = $dest['dir'].$dest['name'];
        $dest['url'] = Yii::getAlias($this->cacheUrl.$dest['name']);

        //Проверяем наличие изображения. Результаты функции file_exists() кешируется. Подробнее смотрите в разделе clearstatcache()
        if (file_exists($dest['path'])) {
            return $dest['url'];
        }

        //Создание миниатюры
        try {
            FileHelper::createDirectory($dest['dir']);
            $this->make($url)->fit($width, $height)->save($dest['path']);
        } catch( NotReadableException $e) {

        }

        return $dest['url'];
    }


    /**
     * Нормализиция Url
     * @param $url
     * @return string
     */
    protected function normalizeUrl($url)
    {
        $url = \Sabre\Uri\normalize($url);
        $parse = \Sabre\Uri\parse($url);

        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $parse['host'] = isset($parse['host']) ? $parse['host'] : $host;

        $parse['scheme'] = isset($parse['scheme']) ? $parse['scheme'] : 'http';

        return \Sabre\Uri\build($parse);
    }



} 