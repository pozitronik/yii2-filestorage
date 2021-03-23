FileStorage
===========
Filestorage module for YII2

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Run

```
php composer.phar require pozitronik/yii2-filestorage "dev-master"
```

or add

```
"pozitronik/yii2-filestorage": "dev-master"
```

to the require section of your `composer.json` file.


Requirements
------------

Yii2,
PHP >= 7.4.0


Usage
-----

Конфигурация модуля должна задаваться в web.php и console.php (для корректного выполнения миграции):

```php
'filestorage' => [
	'class' => FSModule::class,
	'defaultRoute' => 'index',
	'params' => [
		'tableName' => 'sys_file_storage',//используемая таблица хранения метаданных
		'tableNameTags' => 'sys_file_storage_tags',//используемая таблица хранения тегов
		'base_dir' => '@app/web/uploads/',//каталог хранения файлов
		'models_subdirs' => true,//файлы каждой модели кладутся в подкаталог с именем модели
		'name_subdirs_length' => 2//если больше 0, то файлы загружаются в подкаталоги по именам файлов (параметр регулирует длину имени подкаталогов)
	]
]
```
после конфигурирования выполняем миграцию:

```
yii migrate/up --migrationPath=vendor/pozitronik/yii2-filestorage/migrations
```

Логика: добавляем FileStorageTrait к любому наследнику Model::class. Он добавляет классу атрибут UploadedFile $uploadFileInstance, а также методы
```php 
    uploadFile(array $tags = [], string $instanceName = 'uploadFileInstance'):array //добавление файла с заданным набором тегов.
```
и
```php
    files(?array $tags = null):array //получение списка загрузок по заданному набору тегов.
```

Мультизагрузка пока не реализована.

При загрузке через uploadFile, загрузка привязывается к модели. Для ActiveRecord привязка происходит непосредственно к экземпляру класса, для обычных моделей -- ко всему классу.
Тег -- произвольная метка, добавляемая загрузке. Одной загрузке может быть добавлено произвольное количество тегов.
К модели может быть добавлено произвольное количество загрузок с одинаковым набором тегов. Через это реализована версионность: последняя загрузка считается актуальной, остальные -- предыдущими версиями, доступными отдельно).

Метод files() вернёт список всех загрузок модели (опционально -- только по указанным тегам). У каждой загрузки доступны
* метод download() -- отдаёт скачку привязанного к загрузке файла
* атрибут $versions -- отдаёт нумерованный список версий этой загрузки (где 0 -- наиболее актуальная).

Демо - в @vendor/src/controllers/IndexController.php, там так или иначе показаны все фичи.

Для непосредственной загрузки указываем атрибут fileUploadInstance и делаем save()