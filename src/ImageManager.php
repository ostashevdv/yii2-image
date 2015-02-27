<?php
/**
 * Created by Ostashev Dmitriy <ostashevdv@gmail.com>
 * -------------------------------------------------------------
 */

namespace ostashevdv\image;


use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

class ImageManager extends Component
{
    /** @var string драйвер обрабатывающий изображения*/
    public $driver = 'imagick';

    /** @var string путь к папке с кешем изображений */
    public $cachePath = '@web/assets/thumbs/';

    /** @var int ширина по умолчанию */
    public $defaultWidth = 600;

    /** @var int высота по умолчанию */
    public $defaultHeight = 600;

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

    /**
     * @param string $url
     * @param int $width
     * @param int $height
     * @param string | callable $cachePath папка для кеша
     * @return string | null
     */
    public function thumb($url, $width=null, $height=null, $cachePath=null)
    {
        $width = $width===null ? $this->defaultWidth : $width;
        $height = $height===null ? $this->defaultWidth : $height;
        $cachePath = $cachePath===null ? $this->cachePath : $cachePath;
        if(is_callable($cachePath)) {
            $cachePath = call_user_func($cachePath, $url);
        }

        // Нормализация url
        $url = \Sabre\Uri\normalize($url);
        $parts = \Sabre\Uri\parse($url);
        if (!isset($parts['host'])) {
            $host = \yii\helpers\Url::home(true);
            $url = \Sabre\Uri\normalize($host . $url);
        }

        /** @var параметры для создания кеша $dest */
        $dist = [];
        $dist['name'] = md5($url)."[{$width}x{$height}].".pathinfo($url, PATHINFO_EXTENSION);
        $dist['dir'] = FileHelper::normalizePath(Yii::getAlias('@webroot') . '/' . Yii::getAlias($cachePath));
        $dist['path'] = $dist['dir'].DIRECTORY_SEPARATOR.$dist['name'];

        if (!file_exists($dist['path'])) {
            try {
                FileHelper::createDirectory($dist['dir']);
                $this->make($url)->fit($width, $height)->save($dist['path']);
            } catch (\Exception $e) {
                return null;
            }
        }
        return Yii::getAlias($cachePath) .'/'. $dist['name'];
    }
} 