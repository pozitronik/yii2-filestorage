<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\traits;

use pozitronik\filestorage\models\FileStorage;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use yii\base\Exception;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

/**
 * Trait FileStorageTrait
 *
 * @property UploadedFile $uploadFileInstance
 */
trait FileStorageTrait {
	public $uploadFileInstance;

	/**
	 * Шорткат для загрузки файла по имени файлового атрибута сразу с привязкой к этому атрибуту
	 * @param string $attributeName
	 * @param Model|null $toModel Если не null, то загружаемые файлы будут ассоциированы с указанной моделью
	 * @return FileStorage[]
	 * @throws Throwable
	 */
	public function uploadAttribute(string $attributeName, ?Model $toModel = null):array {
		return $this->uploadFile([$attributeName], $attributeName, $toModel);
	}

	/**
	 * Загружает все пришедшие с моделью файлы, тегируя их по именам атрибутов
	 * @param Model|null $toModel
	 * @return FileStorage[] При успехе возвращает массив загруженных объектов хранения файла
	 * @throws Throwable
	 */
	public function uploadAttributes(?Model $toModel = null):array {
		$result = [];
		$instances = ArrayHelper::getValue($_FILES, $this->formName().".name", []);
		foreach ($instances as $attributeName => $fileName) {
			$result[] = $this->uploadAttribute($attributeName, $toModel);
		}
		return array_merge(...$result);
	}

	/**
	 * Загружает файл в соответствующий модели каталог, возвращает полный путь или null в случае ошибки
	 * @param string[] $tags Массив тегов, прибиваемых к загрузке
	 * @param string $instanceName Параметр для переопределения имени инпута при необходимости
	 * @param Model|null $toModel Если не null, то загружаемые файлы будут ассоциированы с указанной моделью
	 * @return FileStorage[] При успехе возвращает массив загруженных объектов хранения файла
	 * @throws Throwable
	 */
	public function uploadFile(array $tags = [], string $instanceName = 'uploadFileInstance', ?Model $toModel = null):array {
		$result = [];

		$uploadModel = $toModel??$this;

		/** @var Model $this */
		if (null !== $uploadFileInstance = UploadedFile::getInstance($this, $instanceName)) {
			$result[] = $uploadModel->processUploadInstance($uploadFileInstance, $tags);
		} elseif ([] !== $uploadFileInstances = UploadedFile::getInstances($this, $instanceName)) {
			foreach ($uploadFileInstances as $uploadFileInstance) {
				$result[] = $uploadModel->processUploadInstance($uploadFileInstance, $tags);
			}
		}
		return $result;
	}

	/**
	 * @param mixed $rawData Любые данные, помещаемые в файл
	 * @param string|null $filename Название файла для сохраняемых данных
	 * @param array $tags Массив тегов, прибиваемых к загрузке
	 * @param Model|null $toModel Если не null, то загружаемые файлы будут ассоциированы с указанной моделью
	 * @return FileStorage При успехе возвращает загруженный объект хранения файла
	 * @throws Exception
	 * @throws Throwable
	 */
	public function uploadRawData($rawData, ?string $filename = null, array $tags = [], ?Model $toModel = null):FileStorage {
		$path = null === $filename?tempnam(sys_get_temp_dir(), $this->formName()):sys_get_temp_dir().DIRECTORY_SEPARATOR.$filename;

		if (
			(false === $path)
			|| (false === $temp = fopen($path, 'wb+'))
			|| false === fwrite($temp, $rawData)
			|| false === fclose($temp)
		) throw new Exception("Can't access temp file (path=$path!");
		/** @var Model $this */
		return FileStorage::fromFile($path, $toModel??$this, $tags);
	}

	/**
	 * @param UploadedFile $uploadFileInstance
	 * @param string[] $tags
	 * @return FileStorage
	 * @throws Throwable
	 */
	private function processUploadInstance(UploadedFile $uploadFileInstance, array $tags = []):FileStorage {
		$fileStorage = new FileStorage([
			'model' => $this,
			'uploadFileInstance' => $uploadFileInstance,
			'tags' => $tags
		]);

		if (!$fileStorage->save()) throw new ServerErrorHttpException("Не могу сохранить объект {$fileStorage->formName()}");

		return $fileStorage;
	}

	/**
	 * Вернуть все файлы модели. Если задан параметр $tags -- только те, которые попадают под эти теги
	 * @param null|string[] $tags
	 * @return FileStorage[]
	 * @throws Throwable
	 */
	public function files(?array $tags = null):array {
		$fileStorage = new FileStorage([
			'model' => $this
		]);

		return $fileStorage->getActualFiles($tags);
	}

	/**
	 * Копирует файл (физически и логически) из текущей записи к текущей модели
	 * @param FileStorage $fromStorage
	 * @param string[] $tags
	 * @return FileStorage
	 * @throws Throwable
	 */
	public function copyFile(FileStorage $fromStorage, array $tags = []):FileStorage {
		/** @var Model $this */
		return FileStorage::fromFile($fromStorage->path, $this, $tags, false);
	}

	/**
	 * Специально, чтобы можно было трейтить только к моделям!
	 * @see Model::formName()
	 */
	abstract public function formName();
}