yii2-image
==========
Обертка над  [intervention/image](http://image.intervention.io/) для работы с изображениями.

Возможности:

1. Улучшенный метод создания миниатюр (по сравнению с imagick) сочетающий в себе crop и resize
2. Работа с url содержащими кирилицу
3. ???

Устновка
------------
Выполните команду

```
php composer require ostashevdv/yii2-image
```

или добавьте

```
"ostashevdv/yii2-image": "dev-master"
```

в секцию require вашего `composer.json`

Использование
-------------

Настройте компонент в конфигурации вашего приложения
 ```php
 return [
    'components' => [
        'image' => [
            'class' => 'ostashevdv\image\ImageManager',
            'cachePath' => '@web/assets/thumbs/'
        ],
    ]
 ];
 ```
создавайте миниатюры
```php
<h1><?=$model->title?></h1>
<img src="<?=Yii::$app->image->thumb($model->image, 300, 300)?>" />
```
